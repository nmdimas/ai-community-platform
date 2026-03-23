# Implementation Tasks

## 1. Prepare Local k3s Target
- [ ] 1.1 Document Rancher Desktop prerequisites for local k3s
- [ ] 1.2 Define the expected kube context for local validation
- [ ] 1.3 Create the target namespace and shared labels or naming conventions

**Acceptance checks**
- `kubectl config current-context` points to the expected Rancher Desktop context
- `kubectl get nodes` shows at least one `Ready` node
- `kubectl get namespace <target>` succeeds after namespace creation

## 2. Add Shared Config and Secrets Model
- [ ] 2.1 Define ConfigMap strategy for non-secret runtime values
- [ ] 2.2 Define Secret strategy for credentials and sensitive runtime values
- [ ] 2.3 Ensure the mapping from current `.env` values to k3s config is documented

**Acceptance checks**
- `kubectl apply -f` for shared config resources succeeds without schema errors
- `kubectl get configmap` and `kubectl get secret` show the expected shared resources

## 3. Boot Infrastructure Services
- [ ] 3.1 Add deployment assets for PostgreSQL
- [ ] 3.2 Add deployment assets for Redis
- [ ] 3.3 Add deployment assets for RabbitMQ
- [ ] 3.4 Add deployment assets for OpenSearch

**Acceptance checks**
- All infrastructure pods reach `Running` or equivalent healthy state
- `kubectl get pods -n <target>` shows no `CrashLoopBackOff` for the infra slice
- Application services can later resolve infra endpoints through cluster DNS

## 4. Boot Core Runtime
- [ ] 4.1 Add deployment assets for the core service
- [ ] 4.2 Add service exposure for the core HTTP surface
- [ ] 4.3 Add readiness and liveness verification requirements

**Acceptance checks**
- Core pod reaches ready state
- Core service is reachable from inside the cluster
- Core health endpoint returns a successful response

## 5. Boot One Reference Agent
- [ ] 5.1 Add deployment assets for a lightweight reference agent
- [ ] 5.2 Connect the agent to required backing services
- [ ] 5.3 Verify core-to-agent connectivity

**Acceptance checks**
- The reference agent pod reaches ready state
- The reference agent health endpoint returns a successful response
- Core can reach the reference agent over cluster networking

## 6. Expose and Document Operator Access
- [ ] 6.1 Define ingress or port-forward path for local operator access
- [ ] 6.2 Document the verified local URLs
- [ ] 6.3 Document known gaps and temporary workarounds

**Acceptance checks**
- At least one browser-reachable URL for core is documented and working
- The documented access path works on Rancher Desktop without undocumented manual steps

