# Local k3s Runtime Validation Specification

## ADDED Requirements

### Requirement: Local k3s Validation Must Be Repeatable
The platform SHALL provide a repeatable validation flow for Rancher Desktop k3s.

#### Scenario: Re-running validation from an existing local cluster
- **WHEN** an operator follows the documented validation flow
- **THEN** they must be able to confirm cluster health, platform health, and local access using documented commands

### Requirement: Every Validation Stage Must Have Success Signals
Each k3s validation stage SHALL define expected success signals.

#### Scenario: Validating the infrastructure layer
- **WHEN** the operator validates PostgreSQL, Redis, RabbitMQ, and OpenSearch
- **THEN** the documentation must specify how to confirm each service is healthy
- **AND** the documentation must specify how to inspect failures

#### Scenario: Validating the core runtime
- **WHEN** the operator validates the core runtime
- **THEN** the documentation must include at least one health endpoint check
- **AND** at least one operator-facing access check

#### Scenario: Validating a reference agent
- **WHEN** the operator validates the reference agent
- **THEN** the documentation must include both the agent health check and a proof of core-to-agent reachability

