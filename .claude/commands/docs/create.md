---
name: "Docs: Create"
description: Create bilingual documentation (UA + EN) in the correct folder structure.
category: Docs
tags: [docs, documentation]
---

Read `.claude/skills/documentation/SKILL.md` for the full convention, templates, and section reference.

Execute the **Create** operation from the skill:
1. Parse `<section>/<filename>` from arguments (e.g., `agents/hello-agent`)
2. If no arguments, ask the user for section and filename
3. Follow the Create steps and use the matching template from the skill
