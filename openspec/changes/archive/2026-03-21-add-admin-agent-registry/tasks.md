# Tasks: add-admin-agent-registry

## 0. Database

- [x] 0.1 Create Doctrine migration: `agent_registry` table (id, name, version, manifest JSONB, config JSONB, enabled, registered_at, updated_at, enabled_at, disabled_at, enabled_by, health_status)
- [x] 0.2 Create Doctrine migration: `agent_registry_audit` table (id, agent_name, action, actor, payload JSONB, created_at)
- [ ] 0.3 Extend `agent_registry` with lifecycle columns: `manifest_hash`, `last_processed_manifest_hash`, `storage_sync_required`, `last_migration_at`, `last_migration_status`, `last_migration_error`
- [ ] 0.3.1 Add `lifecycle_state` and `lifecycle_retry_count` columns
- [ ] 0.4 Backfill existing rows: compute and store `manifest_hash`; set `storage_sync_required=true` where `last_processed_manifest_hash` is null
- [ ] 0.5 Add DB constraints/indexes for new lifecycle fields (`name + manifest_hash`, `storage_sync_required`, `last_migration_status`)
- [ ] 0.6 Create `agent_lifecycle_runs` table for step-level run logs (`run_id`, `agent_name`, `manifest_hash`, `step`, `status`, `actor`, timings, `error`)

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
- [ ] 3.8 Compute deterministic `manifest_hash` on registration (normalized JSON + `sha256`)
- [ ] 3.9 On registration set `storage_sync_required=true` when `manifest_hash` changed or `last_processed_manifest_hash` is missing
- [ ] 3.10 Keep `enabled` state unchanged on re-registration, but never auto-clear `storage_sync_required`
- [ ] 3.11 Add canonicalization policy test fixtures to ensure stable hash across key-order/whitespace variants

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
- [ ] 8.4 Add optional manifest migration contract (`migrations.run_command`, `migrations.run_on_startup`) for knowledge-agent

## 9. Provisioning and Migration Orchestration

- [ ] 9.1 Add unified migration runner entrypoint in repo root: `scripts/agent-migrate.sh <service>` (stack-specific dispatch: Symfony/Doctrine, Python/Alembic, etc.)
- [ ] 9.2 Add root Make target wrapper: `make agent-migrate SERVICE=<service>`
- [ ] 9.3 Update enable endpoint flow: always run provisioning (if required) + migration runner before setting `enabled=true`
- [ ] 9.4 If migration fails during enable: keep agent disabled, persist `last_migration_status=failed` + error payload, return actionable API error
- [ ] 9.5 On successful enable migration: persist `last_processed_manifest_hash=manifest_hash`, `storage_sync_required=false`, `last_migration_status=success`
- [ ] 9.6 Enforce single migration owner policy: agent startup must not execute DDL migrations (documented + tested)
- [ ] 9.7 Add per-agent distributed lock (Postgres advisory lock) for enable/reconcile flows
- [ ] 9.8 Persist step-level lifecycle run logs to `agent_lifecycle_runs` for every enable/reconcile attempt
- [ ] 9.9 Implement retry policy (exponential backoff + max attempts) for provisioning and migration failures
- [ ] 9.10 Implement `agent:lifecycle:reconcile` scheduled command to retry failed/pending states idempotently
- [ ] 9.11 Add lifecycle dry-run endpoint (`POST /api/v1/internal/agents/{name}/enable:dry-run`) returning planned steps and current blockers
- [ ] 9.12 Add race-condition tests: concurrent enable calls, interrupted run recovery, repeated enable with unchanged manifest hash

## 10. Admin Transparency

- [ ] 10.1 Show `lifecycle_state`, `storage_sync_required`, and last migration status in `/admin/agents`
- [ ] 10.2 Add lifecycle timeline widget fed from `agent_lifecycle_runs` (step, status, duration, error)
- [ ] 10.3 Add UI action for dry-run preview before enable
- [ ] 10.4 Show actionable retry hint for `failed_provisioning` / `failed_migration`

## 11. Reliability Observability

- [ ] 11.1 Add metrics/counters: lifecycle runs total, failures by step, retries by agent
- [ ] 11.2 Add alerting rule for agents stuck in `failed_*` or `storage_sync_required=true` over threshold
- [ ] 11.3 Add runbook section for lifecycle recovery and lock contention handling

## 12. Quality

- [x] 12.1 Run `phpstan analyse` — zero errors at level 8
- [x] 12.2 Run `php-cs-fixer check` — no violations
- [x] 12.3 Run `codecept run` — all suites pass (41 tests, 96 assertions)
- [ ] 12.4 Add lifecycle contract tests in CI (`first enable`, `manifest changed`, `migration fail`, `reconcile recovery`)
- [ ] 12.5 Run `make e2e` — Playwright passes
