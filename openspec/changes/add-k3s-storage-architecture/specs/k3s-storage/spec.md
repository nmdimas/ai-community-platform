# k3s Storage Specification

## ADDED Requirements

### Requirement: Stateful Services Must Declare Durability Tier
The platform SHALL classify each stateful k3s dependency by durability and recovery criticality.

The initial matrix SHALL cover at least PostgreSQL, Redis, RabbitMQ, OpenSearch, Langfuse
dependencies, and the local container registry.

#### Scenario: Operator reviews k3s storage architecture
- **WHEN** an operator reads the platform k3s storage architecture
- **THEN** each stateful service is assigned an explicit durability tier
- **AND** the architecture states whether its data is authoritative, recommended to persist, or
  acceptable to rebuild

### Requirement: PostgreSQL Must Have Mandatory Backup And Restore Guidance
The platform SHALL treat PostgreSQL as the primary durable system of record in k3s deployments.

#### Scenario: Operator prepares a k3s upgrade
- **WHEN** an operator follows the upgrade path for a k3s deployment
- **THEN** the documented procedure requires a PostgreSQL backup before rollout
- **AND** the restore procedure includes post-restore verification steps

### Requirement: PVC Strategy Must Be Explicit For Persistent Services
The platform SHALL document which services require PVCs, which storage class they use, and the
baseline size expectations for single-node k3s.

#### Scenario: Operator provisions stateful services
- **WHEN** the operator renders or applies the k3s storage-aware deployment assets
- **THEN** the services marked as persistent have explicit PVC expectations
- **AND** the documentation states the intended storage class and size baseline for each service

### Requirement: Non-Authoritative State Must Have Loss Expectations
The platform SHALL document the loss and rebuild expectations for Redis, RabbitMQ, OpenSearch, and
other non-primary stateful services.

#### Scenario: A non-primary stateful service loses its volume
- **WHEN** Redis, RabbitMQ, or OpenSearch state is lost in a single-node k3s deployment
- **THEN** the operator documentation states whether the service should be restored from backup or
  rebuilt from authoritative sources
- **AND** the documentation identifies the operational impact of that loss
