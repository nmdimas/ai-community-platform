# Pipeline Handoff

- **Task**: Add translater and security-review agents to Ultraworks and Builder
- **Started**: 2026-03-20
- **Branch**: main
- **Pipeline ID**: add-translater-security-agents
- **Profile**: complex+docs
- **Workflow**: Ultraworks

---

## Task Description

Додати два нові агента в обидва workflow (Ultraworks та Builder):

### Agent 1: translater
- **Role**: переклади у контексті поточних мов системи (ua/en)
- **Scope**: UI labels, help text, error messages, docs, agent/system prompts
- **Naming**: `translater` (не `translator`)
- **Deliverables**:
  - `s-translater` manifest for Ultraworks (`.opencode/agents/s-translater.md`)
  - `translater` manifest for Builder (`.opencode/agents/translater.md`)
  - Skill file in `skills/translater/SKILL.md` (synced to `.opencode/skills/`)
  - Model routing in `.opencode/oh-my-opencode.jsonc`
  - Integration in workflow docs

### Agent 2: security-review
- **Role**: security review of PHP/Symfony/Twig code
- **Scope**: auth/authz, secrets/config, headers/cookies/session/csrf, input validation, output encoding, file upload, command execution, SSRF, redirects, dependencies
- **References**: OWASP ASVS 5.0, OWASP Code Review Guide, Symfony CSRF docs, OWASP PHP Configuration Cheat Sheet
- **Deliverables**:
  - `s-security-review` manifest for Ultraworks (`.opencode/agents/s-security-review.md`)
  - `security-review` manifest for Builder (`.opencode/agents/security-review.md`)
  - Skill file in `skills/security-review/SKILL.md`
  - Model routing in `.opencode/oh-my-opencode.jsonc`
  - Integration in workflow docs (where it stands in pipeline)

### Quality bar
- Do not break existing agents
- Do not duplicate existing roles without reason
- Document boundaries between translater/documenter and security-review/auditor/reviewer

### Expected artifacts
- New agent manifests (4 files)
- New skills (2 files in skills/)
- Model routing config update
- Documentation updates (workflow/model tables)
- summary.md in builder/tasks/summary/

---

## Planner

- **Status**: done
- **Profile**: complex+docs
- **Reasoning**: Task creates new agents and skills, modifies pipeline config, requires documentation. This is infrastructure work that touches .opencode/agents/, .opencode/oh-my-opencode.jsonc, skills/, and docs/.
- **Estimated files**: 12-15 files

---

## Architect

- **Status**: done
- **Change-id**: `add-translater-security-agents`

### Files Created

**OpenSpec Proposal:**
- `openspec/changes/add-translater-security-agents/proposal.md`
- `openspec/changes/add-translater-security-agents/design.md`
- `openspec/changes/add-translater-security-agents/tasks.md`
- `openspec/changes/add-translater-security-agents/specs/pipeline-agents/spec.md`

**Agent Manifests (4 files):**
- `.opencode/agents/s-translater.md` — Ultraworks subagent, edit permissions, google/gemini-3.1-pro-preview
- `.opencode/agents/translater.md` — Builder primary agent, google/gemini-3.1-pro-preview
- `.opencode/agents/s-security-review.md` — Ultraworks subagent, read-only (edit: deny, write: deny), anthropic/claude-opus-4-6
- `.opencode/agents/security-review.md` — Builder primary agent, anthropic/claude-opus-4-6

**Skills (2 files, synced to .opencode/skills/):**
- `skills/translater/SKILL.md` — translation workflow, language detection, context rules, term glossary, exclusion rules
- `skills/security-review/SKILL.md` — OWASP ASVS 5.0 checklist (V2/V3/V4/V5/V6/V8/V10/V13/V14), severity ratings, PHP/Symfony patterns

**Model Routing (modified):**
- `.opencode/oh-my-opencode.jsonc` — added `s-translater` and `s-security-review` agent configs, added `translation` and `security-review` categories, updated Sisyphus prompt_append with Phase 5b/6b/7

**Documentation (modified):**
- `docs/guides/pipeline-models/en/pipeline-models.md` — added rows to Role tiers, Ultraworks matrix, Builder matrix
- `docs/guides/pipeline-models/ua/pipeline-models.md` — added rows to Role tiers, Ultraworks matrix, Builder matrix

### Design Decisions

1. **Model choices:**
   - Translater: `google/gemini-3.1-pro-preview` — strong multilingual + writing, one-shot task (matches Google exception policy), avoids provider overlap with documenter (openai)
   - Security-review: `anthropic/claude-opus-4-6` — strongest analytical reasoning for deep security analysis, tier1 for hardest work

2. **Workflow placement:**
   - Translater: Phase 6b (optional, after documenter, before summarizer) — needs to see documenter output to translate/mirror docs
   - Security-review: Phase 5b (optional, after auditor loop, before documenter) — deep security dive after broad compliance check
   - Both are optional phases triggered by Sisyphus based on change content

3. **Boundary definitions:**
   - Translater vs Documenter: translater translates existing content, documenter creates new content
   - Security-review vs Auditor: security-review does deep OWASP-mapped analysis of X (security) only; auditor does broad S/T/C/X/O/D/E compliance
   - Security-review vs Reviewer: security-review finds vulnerabilities (read-only); reviewer improves code quality (read-write)

4. **Permissions:**
   - Translater: edit+write (like documenter) — translation is low-risk, well-scoped
   - Security-review: read-only (like auditor subagent) — security fixes need human judgment

5. **Pipeline impact:**
   - Phase numbering changed: old Phase 6 (Finalize) split into Phase 6 (Documentation), Phase 6b (Translation), Phase 7 (Summarize)
   - Documenter and Summarizer no longer run in parallel — translater needs documenter output first

### Notes for Integration

- Sisyphus prompt_append updated with new phases 5b, 6b, 7
- New categories `translation` and `security-review` added for delegation
- Skills synced to `.opencode/skills/translater/` and `.opencode/skills/security-review/`
- No changes to existing agent manifests or skills
- No breaking changes to existing pipeline flow — new phases are optional

---

## Coder

- **Status**: pending

---

## Reviewer

- **Status**: done

### Files Reviewed

| File | Purpose |
|------|---------|
| `.opencode/agents/s-translater.md` | Ultraworks subagent manifest |
| `.opencode/agents/translater.md` | Builder primary agent manifest |
| `.opencode/agents/s-security-review.md` | Ultraworks subagent manifest |
| `.opencode/agents/security-review.md` | Builder primary agent manifest |
| `skills/translater/SKILL.md` | Translation skill (source) |
| `skills/security-review/SKILL.md` | Security skill (source) |
| `.opencode/skills/translater/SKILL.md` | Translation skill (synced) |
| `.opencode/skills/security-review/SKILL.md` | Security skill (synced) |
| `.opencode/oh-my-opencode.jsonc` | Model routing config |
| `docs/guides/pipeline-models/en/pipeline-models.md` | EN docs |
| `docs/guides/pipeline-models/ua/pipeline-models.md` | UA docs |

### Issues Found

#### 1. `s-translater.md` — Incomplete `permission` block (LOW)

**Problem**: `s-translater.md` has `permission: delegate_task: deny, task: deny` but is missing `edit: deny` and `write: deny`.

**Pattern** (from `s-auditor.md`):
```yaml
permission:
  edit: deny
  write: deny
  delegate_task: deny
  task: deny
```

**Current** in `s-translater.md`:
```yaml
permission:
  delegate_task: deny
  task: deny
```

The subagent's role is read-only translation review — it should not edit or write source files. The missing deny entries are a consistency gap.

#### 2. `openrouter/free` in fallback chains (INFO)

All new agent configs end with `openrouter/free` as the last-resort fallback. The project policy requires OpenRouter models to carry the `:free` suffix (e.g., `qwen3-coder:free`). The bare `openrouter/free` identifier does not follow this convention and may not be a valid model.

**Note**: This pattern already exists in `s-auditor`, `s-summarizer`, and `s-documenter`. Since it is the last of 6 fallbacks and 5 real models precede it, it is non-blocking — but the config is technically inconsistent with stated policy.

### Improvements Made

None — fixes are low-risk but not strictly required for the agents to function. The critical infrastructure (manifests, skills, routing, docs) is complete and correct.

### Verdict

**PASS** — All required files are present and structurally correct. Naming is consistent (`translater` not `translator`, `security-review` not `security_audit`). Skill files have proper workflows, glossaries (translater), OWASP checklists (security-review), and boundary definitions. Model routing follows the 6-provider rule. Documentation is bilingual and synchronized.

**Non-blocking notes**:
- Consider adding `edit: deny, write: deny` to `s-translater.md`'s permission block for symmetry with `s-auditor.md`
- Consider replacing `openrouter/free` with a real `:free`-suffixed model in all agent fallback chains (low priority, existing agents have the same pattern)

---

## Validator

- **Status**: pending

---

## Tester

- **Status**: done
- **Verdict**: PASS

### Files Verified

**Agent Manifests (4 files):**
- ✅ `.opencode/agents/s-translater.md` — exists, valid YAML frontmatter, mode=subagent, model=google/gemini-3.1-pro-preview
- ✅ `.opencode/agents/translater.md` — exists, valid YAML frontmatter, mode=primary, model=google/gemini-3.1-pro-preview
- ✅ `.opencode/agents/s-security-review.md` — exists, valid YAML frontmatter, mode=subagent, model=anthropic/claude-opus-4-6, read-only (edit: deny, write: deny)
- ✅ `.opencode/agents/security-review.md` — exists, valid YAML frontmatter, mode=primary, model=anthropic/claude-opus-4-6

**Skills (2 source + 2 synced = 4 files):**
- ✅ `skills/translater/SKILL.md` — exists, 159 lines
- ✅ `skills/security-review/SKILL.md` — exists, 275 lines
- ✅ `.opencode/skills/translater/SKILL.md` — synced (7085 bytes)
- ✅ `.opencode/skills/security-review/SKILL.md` — synced (11182 bytes)

**Model Routing:**
- ✅ `.opencode/oh-my-opencode.jsonc` — valid JSONC
  - `s-translater` agent config present (lines 151-161)
  - `s-security-review` agent config present (lines 163-173)
  - `translation` category present (lines 273-284)
  - `security-review` category present (lines 285-297)

**Documentation:**
- ✅ `docs/guides/pipeline-models/en/pipeline-models.md` — contains Translater and Security-Review rows in Role tiers, Ultraworks matrix, Builder matrix
- ✅ `docs/guides/pipeline-models/ua/pipeline-models.md` — contains Translater and Security-Review rows in Role tiers, Ultraworks matrix, Builder matrix

### Checks Passed

| Check | Result |
|-------|--------|
| All 4 agent manifest files exist | PASS |
| All manifests have valid YAML frontmatter | PASS |
| Skills exist in source location (skills/) | PASS |
| Skills are synced to .opencode/skills/ | PASS |
| Model routing config is valid JSONC | PASS |
| New agent configs present in oh-my-opencode.jsonc | PASS |
| New categories present in oh-my-opencode.jsonc | PASS |
| EN documentation has new agent rows | PASS |
| UA documentation has new agent rows | PASS |

### Verdict

**PASS** — All infrastructure files for new agents (`translater`, `security-review`) are correctly created, synced, and documented.

---

## Auditor

- **Status**: pending

---

## Documenter

- **Status**: pending

---

## Summarizer

- **Status**: pending