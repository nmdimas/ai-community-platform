## ADDED Requirements

### Requirement: Prompt and Guardrail Management
The system SHALL provide admin controls for editing prompts and guardrails for each AI processing stage.

#### Scenario: Operator updates ranker prompt
- **WHEN** an admin saves a new prompt for the ranking stage
- **THEN** subsequent ranking runs use the updated prompt

#### Scenario: Guardrail is always applied
- **WHEN** an AI stage is executed
- **THEN** the configured guardrail for that stage is appended or enforced in addition to the editable base prompt

### Requirement: Scheduler Configuration
The system SHALL provide admin controls for crawl cadence and raw-news cleanup cadence.

#### Scenario: Crawl schedule changed
- **WHEN** an admin updates the crawl cadence
- **THEN** future crawl runs follow the new schedule without requiring code changes

#### Scenario: Cleanup schedule changed
- **WHEN** an admin updates the cleanup cadence
- **THEN** future raw-news cleanup runs follow the new schedule without requiring code changes

### Requirement: Retention Configuration
The system SHALL provide admin controls for temporary raw-news retention time.

#### Scenario: Retention window updated
- **WHEN** an admin changes the raw-news TTL
- **THEN** newly stored raw items receive `expires_at` values based on the updated retention setting

#### Scenario: Existing non-expired items remain valid
- **WHEN** the retention setting changes
- **THEN** existing raw items remain eligible until their recalculated or already assigned expiry policy is applied by the system design

### Requirement: Proxy Configuration
The system SHALL provide admin controls for proxy settings, with proxy usage disabled by default.

#### Scenario: Default proxy state is disabled
- **WHEN** the admin opens proxy settings on a fresh setup
- **THEN** the proxy enable toggle is off

#### Scenario: Proxy credentials saved
- **WHEN** an admin enables proxy usage and saves valid proxy configuration
- **THEN** the crawler can use that proxy configuration for subsequent requests

### Requirement: Resource Inventory
The system SHALL expose an admin view of available operational resources for the news-maker workflow.

#### Scenario: Admin views resource inventory
- **WHEN** an admin opens the resource inventory page
- **THEN** the UI shows configured sources, selected models, active crawler adapter, and scheduler health

#### Scenario: Manual crawl available
- **WHEN** an admin needs to force processing outside the schedule
- **THEN** the UI provides a manual action to trigger a crawl run immediately
