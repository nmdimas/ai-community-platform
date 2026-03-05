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

### Requirement: Agent Enable / Disable Lifecycle
The platform registry SHALL support enabling and disabling individual agents; only enabled agents receive platform events and have their commands routed.

#### Scenario: Admin enables agent
- **WHEN** an admin calls `POST /api/v1/internal/agents/{name}/enable`
- **THEN** the registry sets `enabled = true`, `enabled_at = now()`, `enabled_by = admin_user`, and invalidates the Redis registry cache

#### Scenario: Admin disables agent
- **WHEN** an admin calls `POST /api/v1/internal/agents/{name}/disable`
- **THEN** the registry sets `enabled = false`, `disabled_at = now()`, and invalidates the Redis registry cache

#### Scenario: Disabled agent receives no events
- **WHEN** a `message.created` event is dispatched and an agent is disabled
- **THEN** the Event Bus does not deliver the event to that agent

#### Scenario: Disabled agent commands not routed
- **WHEN** a user issues a command belonging to a disabled agent
- **THEN** the Command Router returns a graceful "Команда недоступна" message and does not call the agent

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
