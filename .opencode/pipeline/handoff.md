# Pipeline Handoff

- **Task**: Finish OpenSpec change: add-environment-checker (remaining tasks)
- **Started**: 2026-03-20
- **Branch**: main
- **Pipeline ID**: finish-env-checker
- **Profile**: standard
- **Workflow**: Ultraworks

---

## Task Description

Завершити незавершені пункти OpenSpec change `add-environment-checker`.

### Remaining Tasks (from tasks.md)

1. **5.1** — Add `## Environment` section placeholder to `.opencode/pipeline/handoff-template.md`
2. **7.1** — Update `builder/README.md` with env-check.sh usage, flags, exit codes, examples
3. **7.2** — Create `docs/pipeline-env-checker.md` — developer-facing English documentation
4. **7.3** — Update `builder/AGENTS.md` to mention env-check pre-flight step in pipeline flow diagram
5. **8.1** — Run `shellcheck builder/env-check.sh` — zero warnings
6. **8.6** — Verify `openspec validate add-environment-checker --strict` passes

### References

- OpenSpec proposal: `openspec/changes/add-environment-checker/proposal.md`
- OpenSpec tasks: `openspec/changes/add-environment-checker/tasks.md`
- OpenSpec design: `openspec/changes/add-environment-checker/design.md`
- Implementation: `builder/env-check.sh`, `builder/env-requirements.json`, `builder/pipeline.sh`

---

## Documenter

- **Status**: pending
- **Task**: Update documentation files (7.1, 7.2, 7.3)

---

## Validator

- **Status**: pending
- **Task**: Run shellcheck (task 8.1)

---

## Summarizer

- **Status**: pending
- **Final Summary**: Must write to `builder/tasks/summary/`