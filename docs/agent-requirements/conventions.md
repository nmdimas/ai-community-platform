# Agent Platform Conventions

Every service that participates in the AI Community Platform agent ecosystem MUST implement the
conventions described here. These are the minimum contracts that allow `core` to discover,
register, verify, and manage agents automatically — without any agent-specific code in core.

---

## 1. Docker Compose Naming & Labels

Every agent service in `compose.yaml` MUST:

| Requirement | Value |
|---|---|
| Service name | End with `-agent` (e.g., `knowledge-agent`, `news-maker-agent`) |
| Docker label | `ai.platform.agent=true` |
| Docker label | `ai.platform.manifest.path=/api/v1/manifest` *(optional override, default assumed)* |

**Example:**
```yaml
services:
  knowledge-agent:
    build: ...
    labels:
      - "ai.platform.agent=true"
    networks:
      - platform
```

Core discovers agents by querying the Traefik API for services matching the `*-agent` naming
pattern. The Docker label provides an explicit opt-in for non-standard service names.

---

## 2. Manifest Endpoint

**`GET /api/v1/manifest`** — REQUIRED, no authentication.

Returns agent metadata used by core for registration, capability routing, and OpenClaw tool catalog.

### Minimum valid response (HTTP 200):

```json
{
  "name": "knowledge-agent",
  "version": "1.2.0",
  "capabilities": ["search_knowledge", "extract_from_messages"]
}
```

### Full response schema:

```json
{
  "name":         "string (required) — stable slug, kebab-case",
  "version":      "string (required) — semver e.g. 1.0.0",
  "description":  "string (optional)",
  "capabilities": ["string", "..."] ,
  "a2a_endpoint": "string (URL, required if capabilities non-empty)",
  "health_url":   "string (URL, optional — defaults to /health on same host)",
  "admin_url":    "string (URL, optional — link to agent admin panel)",
  "capability_schemas": {
    "<capability>": {
      "input_schema": { "type": "object", "properties": {} }
    }
  }
}
```

### Field rules:

| Field | Required | Notes |
|---|---|---|
| `name` | ✅ | Stable identifier. Changing it creates a new agent in registry |
| `version` | ✅ | Must follow semver `MAJOR.MINOR.PATCH` |
| `capabilities` | ✅ | May be `[]` if agent has no A2A tools |
| `a2a_endpoint` | if capabilities ≠ [] | Full URL to A2A handler |
| `health_url` | ❌ | Defaults to `http://<service-hostname>/health` |
| `admin_url` | ❌ | Shown as link in core admin panel |

### Validation behavior in core:

| Manifest state | Core behavior | Agent status |
|---|---|---|
| Valid, all required fields | Full registration | `healthy` |
| Valid but missing optional fields | Partial registration with warnings | `degraded` |
| `name` or `version` missing | Registration blocked | `error` |
| Connection refused / timeout | Not registered (or previous registration kept) | `unavailable` |
| Invalid JSON | Error stored, raw response saved | `error` |

---

## 3. Health Endpoint

**`GET /health`** — REQUIRED, no authentication.

```json
{"status": "ok"}
```

HTTP 200 always (even during degraded state — the agent is responsible for its own health logic).
Core uses this endpoint for liveness polling every 60 seconds.

---

## 4. A2A Endpoint

**`POST /api/v1/a2a`** — REQUIRED if `capabilities` is non-empty.

Standard request envelope:

```json
{
  "tool":       "search_knowledge",
  "input":      { "query": "..." },
  "trace_id":   "uuid",
  "request_id": "uuid"
}
```

Standard response envelope:

```json
{
  "status":  "completed | failed | needs_clarification",
  "output":  { ... },
  "error":   "string or null"
}
```

Rules:
- MUST return HTTP 200 even for business-level errors (use `status: "failed"`)
- MUST return HTTP 400/422 for malformed request envelopes
- MUST NOT return unstructured plain text as the primary response
- MUST handle unknown `tool` values with `status: "failed"` + descriptive `error`
- MUST be idempotent for the same `request_id`

---

## 5. Convention Verification in Core

Core includes `AgentConventionVerifier` which checks all registered agents on demand and
on every discovery cycle. It reports violations per-agent:

```
VIOLATION [knowledge-agent]: a2a_endpoint missing but capabilities declared
VIOLATION [news-maker-agent]: version "1.0" does not match semver pattern
```

Violations are stored in the agent registry row and shown in the admin panel.
Admins can click a badge to view the full violation list.

---

## 6. Adding a New Agent — Checklist

1. Add service to `compose.yaml` with name ending `-agent` and label `ai.platform.agent=true`
2. Implement `GET /api/v1/manifest` returning valid JSON
3. Implement `GET /health` returning `{"status": "ok"}`
4. If capabilities declared: implement `POST /api/v1/a2a`
5. Run `make conventions-test` — all checks must pass
6. Core auto-discovers on next discovery cycle (up to 60s) or via "Run Discovery" in admin panel

No manual registration required. No code changes in core needed.
