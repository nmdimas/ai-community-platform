## ADDED Requirements
### Requirement: Tenant Entity and Ownership
The system SHALL support multiple Tenants, each associated with one or more Users.

#### Scenario: User creates a tenant
- **WHEN** a user registers or explicitly creates a new tenant
- **THEN** they are assigned as the owner/admin of that tenant

### Requirement: Tenant Safe Deletion
The system MUST prevent deletion of a Tenant that has active agents or running cron jobs.

#### Scenario: Deleting an active tenant
- **WHEN** an admin attempts to delete a tenant with active agents
- **THEN** the system rejects the deletion and returns an error specifying the active resources that must be disabled first

### Requirement: Agent Tenant Isolation
Agents installed by a tenant MUST be exclusive to that tenant unless explicitly marked as shared.

#### Scenario: Attempt to install a non-shared agent
- **WHEN** Tenant B attempts to install an agent already installed by Tenant A (and `shared` is false)
- **THEN** the system returns an error explaining the agent is bound to Tenant A, advising the creation of a new agent instance
- **AND** a link is provided to the GitHub documentation on creating multiple instances

#### Scenario: Install a shared agent
- **WHEN** Tenant B attempts to install an agent marked `shared: true`
- **THEN** the installation succeeds and the agent operates simultaneously in both tenants

### Requirement: Admin Tenant Switcher
The administrative user interface SHALL provide a switcher allowing users to change their active tenant context.

#### Scenario: User switches tenant context
- **WHEN** an administrator belongs to multiple tenants and selects a different tenant in the top navigation switcher
- **THEN** the administrative interface context switches to display resources only belonging to the selected tenant
