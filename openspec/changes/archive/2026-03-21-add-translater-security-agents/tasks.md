## 1. Agent Manifests

- [x] 1.1 Create `.opencode/agents/s-translater.md` (Ultraworks subagent)
- [x] 1.2 Create `.opencode/agents/translater.md` (Builder primary agent)
- [x] 1.3 Create `.opencode/agents/s-security-review.md` (Ultraworks subagent)
- [x] 1.4 Create `.opencode/agents/security-review.md` (Builder primary agent)

## 2. Skills

- [x] 2.1 Create `skills/translater/SKILL.md` with translation workflow, language detection, context rules, term consistency, exclusion rules
- [x] 2.2 Create `skills/security-review/SKILL.md` with security checklist, severity rules, OWASP ASVS mapping, PHP/Symfony focus areas

## 3. Model Routing

- [x] 3.1 Add `s-translater` agent config to `.opencode/oh-my-opencode.jsonc`
- [x] 3.2 Add `s-security-review` agent config to `.opencode/oh-my-opencode.jsonc`
- [x] 3.3 Add `translation` category to `.opencode/oh-my-opencode.jsonc`
- [x] 3.4 Add `security-review` category to `.opencode/oh-my-opencode.jsonc`

## 4. Documentation

- [x] 4.1 Update `docs/guides/pipeline-models/en/pipeline-models.md` with new agent rows in both matrices
- [x] 4.2 Update `docs/guides/pipeline-models/ua/pipeline-models.md` with new agent rows in both matrices
- [x] 4.3 Update Sisyphus prompt_append in `oh-my-opencode.jsonc` to include translater and security-review phases

## 5. Skill Sync

- [x] 5.1 Sync new skills to `.opencode/skills/` via `make sync-skills` or manual copy
