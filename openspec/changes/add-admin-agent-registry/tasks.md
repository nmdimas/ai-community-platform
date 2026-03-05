# Tasks: add-admin-agent-registry

## 0. Database

- [x] 0.1 Create Doctrine migration: `agent_registry` table (id, name, version, manifest JSONB, config JSONB, enabled, registered_at, updated_at, enabled_at, disabled_at, enabled_by, health_status)
- [x] 0.2 Create Doctrine migration: `agent_registry_audit` table (id, agent_name, action, actor, payload JSONB, created_at)

## 1. Agent Manifest Schema

- [x] 1.1 Author JSON Schema file `config/agent-manifest.schema.json` defining all mandatory and optional manifest fields
- [x] 1.2 Create `ManifestValidator` service: validates incoming manifest JSON against the schema
- [x] 1.3 Write unit tests for `ManifestValidator`: valid manifest, missing required fields, invalid config_schema type

## 2. Agent Registry Service

- [x] 2.1 Create `AgentRegistryRepository`: `register()`, `enable()`, `disable()`, `findAll()`, `findEnabled()`, `updateConfig()`, `updateHealthStatus()`
- [x] 2.2 Implement Redis cache layer in `AgentRegistryRepository`: `findEnabled()` reads from Redis with 10s TTL; `enable()`/`disable()`/`updateConfig()` invalidate cache
- [x] 2.3 Create `AgentRegistryAuditLogger` service: records enable/disable and config change events to `agent_registry_audit`
- [x] 2.4 Write unit tests for registry service (Postgres operations, Redis cache hit/miss, audit log entries)

## 3. Registration API (Internal)

- [x] 3.1 Create `POST /api/v1/internal/agents/register` controller: validate manifest, upsert registry row, return `200 OK` or `422`
- [x] 3.2 Add internal API auth guard (shared secret header `X-Platform-Internal-Token`)
- [x] 3.3 Create `POST /api/v1/internal/agents/{name}/enable` controller (admin auth)
- [x] 3.4 Create `POST /api/v1/internal/agents/{name}/disable` controller (admin auth)
- [x] 3.5 Create `GET /api/v1/internal/agents` controller: return full registry list with enabled state and health (admin auth)
- [x] 3.6 Create `PUT /api/v1/internal/agents/{name}/config` controller: validate against config_schema, persist, invalidate cache (admin auth)
- [x] 3.7 Write functional tests for all registry API endpoints

## 4. Event Bus Integration

- [x] 4.1 Update `EventBus::dispatch()` to load enabled agents from `AgentRegistryRepository::findEnabled()` before routing
- [x] 4.2 Filter dispatched events by agent's declared `events` list
- [x] 4.3 Write unit tests: disabled agent receives no events; enabled agent receives matching events only

## 5. Command Router Integration

- [x] 5.1 Update `CommandRouter::resolve()` to look up commands in enabled agents' manifests
- [x] 5.2 Return graceful "Команда недоступна" response when command belongs to a disabled or unregistered agent
- [x] 5.3 Write unit tests: command routing to enabled agent; command of disabled agent blocked

## 6. Health Poller

- [x] 6.1 Create `AgentHealthPoller` Symfony console command (scheduled, runs every 60s via `schedule.tick`): polls each registered agent's `health_url`
- [x] 6.2 Update `health_status` on success or 2 consecutive failures
- [x] 6.3 Write unit tests for health poller state transitions

## 7. Admin UI

- [x] 7.1 Create `/admin/agents` route and Twig template: table with name, version, description, enabled badge, health badge, updated_at
- [x] 7.2 Implement enable/disable toggle with confirmation dialog (AJAX call to internal API)
- [ ] 7.3 Implement expand row: display manifest fields (commands, events, permissions, capabilities, endpoints)
- [ ] 7.4 Implement config editor: render form fields from `config_schema`, validate client-side, POST to config API
- [ ] 7.5 Implement "Налаштування" deep link button from `admin_url`
- [ ] 7.6 Write E2E tests (Playwright): list loads, toggle enable/disable, config save, deep link navigation

## 8. knowledge-base Agent Manifest

- [ ] 8.1 Author `apps/knowledge-agent/manifest.json` with all required and optional fields for the knowledge-base agent
- [ ] 8.2 Add registration call in `apps/knowledge-agent/` Symfony boot sequence (Kernel event listener: `POST_BOOT`)
- [ ] 8.3 Write integration test: knowledge-base agent registers successfully on boot

## 9. Quality

- [x] 9.1 Run `phpstan analyse` — zero errors at level 8
- [x] 9.2 Run `php-cs-fixer check` — no violations
- [x] 9.3 Run `codecept run` — all suites pass (41 tests, 96 assertions)
- [ ] 9.4 Run `make e2e` — Playwright passes
