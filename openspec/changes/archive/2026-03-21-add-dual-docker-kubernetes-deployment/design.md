## Context

The repository already has a strong starting point for Docker-based operation: modular compose
files, a Makefile-based operator workflow, and a production guide for a single VPS deployment.
What is missing is not the basic container topology but the productization of deployment support in
two directions:

- a simple, supported Docker self-hosted path
- a structured Kubernetes path for cluster-native operators

The design goal is not to force both modes to look identical. The goal is to make them share one
service contract so the platform can be packaged differently without forking runtime behavior.

## Goals / Non-Goals

- Goals:
  - support both hobby-scale Docker installs and cluster-native Kubernetes installs
  - keep Docker as the fast path for local development and small production
  - define one cross-platform contract for secrets, migrations, health, and URLs
  - make stateful dependency ownership explicit
  - provide operator-grade docs and upgrade guidance

- Non-Goals:
  - guaranteeing feature parity for every topology on day one
  - building a managed SaaS control plane
  - requiring Kubernetes for ordinary self-hosted users
  - bundling every possible stateful dependency into every Kubernetes install

## Decisions

### 1. Docker remains the canonical simple install

- **Decision**: keep Docker Compose as the default path for local development, hobby production,
  and single-node self-hosted installs
- **Why**: it matches the current repository, the current deployment guide, and the lowest-friction
  operator workflow
- **Alternatives considered**:
  - switching to Kubernetes-only packaging — too heavy for hobby and small-team usage

### 2. Kubernetes is an official, separate packaging target

- **Decision**: provide Kubernetes packaging as a first-class deployment mode rather than an
  unofficial translation of compose files
- **Why**: operators who already run Kubernetes need stable manifests, secret patterns, probes,
  jobs, and upgrade guidance
- **Alternatives considered**:
  - relying on Kompose-like conversion from compose — not reliable enough for production

### 3. Use one deployment contract, multiple packaging forms

- **Decision**: Docker and Kubernetes must share the same service contract for configuration,
  startup, health, migrations, and discovery
- **Why**: deployment packaging should not redefine application behavior
- **Alternatives considered**:
  - Docker-only env model and separate Kubernetes-only config model — creates divergence fast

### 4. Prefer Helm for Kubernetes packaging

- **Decision**: use Helm as the main Kubernetes packaging layer
- **Why**: it is the dominant pattern in self-hosted OSS products that support both simple and
  advanced installs, and it gives operators a stable `values.yaml` contract
- **Alternatives considered**:
  - raw manifests only — too hard to configure for different environments
  - Kustomize-only — workable internally, weaker as a public operator interface

### 5. Make stateful dependencies explicit and partially externalizable

- **Decision**: every stateful dependency must be classified as:
  - bundled by default
  - optional
  - recommended external in Kubernetes
- **Why**: Docker users often want a batteries-included stack, while Kubernetes users often want
  managed Postgres, Redis, object storage, or ingress
- **Alternatives considered**:
  - always bundle everything — poor fit for cluster operators
  - require every dependency to be external — poor fit for hobby Docker users

## Recommended Packaging Model

### Docker

- Curated compose bundle
- Explicit `.env` / secret contract
- Optional compose fragments for agents and add-ons
- Make targets or scripts for bootstrap, migration, health verification, and upgrades

Recommended operator upgrade sequence:

1. Review release notes and compatibility matrix
2. Back up databases and persistent state
3. Pin or update image tags in the supported compose inputs
4. Pull images and run migration commands in the documented order
5. Restart stateless services first where possible, then long-running workers
6. Run health and smoke verification
7. Roll back to the previous image tags if verification fails

### Kubernetes

- Helm umbrella chart
- `values.yaml` for:
  - image tags
  - ingress hosts
  - secret refs
  - external dependency endpoints
  - persistence classes / sizes
  - replica counts for stateless services
- Jobs or hooks for:
  - migrations
  - bootstrap seed steps
  - optional agent registration sync

Recommended operator upgrade sequence:

1. Review chart release notes, app versions, and values diffs
2. Confirm backup coverage for stateful dependencies
3. Run `helm upgrade` with reviewed values and pinned chart/app versions
4. Let migration hooks or upgrade jobs complete before traffic promotion
5. Observe rollout status, readiness, and job completion
6. Run smoke checks against ingress and core health endpoints
7. Use `helm rollback` to the previous revision if rollout or verification fails

## Upgrade Flow Principles

### 1. Upgrades must be versioned and reversible

- **Decision**: every supported deployment mode must define:
  - pinned image tags or chart versions
  - a documented preflight checklist
  - a documented rollback path
- **Why**: self-hosted operators need a predictable way to recover from failed upgrades

### 2. Migrations must be explicit, not implied by container restart

- **Decision**: database or storage migrations are run by a documented command, job, or hook rather
  than relying on an opaque side effect during container startup
- **Why**: this reduces surprise, improves observability, and makes rollback risk easier to assess

### 3. Verification gates should be the same logical checks in both modes

- **Decision**: Docker and Kubernetes use different mechanics, but the same logical verification:
  - migration success
  - core health
  - critical worker health
  - ingress or public surface health
  - optional smoke tests
- **Why**: operators should reason about one upgrade policy even if the execution environment differs

## Risks / Trade-offs

- Supporting two deployment modes increases maintenance cost
  - Mitigation: one shared runtime contract and one compatibility matrix
- Some current services may not be ready for cluster-native scaling
  - Mitigation: document single-replica limitations explicitly at first
- Stateful services in Kubernetes can become a support burden
  - Mitigation: allow managed/external backends where practical

## Migration Plan

1. Freeze the cross-platform deployment contract
2. Productize the existing Docker path
3. Build the first Helm packaging skeleton
4. Add service probes, jobs, and external dependency support
5. Publish separate operator docs for Docker and Kubernetes

## Draft Operator Artifacts

This change now includes draft runbooks to make the upgrade discussion concrete before
implementation:

- `runbooks/docker-upgrade-runbook.md`
- `runbooks/kubernetes-upgrade-runbook.md`

## External References

- `Chatwoot` supports both Docker-based self-hosting and an official Helm chart:
  https://developers.chatwoot.com/self-hosted/deployment/docker
  https://developers.chatwoot.com/self-hosted/deployment/helm-chart
- `Langfuse` documents Kubernetes deployment through Helm and supports external infrastructure
  patterns:
  https://langfuse.com/self-hosting/deployment/kubernetes-helm
- `Open WebUI` clearly separates quick Docker setup from enterprise Helm deployment:
  https://docs.openwebui.com/getting-started/quick-start/
  https://docs.openwebui.com/enterprise/deployment/kubernetes-helm/
- `Supabase` is a strong reference for curated self-hosted Docker packaging:
  https://supabase.com/docs/guides/self-hosting/docker
- `n8n` documents multiple installation topologies instead of forcing a single operator model:
  https://docs.n8n.io/hosting/installation/server-setups/
