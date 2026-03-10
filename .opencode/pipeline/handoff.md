# Pipeline Handoff

- **Task**: Implement openspec change add-central-scheduler
- **Started**: 2026-03-11 00:43:57
- **Branch**: pipeline/implement-openspec-change-add-central-scheduler
- **Pipeline ID**: 20260311_004355

---

## Architect

- **Status**: pending
- **Change ID**: —
- **Apps affected**: —
- **DB changes**: —
- **API changes**: —

## Coder

- **Status**: done
- **Files modified**:
  - `apps/core/composer.json` — added `dragonmantank/cron-expression: ^3.6`
  - `apps/core/composer.lock` — updated
  - `apps/core/migrations/Version20260310000001.php` — new: creates `scheduled_jobs` table
  - `apps/core/src/Scheduler/ScheduledJobRepositoryInterface.php` — new: interface
  - `apps/core/src/Scheduler/ScheduledJobRepository.php` — new: DBAL repository
  - `apps/core/src/Scheduler/CronExpressionHelperInterface.php` — new: interface
  - `apps/core/src/Scheduler/CronExpressionHelper.php` — new: cron expression wrapper
  - `apps/core/src/Scheduler/SchedulerService.php` — new: orchestration service
  - `apps/core/src/A2AGateway/A2AClientInterface.php` — new: interface for A2AClient (needed for testability)
  - `apps/core/src/A2AGateway/A2AClient.php` — modified: implements A2AClientInterface
  - `apps/core/src/Command/SchedulerRunCommand.php` — new: `scheduler:run` command
  - `apps/core/src/Controller/Admin/SchedulerController.php` — new: admin scheduler page
  - `apps/core/src/Controller/Api/Internal/SchedulerRunNowController.php` — new: POST /api/v1/internal/scheduler/{id}/run
  - `apps/core/src/Controller/Api/Internal/SchedulerToggleController.php` — new: POST /api/v1/internal/scheduler/{id}/toggle
  - `apps/core/src/Controller/Api/Internal/AgentInstallController.php` — modified: registers scheduled jobs on install
  - `apps/core/src/Controller/Api/Internal/AgentDeleteController.php` — modified: removes scheduled jobs on uninstall
  - `apps/core/src/Controller/Api/Internal/AgentEnableController.php` — modified: enables scheduled jobs on agent enable
  - `apps/core/src/Controller/Api/Internal/AgentDisableController.php` — modified: disables scheduled jobs on agent disable
  - `apps/core/templates/admin/layout.html.twig` — modified: added Scheduler nav link
  - `apps/core/templates/admin/scheduler/index.html.twig` — new: scheduler admin page template
  - `compose.core.yaml` — modified: added `core-scheduler` service
  - `apps/core/tests/Unit/Scheduler/CronExpressionHelperTest.php` — new: unit tests
  - `apps/core/tests/Unit/Scheduler/SchedulerServiceTest.php` — new: unit tests
  - `apps/core/tests/Functional/Scheduler/ScheduledJobRepositoryCest.php` — new: functional tests
  - `apps/core/tests/Functional/Scheduler/AgentInstallSchedulerCest.php` — new: functional tests
  - `docs/scheduler.md` — new: developer documentation
  - `docs/agent-requirements/storage-provisioning.md` — modified: added scheduled_jobs section
- **Migrations created**: `apps/core/migrations/Version20260310000001.php` — run successfully
- **Deviations**:
  - Added `ScheduledJobRepositoryInterface`, `CronExpressionHelperInterface`, and `A2AClientInterface` to enable unit testing (all three classes are `final`). This is a minor addition not in the spec but required for testability.
  - `enableByAgent` in `SchedulerService` uses `findByAgent()` (added to interface) instead of `findAll()` for efficiency.
  - `updateAfterRun` SQL was split into two branches (null/non-null `nextRunAt`) to avoid PostgreSQL type inference error with `COALESCE(:param::TIMESTAMPTZ, column)`.
  - Test results: 49 functional tests (220 assertions), 172 unit tests (560 assertions) — all pass.
  - PHPStan: 2 pre-existing errors in unrelated files; 0 new errors.
  - CS check: 0 violations.

## Validator

- **Status**: pending
- **PHPStan**: —
- **CS-check**: —
- **Files fixed**: —

## Tester

- **Status**: pending
- **Test results**: —
- **New tests written**: —

## Documenter

- **Status**: pending
- **Docs created/updated**: —

---
