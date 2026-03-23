## ADDED Requirements

### Requirement: Self-Hosted k3s Deployments Must Define Backup Coverage
The platform SHALL provide a backup coverage model for self-hosted k3s deployments.

#### Scenario: Operator prepares a self-hosted upgrade
- **WHEN** an operator prepares to upgrade a self-hosted k3s deployment
- **THEN** the runbook identifies which stateful services require backup before rollout
- **AND** the runbook identifies which services can be rebuilt instead of restored

### Requirement: Self-Hosted k3s Deployments Must Define Restore Verification
The platform SHALL provide restore verification guidance for authoritative stateful services.

#### Scenario: Operator restores PostgreSQL after failed rollout
- **WHEN** the operator restores PostgreSQL in a self-hosted k3s deployment
- **THEN** the runbook defines concrete verification checks for application health and data visibility
- **AND** the restore flow is treated as a first-class rollback path rather than an implicit assumption
