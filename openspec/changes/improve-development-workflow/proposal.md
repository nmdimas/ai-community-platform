# Change: Improve Development Workflow with Strict Quality Gates

## Why
Current development workflow lacks explicit requirements for database migrations, test-first development, continuous integration within active proposals, and ROADMAP synchronization. This leads to inconsistent quality, potential production issues, and poor visibility of project progress.

## What Changes
- **ADDED**: Mandatory database migration workflow for all schema changes
- **ADDED**: Test-Driven Development (TDD) requirement for new features
- **ADDED**: Strict quality gates that must pass before PR creation
- **ADDED**: Continuous commit/push requirement within active proposals
- **ADDED**: Sandbox and long-running task best practices
- **ADDED**: ROADMAP management workflow with mandatory updates
- **MODIFIED**: OpenSpec Stage 2 implementation workflow with explicit steps
- **ADDED**: Workflow guidelines document at `docs/WORKFLOW_GUIDELINES.md`
- **MODIFIED**: Updated ROADMAP.md with current project state and 2025 planning

## Impact
- Affected specs: platform/development-process
- Affected code: All new feature development
- Affected docs: AGENTS.md, WORKFLOW_GUIDELINES.md, ROADMAP.md
- Developer experience: More structured but safer development process with better visibility