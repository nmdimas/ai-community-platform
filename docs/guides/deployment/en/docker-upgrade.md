# Docker Upgrade Runbook

## Overview

This runbook describes the supported upgrade flow for a Docker Compose-based installation.

> **Current state**: The platform is deployed by checking out the repository on the target host
> and running `make up`. When versioned published images are available, this runbook will evolve
> to use `docker compose pull` with pinned image tags instead of source-based builds.

## When to Use

Use this runbook when upgrading a Docker Compose installation on a single host where the
repository is checked out locally.

## Preconditions

- Shell access to the deployment host
- Repository already cloned on the host
- `.env.local` and `compose.override.yaml` are present and backed up
- You know the target Git revision, branch, or release tag to deploy

## Pre-Upgrade Checklist

### 1. Record the currently running revision

```bash
git rev-parse HEAD
git status --short
make ps
```

### 2. Confirm no uncommitted production-only edits

Verify there are no local changes that would be overwritten unintentionally.

### 3. Back up state before upgrading

- PostgreSQL databases
- `.env.local`
- `compose.override.yaml`
- `docker/openclaw/.env`
- `.local/openclaw/state/`
- Any externalized storage used by agents

### 4. Review release notes

Check for:
- New env vars required
- Migration requirements
- Changed compose fragments
- Removed or renamed services

## Standard Upgrade Flow

### 1. Fetch the target revision

```bash
git fetch origin
git checkout <target-ref>
```

If deploying from `main` directly:

```bash
git pull origin main
```

### 2. Review configuration changes

Check whether the new release changes:
- `.env.local.example`
- Compose files
- Required secrets
- Domain settings in `compose.override.yaml`

If secrets or generated OpenClaw config changed, re-run:

```bash
make bootstrap
```

### 3. Rebuild and start the updated stack

```bash
make up
```

This runs `docker compose ... up --build -d` for the supported stack.

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

Run only the agent-specific migrations for services installed in this environment.

### 5. Refresh runtime discovery state

If the release changes manifests, labels, agent URLs, or adds/removes agents:

```bash
make agent-discover
```

### 6. Verify service health

```bash
make ps
curl -sf http://localhost/health
curl -sf http://localhost:8083/health
curl -sf http://localhost:8085/health
```

### 7. Run smoke verification

Minimum recommended smoke checks:
- Open the main platform URL
- Open admin login
- Trigger one known-safe agent flow
- Verify scheduler or worker surfaces if the release touched them

## Rollback Flow

Rollback is safe only if the upgrade did not introduce irreversible schema or data migrations.
If a migration is not backward compatible, restore from backup instead of checking out the old
revision.

### 1. Return to the previous revision

```bash
git checkout <previous-ref>
```

### 2. Rebuild and restart the previous services

```bash
make up
```

### 3. Restore data if needed

If the failed upgrade changed schema incompatibly, restore databases from the pre-upgrade backup.

### 4. Re-run health verification

```bash
make ps
curl -sf http://localhost/health
```

## Upgrade Verification Gates

| Gate | Docker check |
|------|-------------|
| Migration success | Migration commands exit 0 |
| Core health | `curl -sf http://localhost/health` |
| Critical worker health | `make ps` shows scheduler running |
| Public entrypoint health | Platform URL loads |

## Related Runbooks

- [Kubernetes upgrade runbook](./kubernetes-upgrade.md)
- [Deployment topology matrix](./deployment-topology.md)
- [Full deployment guide](./deployment.md)
