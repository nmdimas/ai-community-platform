# Change: Validate Local k3s Runtime End-to-End

## Why

Rendering manifests is not enough. The project needs a reproducible validation path that proves the
local k3s runtime actually works in Rancher Desktop. Without this, the team can produce deployment
assets that look correct but fail on first real boot.

The purpose of this change is to convert the k3s deployment work into a verified operational path
with concrete checks at each stage.

## What Changes

- **ADDED**: A step-by-step local k3s validation workflow
- **ADDED**: Acceptance criteria for cluster health, infra health, core health, agent health, and local access
- **ADDED**: A verified runbook for local operator validation

## Impact

- Affected specs:
  - k3s-runtime-validation
- Affected docs:
  - local k3s validation guide
- Affected runtime:
  - local Rancher Desktop k3s environment

