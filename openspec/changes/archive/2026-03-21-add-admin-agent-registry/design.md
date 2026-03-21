# Design: Admin Agent Registry

## Context

The architecture overview defines four platform components: Telegram Adapter, Event Bus, Agent Registry, and Command Router. The Agent Registry is described as the source of truth for available agents, manifests, config, and enabled/disabled state. This change makes that component real: a Postgres-backed registry with a platform API and admin UI.

The agent manifest format is already sketched in `docs/architecture-overview.md`. This design formalises it into a versioned JSON schema and adds the fields needed for A2A routing and admin display.

## Goals / Non-Goals

### Goals
- Formal, validated agent manifest JSON schema
- Postgres-backed agent registry with enable/disable lifecycle
- Deterministic manifest hash tracking to detect re-provisioning/migration needs
- Unified migration execution contract across stacks
- Explicit and observable lifecycle state machine
- Core-only migration ownership (single DDL owner)
- Reliable orchestration with lock + retries + reconcile loop
- Event Bus and Command Router respect registry state
- Admin panel shows all registered agents and allows toggling
- knowledge-base agent ships with a valid manifest

### Non-Goals
- Marketplace or plugin distribution (out of MVP scope)
- Granular per-community enable/disable (single-community MVP)
- Hot-reload of agent code without service restart
- Automatic manifest discovery (agents self-register on boot, not auto-discovered)

---

## Agent Manifest Schema

Every agent service MUST provide a `manifest.json` at a well-known path within its container (`/app/manifest.json` or via `GET /manifest`).

```json
{
  "name": "knowledge-base",
  "version": "0.2.0",
  "description": "Extracts, stores, and indexes structured knowledge from community chat messages.",
  "permissions": ["moderator", "admin"],
  "commands": ["/wiki", "/wiki add"],
  "events": ["message.created", "command.received", "schedule.tick"],
  "config_schema": {
    "type": "object",
    "properties": {
      "web_encyclopedia_enabled": { "type": "boolean", "default": true },
      "embedding_model": { "type": "string", "default": "text-embedding-3-small" },
      "extraction_rate_limit": { "type": "integer", "default": 60 }
    },
    "required": []
  },
  "a2a_endpoint": "http://knowledge-agent/a2a",
  "capabilities": ["knowledge_search", "extract_from_messages", "get_tree"],
  "admin_url": "/admin/knowledge",
  "health_url": "http://knowledge-agent/health",
  "migrations": {
    "run_command": "./bin/console doctrine:migrations:migrate --no-interaction"
  }
}
```

### Mandatory Fields
| Field | Type | Description |
|-------|------|-------------|
| `name` | string (slug) | Unique agent identifier, kebab-case |
| `version` | semver string | Agent code version |
| `description` | string | Human-readable purpose (shown in admin) |
| `permissions` | string[] | Minimum roles allowed to trigger this agent |
| `commands` | string[] | Chat commands this agent handles (empty if none) |
| `events` | string[] | Platform event types this agent subscribes to |
| `a2a_endpoint` | URL | Internal A2A request target |

### Optional Fields
| Field | Type | Description |
|-------|------|-------------|
| `config_schema` | JSON Schema object | Schema for runtime config; rendered as form in admin |
| `capabilities` | string[] | Named A2A intents this agent exposes |
| `admin_url` | path | Deep link to agent's own admin page |
| `health_url` | URL | Health check URL polled by platform |
| `migrations` | object | Optional migration contract (`run_command`, `run_on_startup`) |

---

## Registry Data Model

```sql
CREATE TABLE agent_registry (
    id           UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name         VARCHAR(64) NOT NULL UNIQUE,      -- slug: knowledge-base
    version      VARCHAR(32) NOT NULL,              -- 0.2.0
    manifest     JSONB NOT NULL,                    -- full manifest JSON
    manifest_hash VARCHAR(64) NOT NULL,             -- sha256(normalized manifest json)
    last_processed_manifest_hash VARCHAR(64),       -- hash for which provisioning+migrations succeeded
    config       JSONB NOT NULL DEFAULT '{}',       -- resolved runtime config
    enabled      BOOLEAN NOT NULL DEFAULT FALSE,
    lifecycle_state VARCHAR(32) NOT NULL DEFAULT 'registered',
    storage_sync_required BOOLEAN NOT NULL DEFAULT FALSE,
    lifecycle_retry_count INTEGER NOT NULL DEFAULT 0,
    registered_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at   TIMESTAMPTZ NOT NULL DEFAULT now(),
    enabled_at   TIMESTAMPTZ,
    disabled_at  TIMESTAMPTZ,
    enabled_by   VARCHAR(128),                      -- admin user who toggled
    health_status VARCHAR(32) DEFAULT 'unknown',    -- healthy|unhealthy|unknown
    last_migration_at TIMESTAMPTZ,
    last_migration_status VARCHAR(32),              -- success|failed|skipped
    last_migration_error TEXT
);
```

```sql
CREATE TABLE agent_lifecycle_runs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    run_id UUID NOT NULL,
    agent_name VARCHAR(64) NOT NULL,
    manifest_hash VARCHAR(64) NOT NULL,
    step VARCHAR(64) NOT NULL,                 -- lock|provision|migrate|finalize
    status VARCHAR(32) NOT NULL,               -- success|failed|skipped
    actor VARCHAR(128),                        -- admin user / system reconciler
    started_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    finished_at TIMESTAMPTZ,
    duration_ms INTEGER,
    error TEXT
);
```

---

## Registry Lifecycle

```
Agent service starts
    │
    ▼
POST /api/v1/internal/agents/register
    │  (sends manifest.json payload)
    │
    ▼
AgentRegistry::register()
    ├── normalize manifest json (stable key order) -> sha256 => manifest_hash
    ├── if new: INSERT with enabled=false, storage_sync_required=true
    └── if existing:
        ├── update version + manifest + manifest_hash
        └── if hash changed: storage_sync_required=true
    │
    ▼
Admin clicks "Enable"
    │
    ▼
AgentRegistry::enable()
    ├── acquire per-agent lock (DB advisory lock by agent name)
    ├── set lifecycle_state = provisioning|migration_pending|migrating
    ├── run provisioning if storage_sync_required=true
    ├── always run unified migration runner command (core-owned)
    ├── on success:
    │   ├── last_processed_manifest_hash = manifest_hash
    │   ├── storage_sync_required = false
    │   ├── last_migration_status = success
    │   ├── lifecycle_state = enabled
    │   └── enabled = true
    └── on failure:
        ├── enabled remains false
        ├── lifecycle_state = failed_provisioning|failed_migration
        ├── lifecycle_retry_count += 1
        └── last_migration_status = failed + error text
    │
    ▼
Event Bus and Command Router read enabled state per request cycle
(cached in Redis with 10s TTL to avoid DB read per event)

    │
    ▼
Reconciler tick (system actor, every 60s)
    ├── finds rows in failed_* or storage_sync_required=true
    ├── applies retry policy with exponential backoff and max attempts
    └── retries lifecycle flow under same lock and logs run steps
```

---

## Lifecycle State Machine

States:
- `registered` — manifest accepted, agent disabled by default
- `provisioning` — storage provisioning in progress
- `migration_pending` — provisioning done, migration not started
- `migrating` — migration command currently running
- `enabled` — agent ready and active
- `failed_provisioning` — provisioning failed, retryable
- `failed_migration` — migration failed, retryable

Allowed transitions:
- `registered -> provisioning -> migration_pending -> migrating -> enabled`
- `registered -> failed_provisioning`
- `provisioning -> failed_provisioning`
- `migration_pending -> migrating -> failed_migration`
- `failed_* -> provisioning|migration_pending` (retry path)
- `enabled -> migrating` (re-enable/reconcile on manifest change)

---

## Manifest Canonicalization Policy

To avoid hash drift:
- normalize manifest JSON with stable key ordering (recursive)
- preserve array order unless field contract explicitly defines sorted semantics
- serialize without whitespace
- hash via `sha256` over canonical JSON bytes
- store algorithm version as `manifest_hash_algo` in code constant for future migration

---

## Event Bus Integration

```
message.created event arrives
    │
    ▼
EventBus::dispatch()
    │
    ├── fetch enabled agents subscribed to 'message.created' (from Redis cache)
    └── for each enabled agent: A2A call to agent.a2a_endpoint
        └── disabled agents: skipped, not called
```

---

## Command Router Integration

```
/wiki query received
    │
    ▼
CommandRouter::resolve('/wiki')
    │
    ├── lookup command in manifest.commands of enabled agents
    ├── if found and agent enabled: route A2A to agent
    └── if not found or agent disabled: return "command not available" response
```

---

## Admin UI: Agent Management Page

Route: `/admin/agents`

Layout:
- Table of registered agents: name, version, description, enabled badge, lifecycle state, health badge, last updated
- Each row: toggle button (enable/disable), "Налаштування" link (→ agent's `admin_url` if present)
- Dry-run button: shows lifecycle plan before enable (`provision?`, `migrate`, `expected skips`)
- Step timeline: last lifecycle run details from `agent_lifecycle_runs`
- Expand row: shows manifest fields (commands, events, permissions, capabilities)
- Config editor: if `config_schema` present, renders form fields; "Зберегти конфіг" saves to `agent_registry.config`

---

## Decisions

### Decision: Agents self-register on boot
- **Why**: No manual manifest file management; agent and manifest evolve together; platform always has fresh version info
- **Alternative**: admin manually uploads manifest JSON — error-prone, version drift

### Decision: Enable always runs migrations
- **Why**: Prevent enabling an agent with outdated schema after deploy or rollback; migrations must be a hard gate
- **Alternative**: migrate only on first install — leaves schema drift risk on subsequent releases

### Decision: Core is the single migration owner
- **Why**: Avoid race conditions and split-brain between service startup and platform orchestration
- **Alternative**: allow agents to run startup DDL — non-deterministic ordering and hard-to-debug failures

### Decision: Use per-agent lock for lifecycle flow
- **Why**: Prevent concurrent enable/reconcile runs for the same agent
- **Alternative**: optimistic concurrency only — can still double-run migrations under contention

### Decision: Reconcile loop repairs incomplete lifecycle runs
- **Why**: Gives eventual consistency after crashes/timeouts and reduces manual recovery
- **Alternative**: manual-only recovery — operational burden and hidden drift

### Decision: Registry state cached in Redis
- **Why**: Event Bus and Command Router are called on every incoming message; Postgres read per-event is unnecessary load; 10s TTL is acceptable stale window for enable/disable changes
- **Alternative**: Always read Postgres — simpler but adds latency to hot path

### Decision: `enabled = false` by default on first registration
- **Why**: New agents should not be active until explicitly approved by admin; prevents accidental activation
- **Alternative**: `enabled = true` by default — risky for production environments

---

## Risks / Trade-offs

| Risk | Mitigation |
|------|-----------|
| Agent fails to register (service down at boot) | Registration is retried with exponential backoff; agent is listed as `health_status=unknown` until first successful registration |
| Lifecycle run interrupted mid-step | Reconciler resumes from persisted lifecycle state and retry policy |
| Admin changes config but agent reads stale cached manifest | Config changes invalidate Redis cache immediately on save |
| Manifest schema mismatch between agent version and registry | Registry validates manifest against JSON schema on registration; rejects invalid manifests with structured error |
