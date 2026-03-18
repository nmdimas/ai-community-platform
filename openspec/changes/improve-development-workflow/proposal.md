# Change: Improve Development Workflow with Strict Quality Gates

## Why
Current development workflow lacks explicit requirements for database migrations, test-first development, and continuous integration within active proposals. This leads to inconsistent quality and potential production issues.

## What Changes
- **ADDED**: Mandatory database migration workflow for all schema changes
- **ADDED**: Test-Driven Development (TDD) requirement for new features
- **ADDED**: Strict quality gates that must pass before PR creation
- **ADDED**: Continuous commit/push requirement within active proposals
- **ADDED**: Sandbox and long-running task best practices
- **MODIFIED**: OpenSpec Stage 2 implementation workflow with explicit steps
- **ADDED**: Workflow guidelines document at `docs/WORKFLOW_GUIDELINES.md`

## Impact
- Affected specs: platform/development-process
- Affected code: All new feature development
- Affected docs: AGENTS.md, new WORKFLOW_GUIDELINES.md
- Developer experience: More structured but safer development process