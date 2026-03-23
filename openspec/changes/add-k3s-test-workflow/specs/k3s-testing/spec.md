# k3s Testing Specification

## ADDED Requirements

### Requirement: Local k3s Must Have Automated Smoke Coverage
The platform SHALL provide automated smoke checks for the local k3s runtime.

#### Scenario: Running k3s smoke checks
- **WHEN** the operator runs the documented smoke command against a healthy local k3s deployment
- **THEN** the smoke checks must confirm core availability
- **AND** confirm at least one reference agent endpoint
- **AND** return a clear pass or fail result

### Requirement: Local k3s Must Support Incremental E2E Coverage
The platform SHALL support a staged E2E workflow for the local k3s runtime.

#### Scenario: Running the minimal k3s E2E subset
- **WHEN** the operator runs the documented k3s E2E command
- **THEN** at least one meaningful browser or end-to-end user flow must execute against k3s-exposed endpoints
- **AND** failures must identify whether the issue is in deployment, ingress, auth, or test configuration

### Requirement: Test Entry Points Must Be Documented
k3s test entry points SHALL be discoverable and documented.

#### Scenario: Looking for k3s test commands
- **WHEN** a developer or operator opens the project documentation
- **THEN** they must be able to find the smoke and E2E test commands for k3s
- **AND** understand the prerequisites required before running them

