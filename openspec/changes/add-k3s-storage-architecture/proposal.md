# Change: Add k3s Storage Architecture for Stateful Platform Services

## Why

The platform already has high-level k3s plans for local validation and Hetzner deployment, but the
storage layer is still underspecified. Postgres, Redis, RabbitMQ, OpenSearch, and observability
dependencies have different durability, recovery, and sizing needs. Without an explicit storage
architecture, the first real k3s rollout risks accidental data loss, weak backup coverage, and
hard-to-reverse persistence decisions.

## What Changes

- **ADDED**: A canonical k3s storage architecture for stateful platform services
- **ADDED**: Classification of services by durability and recovery requirements
- **ADDED**: StorageClass, PVC, sizing, and retention requirements for single-node k3s
- **ADDED**: Backup and restore requirements for Postgres, OpenSearch, Redis, RabbitMQ, and
  Langfuse dependencies
- **ADDED**: Operator runbook requirements for validating persistence and recovery
- **MODIFIED**: k3s deployment requirements to include storage verification before runtime rollout

## Impact

- Affected specs:
  - `k3s-deployment`
  - `self-hosted-deployment`
  - `k3s-storage` (new)
- Affected code:
  - `deploy/charts/ai-community-platform/`
  - k3s deployment values and templates
  - deployment runbooks and docs
- Affected operations:
  - local k3s validation
  - Hetzner single-node production deployment
  - backup and restore procedures
