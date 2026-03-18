## Context

The repository already has an external-agent workspace contract and a worktree-based pipeline
execution model. What is still missing is the product-level object that ties these together:

- repository source
- local checkout path
- stack-specific sandbox
- credentials
- release/deploy contract

Without that object, the future web pipeline cannot safely answer basic questions such as:

- where should the task run?
- which git remote should receive the commit?
- which credentials are allowed for this task?
- which container image or toolchain should build/test the code?
- how should the resulting agent be released and redeployed?

The compact conversation context that motivated these design decisions is preserved in
`brainstorm-context.md` in this change directory.

## Goals

- one canonical source model for managed agents: remote git repositories
- support private GitHub, GitLab.com, and self-hosted GitLab-like remotes
- keep local checkout convention aligned with `projects/<agent-name>/`
- allow each project to select a stack-specific sandbox contract
- derive the first templates from real existing agents instead of synthetic examples
- migrate bundled agents in safe, staged batches

## Non-Goals

- automatic cloning from arbitrary third-party repos without review
- exposing raw PATs/tokens in task payloads or logs
- forcing one universal build image for PHP, Python, and Node agents
- implementing the full web kanban/release UI in the same step

## Proposed Domain Model

### Agent Project

`Agent Project` becomes the canonical development/release record.

Suggested fields:

- `id`
- `name`
- `slug`
- `agent_name`
- `repository_provider` (`github`, `gitlab`, `generic_git`)
- `repository_host_url` (nullable for github.com / gitlab.com)
- `repository_remote_url`
- `repository_default_branch`
- `repository_auth_mode` (`ssh_key`, `https_pat`, `app_token`)
- `repository_credential_ref`
- `checkout_path` (`projects/<slug>`)
- `sandbox_type` (`template`, `custom_image`, `compose_service`)
- `sandbox_template_id`
- `sandbox_image`
- `sandbox_dockerfile_path`
- `sandbox_compose_service`
- `sandbox_env_refs`
- `release_strategy`
- `deploy_type`
- `deploy_target`
- `healthcheck_url`
- `post_deploy_discovery_enabled`

### Credential References

Project records store references, not raw secrets.

- Secret material must be resolved only inside the sandbox or release runner
- Logs and trace payloads must remain redacted
- Write credentials are available only to explicit release/deploy actions, not to normal planning or coding stages

### Sandbox Template

Built-in template profiles provide opinionated defaults for common agent stacks:

- `php-symfony-agent`
  - base tools: `php`, `composer`, `phpunit`, `phpstan`, `php-cs-fixer`, `git`
  - source baseline: current `hello-agent`
- `python-fastapi-agent`
  - base tools: `python`, `pip`, `pytest`, `ruff`, `alembic`, `git`
  - source baseline: current `news-maker-agent`
- `node-web-agent`
  - base tools: `node`, `npm`/`pnpm`, test runner, build tooling, `git`
  - source baseline: current `wiki-agent`

Each template may be overridden by a custom image or by attaching to a compose service.

## Key Decisions

### 1. Repo-only managed source model

- **Decision**: managed agent projects always use a remote repository as the source of truth
- **Why**: it removes ambiguity between `apps/` and `projects/`, simplifies release/deploy logic,
  and aligns with the operator-facing external-agent contract

### 2. `apps/` becomes migration source, not long-term origin

- **Decision**: existing bundled agents are treated as extraction sources only
- **Why**: supporting both models indefinitely creates split-brain ownership and weakens the future
  web pipeline

### 3. One repo mode, multiple sandbox modes

- **Decision**: the repository model is fixed, but sandbox execution remains per-project
- **Why**: PHP, Python, and Node agents have materially different toolchains and runtime needs

### 4. Release/deploy runs in a stricter sandbox than coding

- **Decision**: write credentials for push/tag/deploy are not exposed to ordinary pipeline stages
- **Why**: commit/tag/push/deploy permissions are materially higher risk than build/test access

## Migration Stages

### Stage 1: Agent Project foundation

- implement the `Agent Project` record in core
- support remote repository metadata and credential references
- support checkout into `projects/<slug>`
- wire sandbox selection at project level
- keep existing bundled agents runnable during transition

### Stage 2: Hello template and extraction

- populate `a2a-hello-agent`
- move hello-agent into repo-only managed flow
- treat it as the reference PHP/Symfony template
- validate checkout, compose fragment, manifest, health, discovery, and release basics

### Stage 3: News-maker template and extraction

- populate `a2a-news-maker-agent`
- extract the Python/FastAPI news-maker stack
- validate template needs around Python dependencies, migrations, and admin routes

### Stage 4: Wiki template and extraction

- populate `a2a-wiki-agent`
- extract the Node/wiki stack
- validate template needs around Node package management, build/test flow, and web/admin routing

## Risks / Trade-offs

- Three migrations in one roadmap create coordination overhead
  - Mitigation: keep extraction staged and validate each template before the next
- Private/self-hosted git support increases credential complexity
  - Mitigation: store only credential refs, not raw secrets; isolate release permissions
- Some current Dockerfiles may be too runtime-focused for development use
  - Mitigation: allow `custom_image` and `compose_service` escape hatches per project

## Open Questions For Later Implementation

- which secret backend should back `credential_ref`
- whether checkout/update should be operator-triggered only or also scheduler-triggered
- whether release should create tags locally or via provider APIs
- how much of deploy should be local Docker vs external CI by default
