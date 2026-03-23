# Docker Upgrade Runbook (Draft)

## Status

Draft operator runbook for the current repository shape.

- This runbook is based on the existing `Docker Compose + Makefile` workflow already documented in
  the repository.
- It assumes source-based builds on the target host today.
- When the platform moves to versioned published images, the same flow stays valid but `git pull` +
  `--build` may be replaced by pinned image tag updates plus `docker compose pull`.

## When to Use

Use this runbook when the platform is deployed on one VM or host with the repository checked out
locally and started through the supported compose stack.

## Preconditions

- You have shell access to the deployment host
- The repository is already cloned on the host
- `.env.local` and `compose.override.yaml` are present and backed up
- You know the target Git revision, branch, or release tag to deploy

## Pre-Upgrade Checklist

1. Record the currently running revision:

```bash
git rev-parse HEAD
git status --short
```

2. Confirm there are no uncommitted production-only edits that would be overwritten unintentionally.

3. Back up state before upgrading:
   - PostgreSQL databases
   - `.env.local`
   - `compose.override.yaml`
   - `docker/openclaw/.env`
   - `.local/openclaw/state/`
   - any externalized storage used by agents

4. Review release notes for:
   - new env vars
   - migration requirements
   - changed compose fragments
   - removed or renamed services

5. Capture current service status:

```bash
make ps
```

## Standard Upgrade Flow

### 1. Fetch the target revision

```bash
git fetch origin
git checkout <target-ref>
```

If you deploy from `main` directly:

```bash
git pull origin main
```

### 2. Review configuration changes

Check whether the new release changes:

- `.env.local.example`
- compose files
- required secrets
- domain settings in `compose.override.yaml`

If secrets or generated OpenClaw config changed, re-run:

```bash
make bootstrap
```

Use this only when the release explicitly requires secret redistribution or regenerated state.

### 3. Rebuild and start the updated stack

For a full-stack upgrade:

```bash
make up
```

Current behavior: this runs `docker compose ... up --build -d` for the supported stack.

For a targeted service-only upgrade:

```bash
docker compose -f compose.yaml -f compose.core.yaml \
  $(for f in compose.agent-*.yaml compose.langfuse.yaml compose.openclaw.yaml; do [ -f "$f" ] && echo -n "-f $f "; done) \
  up -d --build --no-deps <service-name>
```

Use targeted upgrades only when the release notes explicitly say the change is isolated and does
not require shared dependency or migration changes.

### 4. Run migration steps

Run the migration commands required by the enabled services:

```bash
make litellm-db-init
make migrate
make knowledge-migrate
make dev-reporter-migrate
make dev-agent-migrate
make news-migrate
```

Notes:

- Run only the agent-specific migrations for services that are installed in this environment.
- `wiki-agent` currently has no dedicated migration target in the top-level `Makefile`.
- If a release introduces a new migration target, it must be listed in the release notes.

### 5. Refresh runtime discovery state

If the release changes manifests, labels, agent URLs, or newly adds/removes agents:

```bash
make agent-discover
```

### 6. Verify service health

Check container status:

```bash
make ps
```

Check core and key agent health endpoints:

```bash
curl -sf http://localhost/health
curl -sf http://localhost:8083/health
curl -sf http://localhost:8085/health
curl -sf http://localhost:8087/health
curl -sf http://localhost:8088/health
```

Check logs for recently restarted services:

```bash
make logs-core
make logs-openclaw
make logs-litellm
```

Use `Ctrl+C` after you confirm the logs are stable.

### 7. Run smoke verification

Minimum recommended smoke checks:

- open the main platform URL
- open admin login
- open Langfuse or OpenClaw if enabled
- trigger one known-safe agent flow
- verify scheduler or worker surfaces if the release touched them

If the environment supports it, also run the project smoke suite:

```bash
make e2e-smoke
```

Use this only in environments prepared for the E2E stack.

## Rollback Flow

Rollback is safe only if the upgrade did not introduce irreversible schema or data migrations.
If a migration is not backward compatible, restore from backup instead of simply checking out the
old revision.

### 1. Return to the previous revision

```bash
git checkout <previous-ref>
```

### 2. Rebuild and restart the previous services

```bash
make up
```

### 3. Restore data if needed

- If the failed upgrade changed schema incompatibly, restore databases from the pre-upgrade backup
- If OpenClaw state or generated config was changed incompatibly, restore:
  - `docker/openclaw/.env`
  - `.local/openclaw/state/`

### 4. Re-run health verification

```bash
make ps
curl -sf http://localhost/health
```

### 5. Document the failed revision

Record:

- target revision
- failed step
- migration status
- services affected
- rollback actions taken

## Expected Future Evolution

When the platform starts publishing versioned images, this runbook should evolve to:

1. update image tags in supported compose inputs
2. run `docker compose pull`
3. run explicit migration commands
4. restart services in supported order
5. verify health and roll back to previous pinned tags if required
