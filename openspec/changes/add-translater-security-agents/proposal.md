# Change: Add Translater and Security-Review Pipeline Agents

## Why

The Ultraworks and Builder pipelines currently lack two specialized capabilities that are repeatedly needed:

1. **Translation**: The platform is bilingual (UA/EN). Every change that touches UI labels, help text, error messages, docs, or agent prompts requires manual translation work. The documenter agent writes bilingual docs but does not handle granular translation of YAML message files, Twig templates, or prompt strings. A dedicated translater agent fills this gap with context-aware, terminology-consistent translation.

2. **Security review**: The auditor agent checks for surface-level security issues (hardcoded secrets, SQL injection vectors, XSS) as part of a broad compliance checklist. It does not perform a deep, manual-style security review with OWASP ASVS mapping, severity ratings, or PHP/Symfony-specific vulnerability analysis. A dedicated security-review agent provides this depth without overloading the auditor's scope.

## What Changes

- **New agent: `translater`** -- Pipeline agent (both Ultraworks subagent `s-translater` and Builder primary `translater`) that finds translatable elements, detects supported languages, translates by context, and maintains term consistency across YAML message files, Twig templates, docs, and prompts.
- **New agent: `security-review`** -- Pipeline agent (both Ultraworks subagent `s-security-review` and Builder primary `security-review`) that performs deep security review of PHP/Symfony code with OWASP ASVS 5.0 category mapping, severity ratings, and a PHP/Symfony-specific checklist.
- **New skill: `translater`** -- Skill file defining translation workflow, language detection, context rules, term glossary management, and what NOT to translate mechanically.
- **New skill: `security-review`** -- Skill file defining security checklist, severity rules, OWASP mapping, and PHP/Symfony focus areas.
- **Modified: model routing** -- New entries in `oh-my-opencode.jsonc` for both agents with appropriate model selection and fallback chains.
- **Modified: pipeline documentation** -- Updated model routing docs and workflow integration docs.

## Impact

- Affected specs: pipeline-agents (new delta)
- Affected code: `.opencode/agents/` (4 new manifests), `skills/` (2 new skills), `.opencode/oh-my-opencode.jsonc` (model routing), `docs/guides/pipeline-models/` (documentation)
- **No new external dependencies** -- uses existing model providers
- **No breaking changes** to existing agents or pipeline phases
