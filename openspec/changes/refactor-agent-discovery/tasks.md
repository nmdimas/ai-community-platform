# Tasks: refactor-agent-discovery

## 0. Preparation

- [ ] 0.1 Read `design.md` — understand Traefik API format, state machine, and naming convention
- [ ] 0.2 Read `docs/agent-requirements/conventions.md` — understand the full agent contract
- [ ] 0.3 Verify Traefik API is accessible: `curl http://traefik:8080/api/http/services` from core container

## 1. Core: Agent Discovery Infrastructure

- [ ] 1.1 Create `AgentDiscoveryService` — queries Traefik API, filters `*-agent@docker` services, returns list of internal hostnames
- [ ] 1.2 Create `AgentManifestFetcher` — fetches `http://{hostname}/api/v1/manifest` with 5s timeout, returns raw JSON + HTTP status
- [ ] 1.3 Create `AgentConventionVerifier` — validates manifest against JSON Schema (`config/agent-manifest-schema.json`), returns `ConventionResult` with violations list
- [ ] 1.4 Create `config/agent-manifest-schema.json` — JSON Schema defining required/optional fields and formats
- [ ] 1.5 Create `AgentDiscoveryCommand` (`agent:discovery`) — orchestrates discovery loop: fetch services → pull manifests → verify → upsert registry
- [ ] 1.6 Register `AgentDiscoveryCommand` in `services.yaml` with `AgentDiscoveryService`, `AgentManifestFetcher`, `AgentConventionVerifier` injected
- [ ] 1.7 Add `agent:discovery` to Symfony Scheduler (60s interval) — update `config/packages/scheduler.yaml`

## 2. Core: Agent State Machine

- [ ] 2.1 Add `violations` (JSON) column to `agent_registry` table — new migration `Version20260305000001.php`
- [ ] 2.2 Update `AgentRegistryInterface` + `DoctrineAgentRegistry` to support `status` values: `healthy | degraded | unavailable | error`
- [ ] 2.3 Update `AgentHealthPollerCommand` — use new state machine transitions (`unavailable` after 3 failed health checks)
- [ ] 2.4 Write unit tests for `AgentConventionVerifier` covering: valid manifest, missing name, missing version, missing a2a_endpoint with capabilities, invalid JSON

## 3. Core: Admin UI Updates

- [ ] 3.1 Update `agents.html.twig` — replace current status column with 4-state badge: `healthy` (green), `degraded` (amber), `unavailable` (grey), `error` (red)
- [ ] 3.2 Add violation detail modal — click on `degraded` or `error` badge → modal shows formatted violation list from `agent_registry.violations`
- [ ] 3.3 Add "Run Discovery" button → `POST /admin/agents/discover` → triggers `agent:discovery` command synchronously → redirects with flash message
- [ ] 3.4 Create `AgentRunDiscoveryController` for the above action
- [ ] 3.5 Add "Add by URL" button → opens modal with "Функціонал в розробці" message and brief explanation of manual compose.yaml approach

## 4. Cleanup: Remove Push Model

- [ ] 4.1 Delete `apps/knowledge-agent/src/Command/KnowledgeRegisterCommand.php`
- [ ] 4.2 Remove `KnowledgeRegisterCommand` wiring from `apps/knowledge-agent/config/services.yaml`
- [ ] 4.3 Remove `knowledge-register` target from root `Makefile`
- [ ] 4.4 Add `ai.platform.agent=true` Docker label to `knowledge-agent` service in `compose.yaml`
- [ ] 4.5 Add `ai.platform.agent=true` Docker label to `news-maker-agent` service in `compose.yaml`

## 5. Convention Test Suite

- [ ] 5.1 Create `tests/agent-conventions/package.json` — deps: `codeceptjs`, `@codeceptjs/playwright`, `playwright`
- [ ] 5.2 Create `tests/agent-conventions/codecept.conf.ts` — configure REST + Playwright helpers; read agent URLs from `AGENT_URLS` env or discover from Traefik API
- [ ] 5.3 Create `tests/agent-conventions/support/manifest-schema.json` — JSON Schema mirroring `config/agent-manifest-schema.json`
- [ ] 5.4 Implement `tests/agent-conventions/tests/manifest_test.ts` — TC-01-01 through TC-01-08
- [ ] 5.5 Implement `tests/agent-conventions/tests/health_test.ts` — TC-02-01 through TC-02-04
- [ ] 5.6 Implement `tests/agent-conventions/tests/a2a_test.ts` — TC-03-01 through TC-03-06 (skipped if no capabilities)
- [ ] 5.7 Add `conventions-test` target to root `Makefile`: `cd tests/agent-conventions && npm ci && npx codeceptjs run --steps`
- [ ] 5.8 Run `make conventions-test` — all existing agents (knowledge-agent, news-maker-agent) must pass

## 6. Quality Checks

- [ ] 6.1 `make analyse` (core) — PHPStan level 8, zero errors
- [ ] 6.2 `make cs-check` (core) — no CS violations
- [ ] 6.3 `make test` (core) — all Codeception suites pass
- [ ] 6.4 `make knowledge-analyse` — PHPStan level 8 (after KnowledgeRegisterCommand deleted)
- [ ] 6.5 `make knowledge-cs-check` — no CS violations
- [ ] 6.6 `make knowledge-test` — all Codeception suites pass
- [ ] 6.7 `make conventions-test` — all TC-01, TC-02, TC-03 pass for knowledge-agent and news-maker-agent

## 7. Documentation

- [ ] 7.1 `docs/agent-requirements/conventions.md` — already created in this proposal; verify accuracy against implementation
- [ ] 7.2 `docs/agent-requirements/test-cases.md` — already created; verify TC IDs match implementation
- [ ] 7.3 Update `LOCAL_DEV.md` — add section "Adding a new agent" with checklist reference to conventions.md
- [ ] 7.4 Update `AGENTS.md` (repo root) — add pointer to `docs/agent-requirements/conventions.md`
