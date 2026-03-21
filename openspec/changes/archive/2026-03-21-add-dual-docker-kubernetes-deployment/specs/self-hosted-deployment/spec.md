## ADDED Requirements

### Requirement: Official Docker Self-Hosted Packaging

The platform SHALL provide an official Docker-based packaging path for local development, hobby
production, and simple self-hosted installations.

#### Scenario: Operator deploys the platform on a single host

- **WHEN** an operator chooses the Docker deployment mode
- **THEN** the platform provides a curated compose-based bundle with documented configuration,
  bootstrap, migration, and health verification steps
- **AND** the operator does not need to infer the runtime topology from development-only files

#### Scenario: Operator upgrades a Docker deployment

- **WHEN** an operator upgrades the platform in Docker mode
- **THEN** the documentation describes the supported sequence for image update, migration, health
  checks, and rollback

#### Scenario: Docker upgrade uses explicit version pinning and verification

- **WHEN** an operator applies a supported Docker upgrade
- **THEN** the platform documents which image tags or release inputs must be changed
- **AND** the operator is instructed to run pre-upgrade backup and post-upgrade verification steps
- **AND** the rollback path restores the previous pinned versions intentionally

### Requirement: Cross-Mode Deployment Contract

The platform SHALL use one deployment contract across Docker and Kubernetes for service
configuration and lifecycle.

#### Scenario: Service configuration is packaged differently

- **WHEN** the same platform service is deployed through Docker or Kubernetes
- **THEN** it uses the same logical configuration inputs for secrets, public URLs, dependencies,
  and migration behavior
- **AND** packaging differences do not redefine the service contract

#### Scenario: Service requires startup validation

- **WHEN** a platform service depends on external infrastructure or seeded data
- **THEN** the service exposes documented startup and health expectations that can be wired into
  both Docker and Kubernetes packaging

#### Scenario: Cross-mode upgrade uses the same logical gates

- **WHEN** the platform is upgraded in either Docker or Kubernetes mode
- **THEN** the documented workflow verifies migration completion, core health, critical worker
  health, and public entrypoint health
- **AND** those checks are described as the standard release gates for supported deployments
