## ADDED Requirements

### Requirement: Agent Self-Registration on Boot
Every agent service SHALL register itself with the platform registry on startup by POSTing its manifest to the platform's internal registration endpoint.

#### Scenario: First registration creates disabled entry
- **WHEN** an agent registers for the first time
- **THEN** the registry creates a new row with `enabled = false` and `health_status = unknown`

#### Scenario: Re-registration updates manifest without changing enabled state
- **WHEN** an already-registered agent re-registers (e.g., after a redeploy)
- **THEN** the registry updates `version`, `manifest`, and `updated_at`; `enabled` and `config` are NOT changed

#### Scenario: Registration retried on failure
- **WHEN** the platform is unreachable at agent boot
- **THEN** the agent retries registration with exponential backoff (max 5 attempts, cap 60s) before marking itself degraded

---

### Requirement: Manifest Processing State Tracking
The registry SHALL track both the latest accepted manifest hash and the latest successfully processed manifest hash.

#### Scenario: First registration requires processing
- **WHEN** an agent is registered for the first time
- **THEN** `manifest_hash` is stored, `last_processed_manifest_hash` is null, and `storage_sync_required` is set to true

#### Scenario: Changed manifest requires re-processing
- **WHEN** an existing agent registers with a different `manifest_hash`
- **THEN** `storage_sync_required` is set to true until provisioning+migrations succeed for that new hash

#### Scenario: Unchanged manifest does not force re-processing
- **WHEN** an existing agent registers with the same `manifest_hash`
- **THEN** `storage_sync_required` remains unchanged and no additional processing is enqueued automatically

---

### Requirement: Agent Enable / Disable Lifecycle
The platform registry SHALL support enabling and disabling individual agents; only enabled agents receive platform events and have their commands routed.

#### Scenario: Admin enables agent
- **WHEN** an admin calls `POST /api/v1/internal/agents/{name}/enable`
- **THEN** the platform acquires per-agent lifecycle lock, runs provisioning (if required), always executes core-owned migrations, and only after successful completion sets `enabled = true`, `enabled_at = now()`, `enabled_by = admin_user`, and invalidates Redis cache

#### Scenario: Admin disables agent
- **WHEN** an admin calls `POST /api/v1/internal/agents/{name}/disable`
- **THEN** the registry sets `enabled = false`, `disabled_at = now()`, and invalidates the Redis registry cache

#### Scenario: Enable fails on migration error
- **WHEN** migration command fails during enable flow
- **THEN** the registry keeps `enabled = false`, sets lifecycle state to `failed_migration`, records `last_migration_status = failed` with error details, and returns a failure response to admin

#### Scenario: Enable success marks manifest as processed
- **WHEN** provisioning+migrations succeed during enable
- **THEN** the registry sets `last_processed_manifest_hash = manifest_hash`, clears `storage_sync_required`, sets lifecycle state to `enabled`, and records `last_migration_status = success`

#### Scenario: Disabled agent receives no events
- **WHEN** a `message.created` event is dispatched and an agent is disabled
- **THEN** the Event Bus does not deliver the event to that agent

#### Scenario: Disabled agent commands not routed
- **WHEN** a user issues a command belonging to a disabled agent
- **THEN** the Command Router returns a graceful "Команда недоступна" message and does not call the agent

---

### Requirement: Lifecycle State Machine
The registry SHALL expose explicit lifecycle states for each agent and only allow valid transitions.

#### Scenario: Enable flow transitions through processing states
- **WHEN** enable starts for an agent with pending storage sync
- **THEN** lifecycle transitions `registered -> provisioning -> migration_pending -> migrating -> enabled` on success

#### Scenario: Provisioning failure uses dedicated state
- **WHEN** provisioning fails before migration starts
- **THEN** lifecycle state is set to `failed_provisioning` and retry metadata is incremented

#### Scenario: Migration failure uses dedicated state
- **WHEN** migration command fails
- **THEN** lifecycle state is set to `failed_migration` and retry metadata is incremented

---

### Requirement: Core-Owned Migration Execution
DDL migrations for agent services SHALL be executed only by the core lifecycle orchestrator (enable/reconcile flows).

#### Scenario: Agent startup does not perform DDL
- **WHEN** an agent container starts
- **THEN** it may report schema status but MUST NOT execute migration DDL commands autonomously

#### Scenario: Reconcile flow re-runs core migration contract
- **WHEN** reconcile retries a failed or pending lifecycle state
- **THEN** migration runs through the same core-owned runner and lock semantics as manual enable

---

### Requirement: Per-Agent Lifecycle Locking
The platform SHALL serialize lifecycle operations per agent using a distributed lock.

#### Scenario: Concurrent enable requests are serialized
- **WHEN** two enable requests for the same agent arrive concurrently
- **THEN** one flow acquires the lock and the other receives a busy/retryable response without running provisioning or migrations

#### Scenario: Different agents can process in parallel
- **WHEN** enable/reconcile runs target different agent names
- **THEN** each flow can proceed independently because locks are scoped per agent

---

### Requirement: Reconcile Loop for Eventual Consistency
The platform SHALL run a periodic reconcile loop that retries pending/failed lifecycle states with bounded backoff.

#### Scenario: Interrupted run is recovered
- **WHEN** core crashes mid-lifecycle after setting a processing state
- **THEN** the reconcile loop detects the stale state and resumes/retries safely under lock

#### Scenario: Max retries stop infinite loops
- **WHEN** lifecycle retries reach configured maximum attempts
- **THEN** agent remains in failed state and is surfaced for manual operator action

---

### Requirement: Lifecycle Execution Logging
The platform SHALL persist step-level lifecycle execution records for observability and audit.

#### Scenario: Successful run logs all steps
- **WHEN** an enable operation succeeds
- **THEN** `agent_lifecycle_runs` contains entries for lock/provision/migrate/finalize with duration and `status=success`

#### Scenario: Failed run logs actionable error
- **WHEN** provisioning or migration fails
- **THEN** `agent_lifecycle_runs` stores failed step, error details, actor, and manifest hash for debugging

---

### Requirement: Registry State Caching
The platform SHALL cache agent registry state in Redis to avoid database reads on every incoming event.

#### Scenario: Cache populated on first read
- **WHEN** the Event Bus reads registry state for the first time after startup or cache expiry
- **THEN** the platform loads all enabled agents from Postgres and stores them in Redis with 10-second TTL

#### Scenario: Cache invalidated on enable/disable
- **WHEN** an agent's enabled state is changed via the registry API
- **THEN** the platform immediately deletes the Redis cache key so the next read reflects the new state

---

### Requirement: Agent Health Tracking
The platform registry SHALL poll each registered agent's `health_url` and update `health_status` accordingly.

#### Scenario: Healthy agent reflected in registry
- **WHEN** the platform health poller receives `200 OK` from an agent's `health_url`
- **THEN** `agent_registry.health_status` is set to `healthy`

#### Scenario: Unhealthy agent flagged in admin
- **WHEN** two consecutive health polls return non-200 or timeout
- **THEN** `health_status` is set to `unhealthy` and the admin panel shows a warning badge

#### Scenario: Agent without health_url shows unknown
- **WHEN** an agent manifest does not include `health_url`
- **THEN** `health_status` remains `unknown` and no polling is attempted

---

### Requirement: Registry Audit Log
All changes to agent enabled/disabled state and config SHALL be recorded in an audit log.

#### Scenario: Enable/disable action logged
- **WHEN** an admin enables or disables an agent
- **THEN** the action is recorded with `agent_name`, `action` (enabled/disabled), `actor`, `timestamp`, and `previous_state`

#### Scenario: Config change logged
- **WHEN** an admin saves updated config for an agent
- **THEN** the change is recorded with `agent_name`, `config_diff`, `actor`, and `timestamp`
