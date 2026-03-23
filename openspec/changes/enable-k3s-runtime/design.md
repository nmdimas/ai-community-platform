## Context

The project currently has a validated Docker Compose runtime and a devcontainer that reuses that
runtime. The next step is not to replace Compose immediately, but to add a separate local k3s path
that can be booted, observed, and tested step by step in Rancher Desktop.

The highest risk is not authoring manifests. The highest risk is building a large k3s layer that
cannot be validated incrementally. This change therefore optimizes for staged verification:

1. cluster access
2. namespace and shared config
3. infrastructure services
4. core runtime
5. one reference agent
6. ingress and operator access

## Decisions

### 1. Local k3s is a separate runtime, not a replacement for Compose

Compose remains the fastest path on a single machine. k3s is added as a second runtime for
Kubernetes-oriented validation and deployment work.

### 2. Initial k3s scope is minimal but real

The first verified slice should include:
- namespace
- shared config and secrets
- postgres
- redis
- rabbitmq
- opensearch
- core
- one lightweight reference agent such as hello-agent

### 3. Verification is mandatory after each layer

Every deployment layer must be accepted independently:
- manifests render
- resources apply
- pods reach healthy state
- endpoints respond
- cross-service connectivity works

## Open Questions

- Whether the first iteration should use plain manifests or the existing chart as the source of truth
- Which ingress exposure path is simplest in Rancher Desktop for browser-based validation

