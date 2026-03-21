## ADDED Requirements

### Requirement: Scheduled Job Delivery Target
Each scheduled job SHALL optionally have a `delivery_target` configuration that specifies where to deliver the agent's response after successful execution. The target SHALL reference a delivery channel and a transport-specific address.

#### Scenario: Job with delivery target completes successfully
- **WHEN** a scheduled job with `delivery_target = { "channel_id": "ch-1", "address": "-100123" }` executes and the agent returns a successful response
- **THEN** the scheduler SHALL call `DeliveryService::deliver()` with the agent's response body as content
- **AND** the delivery target's `channel_id` and `address` SHALL be used to resolve the channel and recipient
- **AND** the `idempotency_key` SHALL be `sched_{job_id}_{log_id}` to prevent duplicate delivery on retry

#### Scenario: Job with delivery target fails
- **WHEN** a scheduled job with `delivery_target` executes but the agent returns a failed response
- **THEN** the scheduler SHALL NOT attempt delivery
- **AND** the job log SHALL record `delivery_status = "skipped"` with reason `agent_failed`

#### Scenario: Job without delivery target
- **WHEN** a scheduled job without `delivery_target` completes (success or failure)
- **THEN** the scheduler SHALL NOT invoke `DeliveryService`
- **AND** behavior SHALL be identical to current scheduler (log only)

### Requirement: Delivery Status in Job Logs
The `scheduler_job_logs` table SHALL include delivery status columns to track whether the agent's output was delivered after execution.

#### Scenario: Delivery succeeds
- **WHEN** a job with delivery target completes and `DeliveryService` returns `delivered`
- **THEN** the job log entry SHALL have `delivery_status = "delivered"` and `delivery_channel_id` set

#### Scenario: Delivery fails
- **WHEN** a job with delivery target completes but `DeliveryService` returns `failed`
- **THEN** the job log entry SHALL have `delivery_status = "failed"` and `delivery_error` set
- **AND** the job itself SHALL still be marked as `completed` (delivery failure does not trigger job retry)

#### Scenario: Delivery rate limited
- **WHEN** `DeliveryService` returns `rate_limited` for a job's delivery
- **THEN** the job log SHALL have `delivery_status = "rate_limited"`
- **AND** the scheduler SHALL NOT retry delivery (rate limit is transient, next job run will try again)

### Requirement: Delivery Target in Agent Manifest
Agent manifests SHALL support an optional `delivery_target` field within `scheduled_jobs[]` entries to declare a default delivery channel and address.

#### Scenario: Agent installed with delivery target in manifest
- **WHEN** an agent is installed with a manifest containing `scheduled_jobs[0].delivery_target = { "channel_id": "openclaw-tg", "address": "-100123" }`
- **THEN** `SchedulerService::registerFromManifest()` SHALL persist the delivery target in the `scheduled_jobs.delivery_target` JSONB column

#### Scenario: Agent installed without delivery target
- **WHEN** an agent is installed with `scheduled_jobs` entries that have no `delivery_target` field
- **THEN** the `delivery_target` column SHALL be NULL
- **AND** the job SHALL execute without delivery (log only)

### Requirement: Delivery Target Admin Management
The scheduler admin UI SHALL allow operators to configure, modify, or remove delivery targets on any scheduled job.

#### Scenario: Admin adds delivery target to existing job
- **WHEN** an admin opens the edit modal for a scheduled job and selects a delivery channel + enters an address
- **THEN** the `delivery_target` JSONB column SHALL be updated with the selected channel and address

#### Scenario: Admin removes delivery target from job
- **WHEN** an admin clears the delivery target fields in the edit modal
- **THEN** the `delivery_target` column SHALL be set to NULL
- **AND** subsequent runs of this job SHALL not attempt delivery

#### Scenario: Admin views delivery status in logs
- **WHEN** an admin opens `/admin/scheduler/{id}/logs` for a job that has delivery configured
- **THEN** the log table SHALL show a "Доставка" column with status badges (delivered/failed/skipped/rate_limited)
