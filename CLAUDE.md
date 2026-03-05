<!-- OPENSPEC:START -->

# OpenSpec Instructions

These instructions are for AI assistants working in this project.

Always open `@/openspec/AGENTS.md` when the request:

- Mentions planning or proposals (words like proposal, spec, change, plan)
- Introduces new capabilities, breaking changes, architecture shifts, or big performance/security work
- Sounds ambiguous and you need the authoritative spec before coding

Use `@/openspec/AGENTS.md` to learn:

- How to create and apply change proposals
- Spec format and conventions
- Project structure and guidelines

Keep this managed block so 'openspec update' can refresh the instructions.

<!-- OPENSPEC:END -->

## Multi-Agent Policy

This repository may be worked on by three AI coding agents:

- `Codex`
- `Claude`
- `Antigravity`

Claude should treat `AGENTS.md` as the shared baseline instruction layer for repository-wide behavior.

## Claude-Specific Position

- `CLAUDE.md` can restate or extend shared repository guidance for Claude tooling
- If there is a conflict between shared process and local task execution details, preserve repository conventions and document the reason for any exception
- OpenSpec workflow, language policy, and documentation conventions are shared across all supported agents

### Permissions & Auto-run

- Claude is authorized to auto-approve and execute all `docker` and `docker-compose` commands.
- Claude has full permission to edit all files within this project as needed.
