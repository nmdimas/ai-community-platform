## ADDED Requirements

### Requirement: Admin Agent List Page
The admin panel SHALL provide a `/admin/agents` page listing all registered agents with their status, version, health, and quick actions.

#### Scenario: All registered agents listed
- **WHEN** an authenticated admin navigates to `/admin/agents`
- **THEN** the page displays a table with one row per registered agent showing: name, version, description, enabled status badge, health status badge, and last updated timestamp

#### Scenario: No agents registered
- **WHEN** no agents have been registered yet
- **THEN** the page displays an empty state message explaining how agents register themselves on boot

---

### Requirement: Agent Enable / Disable Toggle
The admin agent list SHALL include a toggle control per agent that enables or disables it directly from the list.

#### Scenario: Admin enables agent from list
- **WHEN** admin clicks the enable toggle for a disabled agent and confirms
- **THEN** the platform first runs provisioning/migrations; after success the toggle updates to "Увімкнено", a success notification appears, and the agent begins receiving events

#### Scenario: Admin disables agent from list
- **WHEN** admin clicks the disable toggle for an enabled agent and confirms
- **THEN** the toggle updates to "Вимкнено" and the agent stops receiving events immediately

#### Scenario: Toggle requires confirmation
- **WHEN** admin clicks a toggle
- **THEN** a confirmation dialog appears before the state change is applied, preventing accidental toggling

#### Scenario: Enable action shows migration failure
- **WHEN** provisioning or migration fails during enable
- **THEN** the UI keeps the agent in disabled state and shows the actionable error returned by the platform

---

### Requirement: Migration and Provisioning Status Visibility
The admin panel SHALL surface migration/provisioning lifecycle state per agent so operators can understand why an agent cannot be enabled yet.

#### Scenario: Agent pending processing is visible
- **WHEN** `storage_sync_required = true`
- **THEN** the row shows a "Pending sync" indicator with tooltip explaining that provisioning/migrations are required

#### Scenario: Last migration failure visible
- **WHEN** `last_migration_status = failed`
- **THEN** the row shows failure badge/details and provides a retry path via the enable action

---

### Requirement: Lifecycle Step Timeline
The admin panel SHALL show a step timeline for the latest lifecycle run per agent.

#### Scenario: Timeline displays successful sequence
- **WHEN** an enable/reconcile run succeeds
- **THEN** admin sees ordered steps (`lock`, `provision`, `migrate`, `finalize`) with statuses and durations

#### Scenario: Timeline highlights failed step
- **WHEN** lifecycle run fails
- **THEN** admin sees the failed step with error message and retry guidance

---

### Requirement: Enable Dry-Run Preview
The admin panel SHALL support a dry-run preview action before actual enable.

#### Scenario: Dry-run shows planned steps
- **WHEN** admin clicks dry-run for an agent
- **THEN** UI displays whether provisioning is needed, migration command to run, expected skips, and current blockers

#### Scenario: Lock contention shown as retryable state
- **WHEN** dry-run or enable detects lifecycle lock held by another run
- **THEN** UI shows a non-fatal "operation in progress" message with retry instruction

---

### Requirement: Agent Manifest Inspection
The admin panel SHALL allow admins to expand any agent row to view its full manifest details.

#### Scenario: Admin expands agent row
- **WHEN** admin clicks the expand control on an agent row
- **THEN** the panel displays: commands, events, permissions, capabilities, `a2a_endpoint`, and `health_url` (formatted, not raw JSON)

#### Scenario: Config schema shown as readable fields
- **WHEN** the expanded view is shown and the agent has a `config_schema`
- **THEN** each schema property is shown with its type, description, current value, and default value

---

### Requirement: Agent Config Editor
The admin panel SHALL provide an inline config editor for agents that declare a `config_schema`, allowing admins to update runtime config values.

#### Scenario: Admin saves updated config
- **WHEN** admin changes a config value and clicks "Зберегти конфіг"
- **THEN** the new value is persisted to `agent_registry.config` and the Redis cache is invalidated

#### Scenario: Config validation enforced
- **WHEN** admin enters a value that violates the config schema (e.g., string instead of integer)
- **THEN** the save button is disabled and an inline validation message is shown

---

### Requirement: Agent Settings Deep Link
The admin panel SHALL link to an agent's own settings page when the agent manifest declares an `admin_url`.

#### Scenario: Settings link visible
- **WHEN** an agent row has a non-null `admin_url` in its manifest
- **THEN** the row shows a "Налаштування" button linking to that URL (e.g., `/admin/knowledge`)

#### Scenario: No settings link for agents without admin_url
- **WHEN** an agent manifest does not include `admin_url`
- **THEN** no settings button is shown; the expand view is the only admin interface
