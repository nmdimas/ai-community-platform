# Design: k3s Storage Architecture

## Context

The platform's current k3s plans identify the required infrastructure services but do not yet define
which state must persist, how it should be stored, or how operators recover from failures. This
change focuses on the storage layer for the current single-node k3s target, not on multi-node HA.

The target environments are:

- local Rancher Desktop k3s for validation
- single-node Hetzner k3s for self-hosted deployment

## Goals

- Define storage expectations for all stateful services in the k3s topology
- Prevent accidental coupling between "must survive node restart" and "can be rebuilt"
- Make backup/restore responsibilities explicit before the first real rollout
- Keep the first implementation realistic for single-node k3s and local-path storage

## Non-Goals

- Multi-node replication or HA storage
- Cross-region backup strategy
- Full disaster recovery automation
- Managed cloud databases or object storage migration

## Decisions

### 1. Service data is classified by recovery criticality

Stateful services are split into three classes:

- **Tier A: must be backed up and restored intentionally**
  - Postgres (core + agent databases)
- **Tier B: should persist locally, but can be rebuilt from source state if needed**
  - OpenSearch indices
  - Langfuse stateful dependencies where local history matters
- **Tier C: persistence is optional or environment-dependent**
  - Redis cache/state
  - RabbitMQ queues
  - local container registry

This avoids treating every stateful pod as equally critical.

### 2. Postgres is the primary durable system of record

For the current platform architecture, Postgres remains the source of truth for platform and
agent-owned relational data. k3s storage decisions must optimize first for:

- safe PVC attachment
- predictable restart behavior
- pre-upgrade backups
- documented restore verification

### 3. Single-node k3s uses simple persistence, not pseudo-HA

For local and Hetzner single-node k3s, the default baseline is a local-path-backed PVC strategy.
The goal is operational clarity, not introducing a fake HA story on one node.

This means the architecture must document:

- which PVCs are required
- expected size baselines
- what survives pod restarts
- what does not survive node loss unless restored from backup

### 4. Backup policy must match service semantics

Each stateful service must have an explicit operator expectation:

- Postgres: mandatory pre-upgrade backup, documented restore path
- OpenSearch: recommended snapshot/export strategy or documented rebuild expectation
- Redis: explicit statement whether data is critical or disposable in each environment
- RabbitMQ: explicit statement whether queues are durable but non-restored, or included in backups
- Langfuse: document whether traces are required to survive upgrades in the target environment

### 5. Externalization remains an allowed future path

The architecture must support both bundled in-cluster services and externalized dependencies later.
Current k3s work should not hardcode assumptions that prevent moving Postgres, Redis, or OpenSearch
out of the cluster in future phases.

## Target Service Matrix

### Postgres

- Persistence: required
- PVC: required
- Backup: required
- Restore drill: required
- Default mode: in-cluster PVC-backed database for local and Hetzner single-node

### Redis

- Persistence: environment-dependent
- PVC: optional, but decision must be explicit
- Backup: not required for cache-only usage
- Restore drill: not required unless Redis stores critical workflow state

### RabbitMQ

- Persistence: environment-dependent
- PVC: optional, but queue durability expectations must be explicit
- Backup: not primary; operator guidance must explain acceptable queue loss/replay model

### OpenSearch

- Persistence: recommended
- PVC: required if logs/search data must survive pod restart
- Backup: recommended or rebuild expectation must be documented
- Restore drill: optional in first iteration, but data-loss expectation must be explicit

### Langfuse Dependencies

- Persistence: recommended when observability history matters
- Backup: environment-dependent
- Must state whether observability data is operationally critical or best-effort

## Implementation Shape

The first implementation should add:

- chart values documenting persistence flags, PVC sizes, and storage classes
- templates or values wiring for PVC-backed stateful services
- runbooks for pre-upgrade backup and restore verification
- local k3s validation steps that prove data survives pod restart for required services

## Risks

- Treating Redis or RabbitMQ as durable without a recovery story creates false safety
- Treating OpenSearch as disposable without stating that operator expectation creates silent data loss
- Using local-path storage without restore drills may create a misleading production narrative

## Open Questions

- Should Langfuse be classified as Tier B or Tier C for the first Hetzner rollout?
- Does any current workflow require Redis persistence beyond cache/session use?
- Do we want OpenSearch snapshot automation in the first iteration, or only manual operator guidance?
