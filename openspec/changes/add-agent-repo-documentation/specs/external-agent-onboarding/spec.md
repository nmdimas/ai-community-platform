## MODIFIED Requirements

### Requirement: External Agent Compose Fragment Contract

An external agent compose fragment MUST support the image-first deployment pattern.
The fragment SHALL declare both an `image` field referencing a pre-built container image
(e.g. from GHCR) and a `build` field for local development fallback. This allows operators
to pull pre-built images for fast deployment while retaining the ability to build locally
when modifying agent source code.

The fragment MUST satisfy all existing compose fragment contract requirements (service naming,
labels, healthcheck, network) and additionally:
- The `image` field SHALL reference `ghcr.io/<owner>/<agent-name>:<tag>`
- The `build.context` SHALL point to the agent source directory
- The `build.dockerfile` SHALL reference the agent's standalone Dockerfile

#### Scenario: Operator deploys agent using pre-built GHCR image
- **WHEN** operator runs `docker compose pull <agent-name>`
- **THEN** the pre-built image is pulled from GHCR
- **AND** the agent starts without requiring a local build step

#### Scenario: Developer builds agent locally for development
- **WHEN** developer runs `docker compose build <agent-name>`
- **THEN** the agent is built from local source using the standalone Dockerfile
- **AND** the local image overrides the GHCR image for subsequent runs

## ADDED Requirements

### Requirement: Agent Repository Self-Documentation

Every external agent repository MUST include documentation that enables a developer or operator
to understand, run, and integrate the agent without referring to the platform repository.

The repository SHALL contain:
- `README.md` with agent description, prerequisites, standalone run instructions, GHCR image
  reference, platform integration instructions, API endpoint table, and environment variable reference
- `compose.fragment.yaml` implementing the external agent compose fragment contract
- `.env.local.example` documenting all required and optional environment variables with comments

#### Scenario: New developer onboards to agent repository
- **WHEN** a developer clones the agent repository for the first time
- **THEN** the README.md provides sufficient information to run the agent locally
- **AND** the compose.fragment.yaml provides the integration contract for platform deployment

#### Scenario: Operator connects agent to platform using GHCR image
- **WHEN** operator copies compose.fragment.yaml to platform compose.fragments/
- **THEN** the fragment references the GHCR image as the default
- **AND** no local build is required for standard deployment
