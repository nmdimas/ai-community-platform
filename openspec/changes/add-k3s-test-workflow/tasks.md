# Implementation Tasks

## 1. Define k3s Test Entry Points
- [ ] 1.1 Add a smoke-test entry point for k3s
- [ ] 1.2 Add an E2E-test entry point for k3s
- [ ] 1.3 Define required environment variables and endpoint assumptions

**Acceptance checks**
- There is a documented command for smoke tests
- There is a documented command for E2E tests
- The commands explain what must already be running in k3s

## 2. Add Smoke Validation Layer
- [ ] 2.1 Cover core health checks
- [ ] 2.2 Cover at least one authenticated or operator-facing path if available
- [ ] 2.3 Cover at least one reference agent endpoint

**Acceptance checks**
- Smoke tests fail clearly when core is unreachable
- Smoke tests fail clearly when the reference agent is unreachable
- Smoke tests pass against a healthy local k3s deployment

## 3. Add E2E Validation Layer
- [ ] 3.1 Reuse or adapt existing E2E tests for k3s-exposed URLs
- [ ] 3.2 Define a minimal k3s E2E subset before attempting full parity
- [ ] 3.3 Document known blockers for tests that still assume Compose-only topology

**Acceptance checks**
- At least one documented E2E subset runs against k3s
- The test workflow distinguishes smoke failures from browser-flow failures

## 4. Publish Troubleshooting and Expected Outputs
- [ ] 4.1 Document the expected success output for k3s smoke tests
- [ ] 4.2 Document how to inspect k3s deployment failures when tests fail
- [ ] 4.3 Document the boundary between deployment failures and test-environment failures

**Acceptance checks**
- A failed test run can be triaged into deployment, ingress, auth, or test-config categories

