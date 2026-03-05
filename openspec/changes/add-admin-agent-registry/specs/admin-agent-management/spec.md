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
- **THEN** the toggle updates to "Увімкнено", a success notification appears, and the agent begins receiving events

#### Scenario: Admin disables agent from list
- **WHEN** admin clicks the disable toggle for an enabled agent and confirms
- **THEN** the toggle updates to "Вимкнено" and the agent stops receiving events immediately

#### Scenario: Toggle requires confirmation
- **WHEN** admin clicks a toggle
- **THEN** a confirmation dialog appears before the state change is applied, preventing accidental toggling

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
