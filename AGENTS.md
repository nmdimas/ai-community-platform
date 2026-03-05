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

This repository may be used by three AI coding agents:

- `Codex`
- `Claude`
- `Antigravity`

All three agents should follow the same repository-level rules, documentation conventions, and OpenSpec workflow unless a tool-specific instruction file explicitly overrides part of the behavior.

## Instruction Priority

- `AGENTS.md` is the shared baseline instruction file for all supported agents
- `CLAUDE.md` may add or restate guidance for Claude-specific tooling
- Additional agent-specific instruction files may be added later if `Antigravity` or other agents require local overrides

## Global Permissions

To streamline development, the following permissions are **pre-approved** for all AI agents (`Codex`, `Claude`, `Antigravity`):

1.  **Docker & Compose Commands**: All commands starting with `docker` or `docker-compose` are considered safe to auto-run. Agents should set `SafeToAutoRun: true` (or equivalent) for these commands.
2.  **File Modifications**: Agents have full permission to create, modify, or delete any files within the repository to fulfill a task. No separate confirmation is required for each file edit unless it involves critical security credentials or explicitly marked "read-only" files.
3.  **Project Scripts**: Commands using `./vendor/bin/neuron` or project-specific scripts are pre-approved.

## Shared Skills

Project skills live in `skills/` (committed to repo). Each agent syncs them to its local config:

- **Claude**: `.claude/skills/` (synced via `make sync-skills` or `./scripts/sync-skills.sh claude`)
- **Cursor / Antigravity**: `.cursor/skills/` (future)
- **Codex**: `.codex/skills/` (future)

Rules:
- Edit skills in `skills/` (source of truth), never in `.claude/skills/` or other agent dirs
- Run `make sync-skills` after pulling changes or editing skills
- After `git clone`, run `make sync-skills` to populate agent-specific skill directories

## Working Expectation

When multiple agents are used on the same project:

- treat `docs/` as the shared product-facing source layer
- treat OpenSpec files as the source of truth for spec-driven changes
- avoid changing established conventions without updating the shared instructions first
