## ADDED Requirements

### Requirement: Managed Agent Projects Use Remote Repositories

The platform SHALL define a first-class `Agent Project` record for managed agent development and
release. Each managed agent project SHALL use a remote git repository as its source of truth and
SHALL be checked out locally under `projects/<project-slug>/`.

#### Scenario: Managed project uses remote repository
- **WHEN** an operator or admin creates an `Agent Project`
- **THEN** the project stores a remote repository URL and default branch
- **AND** the platform does not require the managed source to live under `apps/`
- **AND** the checkout path follows the convention `projects/<project-slug>/`

#### Scenario: Private or self-hosted repository is configured
- **WHEN** an `Agent Project` points to a private GitHub repository or a self-hosted GitLab repository
- **THEN** the project stores provider type, host URL when needed, auth mode, and a credential reference
- **AND** the repository remains manageable through the same project flow

### Requirement: Agent Projects Define Release and Deploy Contracts

Each `Agent Project` SHALL declare how release and deploy actions should run so future pipeline and
admin flows can target the project without ad hoc per-task configuration.

#### Scenario: Project defines release/deploy behavior
- **WHEN** an admin configures an `Agent Project`
- **THEN** the project stores release strategy, deploy type, deploy target, and post-deploy health/discovery checks
- **AND** later release flows can resolve these values from the project record

### Requirement: Agent Project Credentials Are Referenced, Not Persisted In Cleartext

The platform SHALL store credential references for managed repositories and deploy actions rather
than persisting raw tokens or keys directly in project records or task payloads.

#### Scenario: Project uses repository credentials
- **WHEN** a project requires clone/fetch/push access
- **THEN** the project stores a credential reference and auth mode
- **AND** logs, traces, and summaries do not expose the raw secret value

#### Scenario: Release credentials are more restricted than coding access
- **WHEN** a normal coding or validation stage runs for a project
- **THEN** write credentials for push/tag/deploy are not automatically exposed
- **AND** those credentials are only resolved for explicit release/deploy actions
