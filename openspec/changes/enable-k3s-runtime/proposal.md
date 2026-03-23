# Change: Enable Platform Runtime on Local k3s

## Why

The platform already has a fast single-node path via Docker Compose, but there is no verified local
cluster path for Kubernetes-style deployment. Rancher Desktop now provides a local k3s environment,
which makes it practical to build and verify a k3s-compatible runtime without waiting for a remote
production cluster.

To support k3s, the project needs a dedicated deployment layer, Kubernetes-friendly configuration
management, and a minimal verified path that proves the platform can boot outside Compose.

## What Changes

- **ADDED**: A local k3s deployment path for Rancher Desktop
- **ADDED**: k3s deployment assets under the workspace deployment layer
- **ADDED**: Secrets and configuration mapping strategy for Kubernetes
- **ADDED**: Stepwise acceptance criteria for booting infra, core, and at least one agent in k3s

## Impact

- Affected specs:
  - k3s-deployment
- Affected code:
  - deployment manifests or Helm values/templates for k3s
  - deployment documentation
- Affected runtime:
  - local Rancher Desktop k3s environment

