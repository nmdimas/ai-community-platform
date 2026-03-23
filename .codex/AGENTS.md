# Codex Skills

This directory contains shared skills synced from `skills/` (source of truth).

## Available Skills

- **agent-auditor**: Audit agents in the AI Community Platform for structure, testing, security,
- **agent-debugger**: Debug agent issues in the AI Community Platform. Use when the user reports
- **builder-agent**: Delegate a coding task to the autonomous pipeline. Supports two workflows:
- **devcontainer-provisioner**: Install missing software and start services on the fly inside the devcontainer
- **documentation**: documentation skill
- **iframe-admin-harmonizer**: Harmonize agent admin UIs rendered in core admin iframe. Use when an agent
- **monitor-version**: Auto-bump pipeline monitor version when builder/monitor/pipeline-monitor.sh is modified.
- **security-review**: Security review agent for PHP/Symfony codebase. Performs manual-style security
- **translater**: Translation agent for ua/en bilingual content. Handles UI labels, help text,
- **web-to-docs**: Convert a website or page with related links into a local collection of

## Usage

When a user request matches a skill, read the corresponding `skills/<name>/SKILL.md` and follow its instructions.

Skills source of truth: `skills/` directory. Do not edit copies — edit the source and run `make sync-skills`.
