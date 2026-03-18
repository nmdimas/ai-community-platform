---
name: builder-agent
description: >
  Delegate a coding task to the autonomous builder agent pipeline. Creates a task
  file in builder/tasks/todo/ — the pipeline monitor auto-starts workers to execute it.
  Triggers on: "builder", "delegate", "делегувати", "білдер", "queue task",
  "add to pipeline", "schedule task", "pipeline task",
  "agent builder", "поставити задачу", "в чергу", "на білдера".
  Do NOT execute the task yourself — only create the task file.
---

# Builder Agent — Task Queue

Delegate a task to the autonomous multi-agent builder pipeline.
**You do NOT implement the task yourself.** You create a `.md` file in `builder/tasks/todo/`
and the pipeline monitor (`builder/monitor/pipeline-monitor.sh`) auto-starts workers to
execute it.

## When to Use

- User says "delegate to builder", "делегувати білдеру", "на білдера"
- User asks to "queue", "schedule", or "add to pipeline"
- User says "agent builder", "builder agent", "поставити задачу"
- User wants work done asynchronously by the builder agents
- User explicitly asks NOT to do the work now but to queue it

## When NOT to Use

- User wants you (Claude) to do the work directly in this conversation
- User is asking about pipeline status (just tell them to check the monitor)
- Task is trivial (single file edit, quick fix) — do it directly instead

## Pipeline Overview

The builder pipeline runs these agents in sequence:
1. **Architect** — reads specs, plans implementation
2. **Coder** — writes the code
3. **Validator** — runs PHPStan, CS-Fixer, type checks
4. **Tester** — runs Codeception tests
5. **Summarizer** — writes final summary to `builder/tasks/summary/*.md`

Each task runs in an isolated git worktree on its own branch (`pipeline/<slug>`).

## Workflow

### Step 1 — Gather Task Details

From the user's request, determine:

1. **Title** — short imperative sentence (e.g., "Implement change: add-delivery-channels")
2. **Description** — what needs to be done (1-3 sentences)
3. **OpenSpec reference** — if this implements an OpenSpec change, link to proposal/builder/tasks/specs
4. **Context** — dependencies, patterns to follow, key decisions
5. **Key files** — files to create or modify
6. **Validation** — how to verify success
7. **Priority** — default 1; higher = picked up first (ask user if multiple tasks)

### Step 2 — Create the Task File

First, ensure the directory exists: `mkdir -p builder/tasks/todo` (builder/tasks/ is gitignored).
Then write the file to `builder/tasks/todo/`. Use the slug format: `implement-change-<id>.md` for
OpenSpec changes, or `<descriptive-slug>.md` for ad-hoc tasks.

**File format** — see `references/example-task.md` for the full template.

```markdown
<!-- priority: 1 -->
# Task title here

Brief description of what needs to be done.

## OpenSpec

- Proposal: openspec/changes/<id>/proposal.md
- Tasks: openspec/changes/<id>/tasks.md
- Spec delta: openspec/changes/<id>/specs/<component>/spec.md

## Context

Background, dependencies, patterns to follow.

## Key files to create/update

- path/to/file.php (new or modify)

## Validation

- PHPStan level 8 passes
- CS-Fixer passes
- Unit/functional tests pass
```

### Step 3 — Confirm to User

After creating the file, tell the user:

1. Task file path created
2. Priority level
3. How to monitor: `./builder/monitor/pipeline-monitor.sh` (auto-starts workers)
4. Where results will appear:
   - **Branch**: `pipeline/<slug>` (with commits)
   - **Summary**: `builder/tasks/summary/<timestamp>-<slug>.md`
   - **Reports**: `.opencode/pipeline/reports/`
   - **Logs**: `.opencode/pipeline/logs/`
   - **Task moves**: `builder/tasks/todo/` → `builder/tasks/in-progress/` → `builder/tasks/done/` or `builder/tasks/failed/`

## Priority & Task Ordering

Priority controls the order tasks are picked up by workers.

### Priority Rules

- `<!-- priority: N -->` — first line of the task file
- **Higher number = picked up first** (priority 5 runs before priority 1)
- Default priority = 1 (if omitted)
- Monitor TUI keys: `[+]` raise priority, `[-]` lower priority

### Managing Dependencies Between Tasks

When tasks depend on each other (e.g., task B needs code from task A):

**Sequential execution (MONITOR_WORKERS=1, default):**
- Set higher priority on the dependency: task A = priority 3, task B = priority 1
- Tasks execute one by one in priority order: A first, then B
- B will run on top of A's branch since A's commits land on main first

**Parallel execution (MONITOR_WORKERS=2+):**
- Only queue independent tasks at the same time
- For dependent tasks, queue the dependency first with higher priority
- Queue the dependent task AFTER the dependency completes (or set priority so
  it starts only when workers become free after the dependency finishes)

### Priority Strategy Examples

**3 independent tasks** — same priority, any order:
```
task-a.md: <!-- priority: 1 -->
task-b.md: <!-- priority: 1 -->
task-c.md: <!-- priority: 1 -->
```

**Chain: A → B → C** (B depends on A, C depends on B):
```
task-a.md: <!-- priority: 3 -->  ← runs first
task-b.md: <!-- priority: 2 -->  ← runs second
task-c.md: <!-- priority: 1 -->  ← runs last
```
With MONITOR_WORKERS=1 this guarantees correct order.

**Mixed: A and B independent, C depends on both:**
```
task-a.md: <!-- priority: 2 -->  ← can run in parallel with B
task-b.md: <!-- priority: 2 -->  ← can run in parallel with A
task-c.md: <!-- priority: 1 -->  ← waits for A and B to finish
```
With MONITOR_WORKERS=2, A and B run in parallel, then C runs.

### When to Ask User About Priority

- Always ask when queuing 2+ tasks in the same conversation
- Ask if the task might conflict with existing tasks in `builder/tasks/todo/`
- Check `builder/tasks/todo/` and `builder/tasks/in-progress/` before assigning priority
- Suggest priorities based on dependencies the user describes

## Monitoring & Results

### Pipeline Monitor TUI
```bash
./builder/monitor/pipeline-monitor.sh
```
- Auto-starts workers when tasks appear (configurable: `MONITOR_WORKERS=N`)
- Shows real-time progress, worker status, cost tracking
- Keys: `[s]` manual start, `[k]` kill, `[f]` retry failed, `[+/-]` priority

### Checking Results After Completion

1. **Task summary** (written by Summarizer agent):
   ```
   builder/tasks/summary/<timestamp>-<slug>.md
   ```

2. **Pipeline handoff** (inter-agent context):
   ```
   .opencode/pipeline/handoff.md
   ```

3. **Git branch with commits**:
   ```bash
   git log pipeline/<slug>
   git diff main...pipeline/<slug>
   ```

4. **Batch reports**:
   ```
   .opencode/pipeline/reports/batch_<timestamp>.md
   ```

5. **Task file with metadata** (in `builder/tasks/done/` or `builder/tasks/failed/`):
   ```markdown
   <!-- batch: 20260312_104327 | status: pass | duration: 420s | branch: pipeline/slug -->
   ```

## Important Rules

1. **NEVER execute the task yourself** — only create the task file
2. **NEVER start pipeline-batch.sh manually** — the monitor handles auto-start
3. Always include OpenSpec references when implementing a spec change
4. Always include the `## Validation` section so the pipeline knows what to check
5. Use Ukrainian in the description if the user writes in Ukrainian
