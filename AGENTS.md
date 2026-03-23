# Product Repo Instructions

This file applies to the `brama-core` product repository.

Workspace-level runtime and deployment instructions now live in the root workspace [`AGENTS.md`](/Users/nmdimas/work/brama-workspace/AGENTS.md).

## Use This Repository For

- application code under `apps/`
- product docs under `docs/`
- product specs under `openspec/`
- product tests under `tests/`
- shared product skills under `skills/`

## Product OpenSpec

Always open [`openspec/AGENTS.md`](/Users/nmdimas/work/brama-workspace/brama-core/openspec/AGENTS.md) when the request involves proposals, spec changes, architecture changes, or ambiguous feature work.

## Product-Level Rules

- Treat `openspec/` as the source of truth for spec-driven product changes
- Treat `docs/` as the source of truth for product-facing documentation
- Treat `skills/` as the committed source of truth for shared product skills
- Do not move runtime/deployment concerns back into `brama-core` unless the change is explicitly product-owned

## Scheduled Jobs

Agents can declare recurring or one-shot jobs that the platform's central scheduler executes automatically via A2A.

To add scheduled jobs, include a `scheduled_jobs` array in the agent's manifest (`ManifestController`):

```json
{
  "scheduled_jobs": [
    {
      "name": "daily-digest",
      "skill_id": "myagent.digest",
      "cron_expression": "0 9 * * *",
      "payload": {"channel": "general"},
      "max_retries": 3,
      "retry_delay_seconds": 120,
      "timezone": "Europe/Kyiv"
    }
  ]
}
```

Rules:

- `skill_id` must reference an existing skill from the same agent's `skills` array
- `cron_expression` uses standard 5-field cron; omit for one-shot jobs
- Jobs are registered automatically on agent install and removed on uninstall
- Admin can also create jobs manually via the scheduler UI at `/admin/scheduler`
- If an agent update removes a referenced skill, the job is flagged as stale in the admin UI
