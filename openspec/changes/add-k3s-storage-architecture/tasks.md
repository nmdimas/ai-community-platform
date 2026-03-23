# Tasks: add-k3s-storage-architecture

## 1. Define Storage Contracts
- [ ] 1.1 Add a `k3s-storage` spec describing service tiers, durability expectations, and recovery requirements
- [ ] 1.2 Modify `k3s-deployment` spec to require storage verification before core and agent rollout
- [ ] 1.3 Modify `self-hosted-deployment` spec to require backup and restore guidance for stateful services

## 2. Document Architecture
- [ ] 2.1 Document the stateful service matrix: Postgres, Redis, RabbitMQ, OpenSearch, Langfuse, registry
- [ ] 2.2 Define the baseline StorageClass and PVC strategy for local and Hetzner single-node k3s
- [ ] 2.3 Document size baselines and retention assumptions per service
- [ ] 2.4 Document what data is considered authoritative vs rebuildable

## 3. Prepare Implementation Path
- [ ] 3.1 Update Helm values structure to express persistence and storage class choices explicitly
- [ ] 3.2 Add or adjust chart wiring for PVC-backed services based on the approved storage matrix
- [ ] 3.3 Add backup and restore runbook requirements for Postgres and other selected stateful services
- [ ] 3.4 Add verification steps proving required state survives pod restart in k3s

## 4. Documentation
- [ ] 4.1 Add deployment/storage architecture documentation under `docs/`
- [ ] 4.2 Add operator guidance for pre-upgrade backup, restore, and rollback checks

## 5. Quality Checks
- [ ] 5.1 `openspec validate add-k3s-storage-architecture --strict`
- [ ] 5.2 Review the change against `enable-k3s-runtime` and `migrate-to-k3s-hetzner` for overlap and conflicts
