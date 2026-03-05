---
name: "Docs: Migrate"
description: Migrate legacy .en.md suffix docs to folder-based ua/en structure.
category: Docs
tags: [docs, documentation, migration]
---

Read `.claude/skills/documentation/SKILL.md` for the full convention and section reference.

Execute the **Migrate** operation from the skill:
1. Parse `<section>` from arguments (e.g., `specs`) or migrate all sections if no argument
2. Find all `<name>.md` + `<name>.en.md` pairs in the target section
3. Follow the Migrate steps — move to `ua/` and `en/` folders, update cross-references
