# Change: Add dual Docker and Kubernetes deployment packaging

## Why

The project already has a workable Docker Compose topology for local development and single-host
production, which is a good fit for hobby use and small self-hosted installs. At the same time,
the platform direction now needs to support two operator realities:

- `Docker` for simple and low-cost deployments
- `Kubernetes` for teams that already operate cluster-native infrastructure

Today the repository documents mostly the Compose path. There is no official packaging model for
Kubernetes, no clear split between app services and optional external infrastructure, and no
operator-facing contract for secrets, migrations, upgrades, or image versioning across both modes.

## What Changes

- Define `dual deployment packaging` as an official platform capability
- Keep `Docker Compose` as the canonical simple/self-hosted path for:
  - local development
  - hobby production
  - single-node VM installs
- Add an official `Kubernetes` packaging path for:
  - cluster-native operators
  - managed databases / caches / queues
  - rolling upgrades and multi-replica services where appropriate
- Standardize deployment contracts that must work across both modes:
  - versioned container images
  - secrets and config injection
  - health / readiness / startup behavior
  - migrations and bootstrap jobs
  - ingress and public URL configuration
  - external dependency declarations
- Define explicit operator upgrade flows for both supported modes:
  - Docker: pull or pin new image tags, run preflight checks, execute migrations, restart services in
    supported order, verify health, and document rollback to previous tags
  - Kubernetes: `helm upgrade` flow with values review, migration jobs/hooks, rollout observation,
    probe-based readiness gates, and rollback through Helm revision history
- Publish separate operator documentation for:
  - Docker setup
  - Kubernetes setup
  - upgrades, backups, rollback, and troubleshooting

## Impact

- Affected specs:
  - new capability `self-hosted-deployment`
  - new capability `kubernetes-packaging`
- Affected code and docs:
  - release and image publishing workflow
  - compose packaging layout
  - Helm chart or Kubernetes manifests
  - deployment runbooks under `docs/guides/deployment/`
  - healthcheck / migration / bootstrap conventions across services
- Related existing assets:
  - current Compose deployment guide and Makefile remain the baseline
  - current modular compose files become the Docker packaging foundation
- Breaking considerations:
  - deployment support becomes an explicit product surface and must be versioned
  - services that currently assume local Compose-only dependencies may need config refactoring to
    support external managed services cleanly
