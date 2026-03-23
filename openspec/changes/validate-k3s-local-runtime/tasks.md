# Implementation Tasks

## 1. Validate Cluster Readiness
- [ ] 1.1 Confirm the Rancher Desktop k3s cluster is reachable
- [ ] 1.2 Confirm the target namespace exists
- [ ] 1.3 Confirm no critical system pods are failing

**Acceptance checks**
- `kubectl get nodes` shows all nodes `Ready`
- `kubectl get pods -A` shows no critical system pod in `CrashLoopBackOff`

## 2. Validate Infrastructure Layer
- [ ] 2.1 Validate PostgreSQL readiness
- [ ] 2.2 Validate Redis readiness
- [ ] 2.3 Validate RabbitMQ readiness
- [ ] 2.4 Validate OpenSearch readiness

**Acceptance checks**
- Each infra service has a documented readiness signal
- Each failing service has a documented inspection command such as `kubectl logs` or `kubectl describe pod`

## 3. Validate Core Runtime
- [ ] 3.1 Validate core pod readiness
- [ ] 3.2 Validate core health endpoint from inside or outside the cluster
- [ ] 3.3 Validate operator-facing access path such as ingress or port-forward

**Acceptance checks**
- Core health responds successfully
- Documented local access path works in a browser or with `curl`

## 4. Validate Reference Agent Runtime
- [ ] 4.1 Validate reference agent readiness
- [ ] 4.2 Validate reference agent health endpoint
- [ ] 4.3 Validate core-to-agent connectivity or discovery

**Acceptance checks**
- The reference agent responds successfully on its health endpoint
- There is evidence that core can reach the agent using the local k3s network path

## 5. Publish Verified Runbook
- [ ] 5.1 Capture the exact step order that worked on Rancher Desktop
- [ ] 5.2 Capture known issues and workarounds
- [ ] 5.3 Capture the minimum command sequence for re-validation

**Acceptance checks**
- A new operator can follow the runbook without relying on undocumented tribal knowledge
- The runbook includes both success criteria and failure inspection commands

