# Design: Translater and Security-Review Pipeline Agents

## Context

The Ultraworks pipeline (Sisyphus-orchestrated) and Builder pipeline (task-file-driven) share a common set of agents: architect, coder, reviewer, validator, tester, auditor, documenter, summarizer. Each agent has a subagent variant (`s-*`) for Ultraworks and a primary variant for Builder.

Two gaps exist:
1. No agent handles granular translation of i18n files, UI text, or prompts.
2. No agent performs deep security review beyond the auditor's surface-level checks.

## Goals / Non-Goals

### Goals

- Add `translater` agent to both workflows with a skill that understands YAML message files, Twig templates, Markdown docs, and prompt strings
- Add `security-review` agent to both workflows with a skill that covers OWASP ASVS 5.0 categories for PHP/Symfony
- Define clear boundaries between translater/documenter and security-review/auditor/reviewer
- Choose appropriate models for each agent's cognitive requirements
- Integrate into the existing pipeline phase structure without disrupting current flow

### Non-Goals

- Machine translation API integration (the agent uses LLM capabilities, not Google Translate)
- Automated penetration testing or dynamic analysis
- Replacing the auditor's compliance checklist (security-review is complementary)
- Translating code comments or variable names

## Decisions

### Decision: Translater uses Google Gemini as primary model

- **Why**: Translation requires strong multilingual capability, contextual understanding, and writing quality. Google Gemini models excel at one-shot writing and multilingual tasks. The documenter already uses `openai/gpt-5.4` -- spreading to Google avoids provider concentration and rate-limit coupling.
- **Primary**: `google/gemini-3.1-pro-preview` -- strong multilingual, excellent writing
- **Fallback chain**: `openai/gpt-5.4` (strong writing), `anthropic/claude-sonnet-4-6` (balanced), `minimax/MiniMax-M2.5` (tier2), `opencode-go/kimi-k2.5` (tier2), `opencode/big-pickle` (zen), `openrouter/free` (last resort)
- **Alternative considered**: `openai/gpt-5.4` as primary -- rejected because documenter already uses it, and Gemini is specifically strong at multilingual one-shot tasks per the model routing policy.

### Decision: Security-review uses Anthropic Claude Opus as primary model

- **Why**: Deep security review requires the strongest analytical reasoning, attention to detail, and ability to trace complex code paths. Claude Opus is tier1 for hardest agentic work. The auditor also uses Opus, but security-review runs at a different pipeline phase and the two are not concurrent.
- **Primary**: `anthropic/claude-opus-4-6` -- strongest analytical reasoning
- **Fallback chain**: `openai/gpt-5.4` (strongest direct alt), `opencode-go/glm-5` (long-horizon), `minimax/MiniMax-M2.7` (tier1 direct), `opencode/big-pickle` (zen), `google/gemini-3.1-pro-preview` (reserve), `openrouter/free` (last resort)
- **Alternative considered**: `openai/gpt-5.4` as primary -- rejected because security review needs the deepest reasoning capability, and Opus is the established choice for review/audit-class tasks.

### Decision: Translater runs after documenter, before summarizer

- **Why**: The translater needs to see what the documenter wrote (new docs, updated docs) and what the coder changed (UI labels, messages). Running after documenter ensures all translatable content exists. Running before summarizer ensures the summary reflects the final state.
- **Pipeline position**: Phase 6b (optional, after documenter, before summarizer)
- **Trigger**: Sisyphus delegates translater when the change touches i18n files, docs, UI text, or prompts
- **Alternative considered**: Running in parallel with documenter -- rejected because translater may need to translate content the documenter just created.

### Decision: Security-review runs after auditor, as an optional deep-dive

- **Why**: The auditor performs a broad compliance check (S/T/C/X/O/D). Security-review performs a deep dive on the X (security) dimension only. Running after auditor avoids duplicate surface-level checks. Security-review is triggered only when the change touches security-sensitive code (auth, input handling, file operations, external calls).
- **Pipeline position**: Phase 5b (optional, after auditor loop completes)
- **Trigger**: Sisyphus delegates security-review when the change touches auth/authz, input validation, file upload, command execution, external HTTP calls, or session/cookie handling
- **Alternative considered**: Running before auditor -- rejected because auditor may catch structural issues that affect security review findings.

### Decision: Security-review is read-only (like auditor subagent)

- **Why**: Security findings require human judgment for remediation. Auto-fixing security issues risks introducing new vulnerabilities. The agent reports findings; the coder fixes them in a subsequent iteration if needed.
- **Alternative considered**: Allow security-review to apply fixes -- rejected due to risk of introducing new vulnerabilities through automated remediation.

### Decision: Translater has edit permissions (like documenter)

- **Why**: Translation is a mechanical-creative task where the agent directly modifies YAML files, Twig templates, and Markdown docs. Requiring a separate coder pass for translation changes adds unnecessary pipeline latency.
- **Alternative considered**: Read-only translater that outputs a translation report for coder to apply -- rejected because translation changes are low-risk and well-scoped.

## Boundary Definitions

### Translater vs Documenter

| Aspect | Translater | Documenter |
|--------|-----------|------------|
| **Scope** | Translates existing content between UA/EN | Creates new documentation content |
| **Input** | Existing text that needs translation | Feature context, code changes |
| **Output** | Translated YAML keys, Twig strings, doc mirrors | New doc files, updated INDEX.md |
| **Languages** | Bidirectional UA<->EN | Writes UA first, then EN mirror |
| **Files** | `messages.*.yaml`, `*.html.twig`, `docs/**/*.md`, prompts | `docs/**/*.md`, `INDEX.md` |
| **When** | After content exists and needs translation | After implementation, to document changes |

### Security-Review vs Auditor

| Aspect | Security-Review | Auditor |
|--------|----------------|---------|
| **Scope** | Deep security analysis only | Broad compliance (S/T/C/X/O/D/E) |
| **Depth** | OWASP ASVS 5.0 categories, severity ratings | Surface-level security checks (X section) |
| **Output** | Security report with OWASP mapping | Compliance report with PASS/WARN/FAIL |
| **Permissions** | Read-only | Read-only (subagent) or read-write (primary) |
| **When** | Optional, for security-sensitive changes | Always, as quality gate |
| **Blocking** | Advisory (WARN) unless CRITICAL severity | FAIL blocks pipeline |

### Security-Review vs Reviewer

| Aspect | Security-Review | Reviewer |
|--------|----------------|---------|
| **Scope** | Security vulnerabilities | Code quality (SOLID, DRY, KISS) |
| **Output** | Security findings with severity | Refactored code |
| **Permissions** | Read-only | Read-write |
| **Focus** | What could be exploited | What could be cleaner |

## New Category for Model Routing

Two new categories in `oh-my-opencode.jsonc`:

- `translation` -- for translater agent delegation
- `security-review` -- for security-review agent delegation

## Risks / Trade-offs

- **Translater may produce inconsistent terminology** across files if not given a glossary.
  - Mitigation: Skill includes a term glossary section and instructions to check existing translations for consistency before translating new content.
- **Security-review may produce false positives** that waste coder time.
  - Mitigation: Severity ratings (CRITICAL/HIGH/MEDIUM/LOW/INFO) help prioritize. Only CRITICAL/HIGH are flagged for coder attention.
- **Adding two more agents increases pipeline duration.**
  - Mitigation: Both are optional phases triggered only when relevant. Translater runs only when i18n/docs change. Security-review runs only for security-sensitive code.
- **Google Gemini as primary for translater may have rate limits.**
  - Mitigation: Fallback chain includes 5 alternative providers.

## Open Questions

- Should the translater maintain a persistent glossary file (e.g., `docs/glossary.yaml`) for term consistency across pipeline runs?
- Should security-review findings above HIGH severity block the pipeline (like auditor FAIL), or always be advisory?
