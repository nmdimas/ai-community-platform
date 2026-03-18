## 1. Database Migrations

- [ ] 1.1 Create migration `Version20260314000001.php`:
  - ADD column `delivery_target` JSONB DEFAULT NULL to `scheduled_jobs`
- [ ] 1.2 Create migration `Version20260314000002.php`:
  - ADD column `delivery_status` VARCHAR(32) DEFAULT NULL to `scheduler_job_logs`
  - ADD column `delivery_error` TEXT DEFAULT NULL to `scheduler_job_logs`
  - ADD column `delivery_channel_id` UUID DEFAULT NULL to `scheduler_job_logs`

## 2. Repository Changes

- [ ] 2.1 Modify `ScheduledJobRepository`:
  - Include `delivery_target` in `registerJob()` INSERT and ON CONFLICT UPDATE
  - Include `delivery_target` in `findDueJobs()` SELECT
  - Include `delivery_target` in `findAll()` and `findById()`
  - Add `updateDeliveryTarget(string $id, ?array $target): void`
- [ ] 2.2 Modify `SchedulerJobLogRepository`:
  - Add `updateDeliveryStatus(string $logId, string $status, ?string $error, ?string $channelId): void`
  - Include delivery columns in `findByJob()` SELECT

## 3. Scheduler Service Integration

- [ ] 3.1 Modify `SchedulerService::tick()` — Phase 3 (process results):
  - After successful job execution (`status = completed`), check if `delivery_target` is set
  - If set: build `DeliveryTarget` and `DeliveryPayload` from agent response
  - Call `DeliveryService::deliver()` and capture `DeliveryResult`
  - Call `jobLog->updateDeliveryStatus()` with result
  - If delivery fails: log warning but do NOT trigger job retry
- [ ] 3.2 Modify `SchedulerService::tick()` — idempotency key format:
  - Key: `sched_{job_id}_{log_id}` — unique per job execution
- [ ] 3.3 Modify `SchedulerService::tick()` — failed job with delivery target:
  - Set `delivery_status = "skipped"` in job log
- [ ] 3.4 Modify `SchedulerService::registerFromManifest()`:
  - Parse `delivery_target` from manifest `scheduled_jobs[].delivery_target`
  - Pass to `repository->registerJob()`
- [ ] 3.5 Add `DeliveryServiceInterface` as optional constructor dependency to `SchedulerService`:
  - If null (not wired): skip delivery silently (backward compatible)

## 4. Admin UI Changes

- [ ] 4.1 Modify scheduler create/edit modal in `index.html.twig`:
  - Add "Канал доставки" dropdown — populated from `delivery_channels` (fetched via AJAX or passed to template)
  - Add "Адреса" text input — chat_id / channel name / phone number
  - Pre-fill from existing `delivery_target` on edit
  - Clear both fields = remove delivery target
- [ ] 4.2 Modify `SchedulerController` — pass `deliveryChannels` to template for dropdown
- [ ] 4.3 Modify `SchedulerController` — handle `delivery_target` in create/update POST
- [ ] 4.4 Modify `logs.html.twig` — add "Доставка" column:
  - Badge colors: `delivered` (green), `failed` (red), `skipped` (grey), `rate_limited` (yellow)
  - Show `delivery_error` in tooltip on failed
- [ ] 4.5 Modify job table in `index.html.twig` — show delivery channel name in "Доставка" column (or "—" if none)

## 5. Tests

- [ ] 5.1 Unit test: `SchedulerServiceTest::testTickDeliversOnSuccess` — mock DeliveryService, verify `deliver()` called with correct target and payload after successful job
- [ ] 5.2 Unit test: `SchedulerServiceTest::testTickSkipsDeliveryOnFailedJob` — verify delivery NOT called when agent returns failed
- [ ] 5.3 Unit test: `SchedulerServiceTest::testTickSkipsDeliveryWhenNoTarget` — verify no delivery when `delivery_target` is null
- [ ] 5.4 Unit test: `SchedulerServiceTest::testTickLogsDeliveryFailure` — verify `delivery_status = "failed"` logged when DeliveryService returns failed
- [ ] 5.5 Unit test: `SchedulerServiceTest::testTickHandlesNoDeliveryService` — verify graceful skip when DeliveryService is null
- [ ] 5.6 Functional test: `ScheduledJobRepositoryTest` — verify `delivery_target` JSONB round-trip (insert, read, update to null)

## 6. Documentation

- [ ] 6.1 Update `docs/scheduler.md` — add "Доставка результатів" section: how delivery_target works, manifest format, admin UI, idempotency key format
- [ ] 6.2 Update `docs/delivery-channels.md` — add "Scheduler Integration" section with cross-reference

## 7. Quality Checks

- [ ] 7.1 Run `phpstan analyse` — 0 errors at level 8
- [ ] 7.2 Run `php-cs-fixer check` — no style violations
- [ ] 7.3 Run `codecept run` — all tests pass
