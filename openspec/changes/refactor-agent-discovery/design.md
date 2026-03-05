## Context

Core currently uses a push model: agents call `POST /api/v1/internal/agents/register` on startup.
This couples every agent to core's availability and forces language-agnostic boilerplate
(registration HTTP client, retry logic, token handling) in each agent.

Traefik is already in the stack and has an HTTP management API at `http://traefik:8080/api/`.
It knows every service in the Docker network. We can use it as a passive service catalog.

## Goals / Non-Goals

- Goals:
  - Core owns 100% of agent lifecycle (no push from agents)
  - Single manifest contract (`GET /api/v1/manifest`) is the only requirement for agents
  - Automatic discovery: new agent added to compose → discovered within 60s
  - Convention verification: violations visible in admin panel without manual inspection
  - Agent compliance test suite runnable via `make conventions-test`

- Non-Goals:
  - URL-based agent provisioning (install from GitHub URL) — future scope
  - Multi-host / cross-cluster discovery
  - Real-time Docker event streaming (60s polling is sufficient for MVP)

## Decisions

### D1: Discovery source — Traefik API (not Docker socket)

- **Chosen**: Query `http://traefik:8080/api/http/services` to get all running services.
  Filter by name pattern `*-agent@docker` (all Docker-provider services whose name ends in `-agent`).
- **Why**: Traefik is already in the stack. Docker socket mounting grants root-equivalent access
  and is a security risk. Traefik API is read-only and scoped to routing metadata.
- **Alternative considered**: Docker socket + labels. Rejected — security risk, extra dep.
- **Alternative considered**: Env-var list (`AGENT_ENDPOINTS=...`). Rejected — requires manual
  config update when adding agents.

### D2: Internal agent URL construction

- From Traefik service name `knowledge-agent@docker`, the internal Docker DNS hostname is `knowledge-agent`.
- Core calls `http://knowledge-agent/api/v1/manifest` (port 80 by default).
- **Non-standard ports**: If an agent runs on a non-80 port internally, it can set the
  `ai.platform.manifest.path` label to include port, or expose port 80 via its container config.
  For MVP: all agents internally listen on port 80 or configure Traefik to proxy to the correct port.

### D3: Naming convention — service name suffix `-agent`

- **Chosen**: Services ending in `-agent` in compose.yaml are candidates.
- Core additionally checks for Docker label `ai.platform.agent=true` if available via Traefik metadata.
  If label not available through Traefik API, name suffix alone is the filter.
- Traefik API service names follow `{service-name}@docker` format.

### D4: Discovery timing — startup command + 60s cron

- `AgentDiscoveryCommand` runs as a Symfony console command.
- Invoked via Supervisor or `docker compose` entrypoint on core startup.
- Registered as a scheduled task (using existing `php bin/console` cron pattern) every 60 seconds.
- **Why 60s**: Balance between responsiveness (new agent visible quickly) and avoiding hammering
  the Traefik API. For manual refresh: admin panel "Run Discovery" button triggers on demand.

### D5: Agent state machine

```
         ┌─────────┐
         │ unknown  │  (not yet seen by discovery)
         └────┬─────┘
              │ manifest fetch OK, schema valid
              ▼
         ┌─────────┐
         │ healthy  │  ←──────────────────────────┐
         └────┬─────┘                              │ manifest re-validates OK
              │ manifest fetch OK, schema warnings  │
              ▼                                    │
         ┌──────────┐                              │
         │ degraded  │ ──────────────────────────► ┘
         └────┬──────┘
              │ health check fails N times
              ▼
        ┌────────────┐
        │ unavailable │  (container down/crashed)
        └─────┬───────┘
              │ manifest unreadable / JSON invalid
              ▼
          ┌───────┐
          │ error  │
          └───────┘
```

- `healthy` → `degraded`: manifest has warnings (missing optional fields, semver mismatch)
- `healthy/degraded` → `unavailable`: health check fails 3 consecutive times
- `unavailable` → `healthy/degraded`: next discovery run succeeds
- any state → `error`: manifest returns non-JSON or HTTP 5xx
- `error`: violations stored as JSON, shown in admin panel on click

### D6: Manifest JSON Schema validation

Core validates manifest against a JSON Schema stored at `config/agent-manifest-schema.json`.
Violations are categorized:
- **Error** (blocks full registration): `name` missing, `version` missing, invalid JSON
- **Warning** (allows degraded registration): `a2a_endpoint` missing when capabilities declared,
  `version` not semver, unknown extra fields

### D7: Convention test suite — Codecept.js + Playwright REST helper

- Framework: Codecept.js with `@codeceptjs/playwright` and REST helper
- Tests run outside Docker, calling agents via Traefik-published ports
- Agent list auto-discovered from Traefik API or via `AGENT_URLS` env var
- `make conventions-test`: `cd tests/agent-conventions && npm ci && npx codeceptjs run --steps`
- Tests run in Docker via `docker compose run --rm node-test-runner` for CI

### D8: Remove KnowledgeRegisterCommand — BREAKING

- `apps/knowledge-agent/src/Command/KnowledgeRegisterCommand.php` — deleted
- `make knowledge-register` Makefile target — removed
- `apps/knowledge-agent/config/services.yaml` — remove command wiring

## Risks / Trade-offs

- **Traefik API format change** → Mitigation: pin Traefik version in compose.yaml; add integration
  test for API response parsing
- **60s discovery lag** → Mitigation: "Run Discovery" button in admin panel for immediate refresh
- **Internal port assumption (80)** → Mitigation: document convention; future: read port from
  Traefik service backends

## Migration Plan

1. Add `ai.platform.agent=true` Docker label to existing agents in compose.yaml
2. Delete `KnowledgeRegisterCommand`
3. Deploy `AgentDiscoveryCommand` — first run re-registers all running agents
4. Existing registry data is preserved (upsert by `name`)

## Open Questions

- Should discovery run as a long-lived daemon or a cron-triggered command? (Decision: cron via
  Symfony Scheduler, 60s interval — keeps it simple, no daemon complexity)
- Should we expose Traefik API via a dedicated network or keep it on the existing internal network?
  (Decision: keep existing — Traefik API is already on internal network, not exposed externally)
