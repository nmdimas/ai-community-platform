# Design: Admin Agent Registry

## Context

The architecture overview defines four platform components: Telegram Adapter, Event Bus, Agent Registry, and Command Router. The Agent Registry is described as the source of truth for available agents, manifests, config, and enabled/disabled state. This change makes that component real: a Postgres-backed registry with a platform API and admin UI.

The agent manifest format is already sketched in `docs/architecture-overview.md`. This design formalises it into a versioned JSON schema and adds the fields needed for A2A routing and admin display.

## Goals / Non-Goals

### Goals
- Formal, validated agent manifest JSON schema
- Postgres-backed agent registry with enable/disable lifecycle
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
  "health_url": "http://knowledge-agent/health"
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

---

## Registry Data Model

```sql
CREATE TABLE agent_registry (
    id           UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name         VARCHAR(64) NOT NULL UNIQUE,      -- slug: knowledge-base
    version      VARCHAR(32) NOT NULL,              -- 0.2.0
    manifest     JSONB NOT NULL,                    -- full manifest JSON
    config       JSONB NOT NULL DEFAULT '{}',       -- resolved runtime config
    enabled      BOOLEAN NOT NULL DEFAULT FALSE,
    registered_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at   TIMESTAMPTZ NOT NULL DEFAULT now(),
    enabled_at   TIMESTAMPTZ,
    disabled_at  TIMESTAMPTZ,
    enabled_by   VARCHAR(128),                      -- admin user who toggled
    health_status VARCHAR(32) DEFAULT 'unknown'     -- healthy|unhealthy|unknown
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
    ├── if new: INSERT with enabled=false
    └── if existing: UPDATE version + manifest (preserve enabled state + config)
    │
    ▼
Admin toggles enable/disable
    │
    ├── enable:  UPDATE enabled=true, enabled_at=now(), enabled_by=admin
    └── disable: UPDATE enabled=false, disabled_at=now()
    │
    ▼
Event Bus and Command Router read enabled state per request cycle
(cached in Redis with 10s TTL to avoid DB read per event)
```

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
- Table of registered agents: name, version, description, status badge (Enabled/Disabled), health badge (Healthy/Unhealthy/Unknown), last updated
- Each row: toggle button (enable/disable), "Налаштування" link (→ agent's `admin_url` if present)
- Expand row: shows manifest fields (commands, events, permissions, capabilities)
- Config editor: if `config_schema` present, renders form fields; "Зберегти конфіг" saves to `agent_registry.config`

---

## Decisions

### Decision: Agents self-register on boot
- **Why**: No manual manifest file management; agent and manifest evolve together; platform always has fresh version info
- **Alternative**: admin manually uploads manifest JSON — error-prone, version drift

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
| Admin changes config but agent reads stale cached manifest | Config changes invalidate Redis cache immediately on save |
| Manifest schema mismatch between agent version and registry | Registry validates manifest against JSON schema on registration; rejects invalid manifests with structured error |
