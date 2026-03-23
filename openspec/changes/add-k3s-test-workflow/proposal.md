# Change: Add Automated Test Workflow for Local k3s Runtime

## Why

Manual validation is necessary to bring up a new runtime, but it is not sufficient to keep that
runtime stable. Once the platform can boot on local k3s, the next requirement is automated
verification so regressions are caught early.

The test strategy should be staged: smoke checks first, then browser-oriented or broader E2E checks
once ingress and auth flows are stable.

## What Changes

- **ADDED**: A dedicated automated test workflow for local k3s
- **ADDED**: Smoke checks as the first test layer for k3s
- **ADDED**: A path for broader E2E coverage against k3s-exposed endpoints
- **ADDED**: Acceptance criteria for command entry points and expected outputs

## Impact

- Affected specs:
  - k3s-testing
- Affected code:
  - k3s-focused scripts or make targets
  - test environment configuration
- Affected runtime:
  - local Rancher Desktop k3s deployment

