## ADDED Requirements

### Requirement: k3s Single-Node Cluster

The platform SHALL support deployment on a k3s single-node Kubernetes cluster on a Hetzner VPS
(minimum CX32: 4 vCPU / 8 GB RAM).

#### Scenario: Fresh k3s installation
- **WHEN** k3s is installed on a clean Ubuntu VPS with `curl -sfL https://get.k3s.io | sh -`
- **THEN** a single-node cluster is available with built-in Traefik ingress and local-path storage
- **AND** `kubectl get nodes` shows the node in Ready state

#### Scenario: k3s resource overhead
- **WHEN** k3s is running with no user workloads
- **THEN** total k3s system resource usage (control plane + system pods) SHALL NOT exceed 1 GB RAM

### Requirement: Local Container Registry

The platform SHALL deploy a local container registry inside the k3s cluster for storing platform
images without external registry dependencies.

#### Scenario: Registry deployment
- **WHEN** the registry Deployment is applied to the cluster
- **THEN** the registry SHALL be accessible at `registry.localhost:5000`
- **AND** k3s containerd SHALL be configured to pull from this registry via `/etc/rancher/k3s/registries.yaml`

#### Scenario: Image push and pull
- **WHEN** an image is built with Docker and pushed to `registry.localhost:5000/acp/<service>:<tag>`
- **THEN** k3s pods SHALL be able to pull that image by referencing the same registry path
- **AND** `curl http://registry.localhost:5000/v2/_catalog` SHALL list all pushed repositories

### Requirement: Full-Stack Helm Chart

The Helm chart at `deploy/charts/ai-community-platform/` SHALL cover all services from the
docker-compose stack.

#### Scenario: LiteLLM deployment
- **WHEN** `litellm.enabled: true` in values
- **THEN** the chart SHALL create a Deployment, Service (port 4000), and optional ConfigMap for LiteLLM
- **AND** the LiteLLM pod SHALL pass readiness checks on `/health`

#### Scenario: Knowledge worker deployment
- **WHEN** `agents.knowledge.enabled: true` and `agents.knowledge.worker.enabled: true` in values
- **THEN** the chart SHALL create a separate Deployment for the knowledge worker
- **AND** the worker SHALL use the same image as the knowledge agent but with command `php bin/console messenger:consume`

#### Scenario: Additional agents
- **WHEN** agents `wiki`, `devReporter`, or `devAgent` are enabled in values
- **THEN** the chart SHALL create Deployments and Services for each enabled agent
- **AND** each agent SHALL have configurable image, resources, probes, and secretRef

#### Scenario: OpenSearch sub-chart
- **WHEN** `opensearch.enabled: true` in values
- **THEN** the chart SHALL deploy OpenSearch as a sub-chart dependency
- **AND** the OpenSearch pod SHALL be accessible within the cluster

#### Scenario: RabbitMQ sub-chart
- **WHEN** `rabbitmq.enabled: true` in values
- **THEN** the chart SHALL deploy RabbitMQ as a sub-chart dependency
- **AND** the RabbitMQ pod SHALL be accessible within the cluster

#### Scenario: Langfuse observability
- **WHEN** `langfuse.enabled: true` in values
- **THEN** the chart SHALL create Deployments for Langfuse web and worker
- **AND** the Langfuse web UI SHALL be accessible via the ingress host

#### Scenario: OpenClaw gateway
- **WHEN** `openclaw.enabled: true` in values
- **THEN** the chart SHALL create a Deployment and Service for the OpenClaw gateway

#### Scenario: Agent-specific migrations
- **WHEN** `migrations.enabled: true` and agent migrations are configured
- **THEN** the migration Job SHALL run both core and agent-specific database migrations
- **AND** the Job SHALL complete before application pods start (pre-upgrade hook)

### Requirement: Traefik Ingress Compatibility

The Helm chart SHALL support both nginx and Traefik ingress controllers via the `ingress.className`
value.

#### Scenario: Traefik ingress on k3s
- **WHEN** `ingress.className: traefik` is set in values
- **THEN** the Ingress resource SHALL be routable through k3s built-in Traefik
- **AND** all configured hosts (core, litellm, langfuse, openclaw) SHALL resolve to the correct backend services

#### Scenario: TLS with cert-manager
- **WHEN** cert-manager is installed and `ingress.tls.enabled: true` with a ClusterIssuer annotation
- **THEN** TLS certificates SHALL be automatically provisioned via Let's Encrypt
- **AND** HTTPS SHALL be enforced for all ingress hosts

### Requirement: Resource Budget for 8 GB VPS

The `values-hetzner.yaml` SHALL define resource requests and limits that fit within 8 GB total RAM.

#### Scenario: Full stack within budget
- **WHEN** all services are deployed with `values-hetzner.yaml` resource settings
- **THEN** total resource requests SHALL NOT exceed 3 GB
- **AND** total resource limits SHALL NOT exceed 5.5 GB
- **AND** all pods SHALL reach Running state without OOMKill

### Requirement: PostgreSQL Data Migration

The migration procedure SHALL preserve existing PostgreSQL data from the Docker Compose deployment.

#### Scenario: Backup and restore
- **WHEN** `pg_dumpall` is run against the Docker Compose PostgreSQL container
- **AND** the dump is restored into the k3s-managed PostgreSQL pod
- **THEN** all databases, schemas, and data SHALL be present in the new PostgreSQL instance
- **AND** the core application SHALL function correctly with the restored data

### Requirement: CI/CD Deployment via Helm

The GitHub Actions deploy workflow SHALL use `helm upgrade` for deployments to the k3s cluster.

#### Scenario: Automated deploy on push to main
- **WHEN** code is pushed to the `main` branch
- **THEN** the workflow SHALL SSH into the VPS
- **AND** build changed images and push to local registry
- **AND** run `helm upgrade --install` with the appropriate values file
- **AND** wait for rollout to complete

#### Scenario: Rollback on failure
- **WHEN** a Helm upgrade fails (pods not reaching Ready state within timeout)
- **THEN** the operator SHALL be able to run `helm rollback acp <revision> -n acp`
- **AND** the previous working version SHALL be restored

### Requirement: Build and Push Script

The platform SHALL provide a `deploy/build-and-push.sh` script for building all platform images
and pushing them to the local registry.

#### Scenario: Build all images
- **WHEN** `deploy/build-and-push.sh` is run on the VPS
- **THEN** all platform Dockerfiles SHALL be built and tagged as `registry.localhost:5000/acp/<service>:<version>`
- **AND** all images SHALL be pushed to the local registry
- **AND** `curl http://registry.localhost:5000/v2/_catalog` SHALL list all platform images
