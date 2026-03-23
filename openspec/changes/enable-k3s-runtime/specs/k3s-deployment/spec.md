# k3s Deployment Specification

## ADDED Requirements

### Requirement: The Platform Must Support Local k3s Bootstrapping
The platform SHALL provide a documented path to boot a minimal runtime on local k3s.

#### Scenario: Starting from a fresh Rancher Desktop k3s environment
- **GIVEN** Rancher Desktop k3s is enabled locally
- **WHEN** the operator follows the documented k3s setup flow
- **THEN** they must be able to create the target namespace and shared configuration resources
- **AND** apply the platform deployment assets without schema errors

### Requirement: Infrastructure Services Must Be Verifiable Before Core Starts
Infrastructure services SHALL be deployable and verifiable as an independent layer.

#### Scenario: Applying infrastructure manifests
- **WHEN** the operator deploys PostgreSQL, Redis, RabbitMQ, and OpenSearch
- **THEN** each service must reach a healthy running state or provide a diagnosable failure state
- **AND** the verification steps must include concrete `kubectl` commands to inspect the result

### Requirement: Core Must Be Reachable in k3s
The core service SHALL be deployable and reachable in the local k3s runtime.

#### Scenario: Core deployment completes
- **WHEN** the core deployment is applied after the infrastructure layer is healthy
- **THEN** the core pod must become ready
- **AND** the core health endpoint must return a successful response

### Requirement: A Reference Agent Must Run in k3s
The platform SHALL prove agent runtime viability by booting at least one reference agent in k3s.

#### Scenario: Reference agent deployment completes
- **WHEN** the reference agent deployment is applied
- **THEN** the agent pod must become ready
- **AND** the agent health endpoint must return a successful response
- **AND** the core service must be able to reach that agent over cluster networking

