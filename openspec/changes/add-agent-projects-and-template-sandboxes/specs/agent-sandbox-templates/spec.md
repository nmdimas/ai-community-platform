## ADDED Requirements

### Requirement: Agent Projects Select A Sandbox Contract

Each `Agent Project` SHALL define how its development/build sandbox is resolved. The platform SHALL
support three sandbox modes: built-in template, custom image, and compose-service-backed sandbox.

#### Scenario: Project uses built-in template
- **WHEN** an admin configures a project with sandbox type `template`
- **THEN** the project references a built-in template identifier
- **AND** pipeline/build execution uses the tools and environment defined by that template

#### Scenario: Project uses custom image
- **WHEN** an admin configures a project with sandbox type `custom_image`
- **THEN** the project stores an image or Dockerfile reference
- **AND** the sandbox is created from that project-specific definition

#### Scenario: Project uses compose service as sandbox
- **WHEN** an admin configures a project with sandbox type `compose_service`
- **THEN** the project stores the compose service name
- **AND** build/development actions run against that configured service

### Requirement: Platform Provides Initial Real-Stack Sandbox Templates

The platform SHALL ship initial sandbox templates derived from real existing agent stacks so the
first managed projects can adopt the flow without designing custom images first.

#### Scenario: PHP/Symfony template is available
- **WHEN** a user creates a project for the extracted hello-agent
- **THEN** the platform provides a `php-symfony-agent` sandbox template derived from the current hello-agent stack

#### Scenario: Python/FastAPI template is available
- **WHEN** a user creates a project for the extracted news-maker-agent
- **THEN** the platform provides a `python-fastapi-agent` sandbox template derived from the current news-maker-agent stack

#### Scenario: Node/Web template is available
- **WHEN** a user creates a project for the extracted wiki-agent
- **THEN** the platform provides a `node-web-agent` sandbox template derived from the current wiki-agent stack

### Requirement: Release Sandbox Uses Stronger Permission Boundaries

The platform SHALL distinguish normal development sandboxes from release/deploy sandboxes.

#### Scenario: Development sandbox runs without release permissions
- **WHEN** planning, coding, validation, or testing stages run for a project
- **THEN** the sandbox has only the repository and tool access needed for those stages
- **AND** push/tag/deploy credentials are not mounted by default

#### Scenario: Release action resolves deploy permissions explicitly
- **WHEN** a user triggers a release or deploy action for a project
- **THEN** the platform resolves the project's release/deploy credential references for that action
- **AND** the stronger permissions are scoped to the release/deploy sandbox only
