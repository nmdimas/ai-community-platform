---
name: "Docs: Delete"
description: Remove a bilingual documentation pair (UA + EN) and clean up references.
category: Docs
tags: [docs, documentation]
---

Read `.claude/skills/documentation/SKILL.md` for the full convention and section reference.

Execute the **Delete** operation from the skill:
1. Parse `<section>/<filename>` from arguments (e.g., `agents/hello-agent`)
2. If no arguments, ask the user which doc to delete
3. Follow the Delete steps — remove both files, clean empty folders, flag broken references
