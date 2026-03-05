---
name: "Docs: Update"
description: Update an existing bilingual documentation pair (UA + EN), keeping both in sync.
category: Docs
tags: [docs, documentation]
---

Read `.claude/skills/documentation/SKILL.md` for the full convention and section reference.

Execute the **Update** operation from the skill:
1. Parse `<section>/<filename>` from arguments (e.g., `agents/hello-agent`)
2. If no arguments, ask the user which doc to update
3. Follow the Update steps — migrate from legacy format if needed, keep both files in sync
