## ADDED Requirements

### Requirement: Official Kubernetes Packaging

The platform SHALL provide an official Kubernetes packaging path for operators who deploy through a
cluster-native environment.

#### Scenario: Operator installs the platform on Kubernetes

- **WHEN** an operator chooses the Kubernetes deployment mode
- **THEN** the platform provides an official packaging artifact such as a Helm chart
- **AND** the installation documents ingress, secrets, persistence, and dependency configuration

#### Scenario: Operator configures managed infrastructure

- **WHEN** an operator supplies external managed services for supported dependencies
- **THEN** the Kubernetes packaging allows those services to be referenced without patching the
  application source
- **AND** the required values are documented

#### Scenario: Operator upgrades the platform with Helm

- **WHEN** an operator upgrades the platform in Kubernetes mode
- **THEN** the platform documents a supported `helm upgrade` flow with chart versioning, values
  review, migration handling, rollout verification, and rollback guidance
- **AND** the operator can identify the previous release revision for recovery

### Requirement: Kubernetes Lifecycle Jobs and Probes

The platform SHALL define Kubernetes-native lifecycle behavior for migrations, health, and safe
rollout.

#### Scenario: Deployment requires migrations

- **WHEN** an operator upgrades or installs the platform through Kubernetes
- **THEN** the packaging defines a documented migration or bootstrap job flow
- **AND** the operator does not need to run undocumented one-off commands inside pods

#### Scenario: Kubernetes rolls out a platform service

- **WHEN** a platform service is deployed or restarted in Kubernetes
- **THEN** the service exposes readiness and liveness behavior suitable for rollout and recovery
- **AND** the packaging wires those probes explicitly

#### Scenario: Failed Kubernetes upgrade can be rolled back safely

- **WHEN** a Kubernetes upgrade fails due to a rollout error, probe failure, or post-upgrade smoke
  regression
- **THEN** the documented operator workflow includes `helm rollback` or equivalent recovery steps
- **AND** it explains which migration or data changes require special caution before rollback
