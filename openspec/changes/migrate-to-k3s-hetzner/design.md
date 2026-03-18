## Context

The AI Community Platform runs on a single Hetzner CX32 VPS (4 vCPU / 8 GB RAM, x86_64, Ubuntu).
Current deployment is Docker Compose with ~15 services. The existing Helm chart covers ~40% of the
stack. Goal: run the full stack on k3s with minimal downtime and no external dependencies.

Stakeholders: platform operator (single maintainer), CI/CD pipeline (GitHub Actions).

## Goals / Non-Goals

### Goals
- Run the full platform stack on k3s single-node cluster
- Use a local container registry (no external registry dependency)
- Migrate existing PostgreSQL data without loss
- Maintain CI/CD automation (push to main → deploy)
- Stay within 8 GB RAM budget

### Non-Goals
- Multi-node cluster or HA setup
- External managed services (RDS, ElastiCache, etc.)
- Helm chart repository publishing
- Auto-scaling (HPA) — single node, fixed resources
- Zero-downtime migration from Docker to k3s (planned maintenance window is acceptable)

## Decisions

### 1. k3s as Kubernetes distribution

**Decision:** Use k3s (not microk8s, kubeadm, or managed K8s).

**Why:** k3s is purpose-built for single-node and edge deployments. ~500 MB RAM overhead, single
binary install, built-in Traefik ingress controller, built-in local-path storage provisioner.
Production-grade CNCF-certified Kubernetes.

**Alternatives considered:**
- microk8s — heavier snap-based packaging, more suited for Ubuntu-native workflows
- kubeadm — full K8s control plane, unnecessary overhead for single node
- Hetzner managed K8s — requires separate node pools, more expensive, overkill for one VPS

### 2. Built-in Traefik as ingress controller

**Decision:** Use k3s default Traefik instead of installing nginx-ingress.

**Why:** Zero extra install, works out of the box on single node. Traefik supports standard
Kubernetes Ingress resources. The Helm chart currently specifies `className: nginx` — we change this
to `traefik` in `values-hetzner.yaml`. The chart's `ingress.className` is already configurable, so
no template changes needed.

**Trade-off:** Traefik IngressRoute CRDs offer more features, but standard Ingress API is sufficient
and keeps the chart portable.

### 3. Local container registry inside k3s

**Decision:** Deploy a registry as a Deployment+Service in k3s (port 5000), configured as a mirror
in `/etc/rancher/k3s/registries.yaml`.

**Why:** Eliminates GHCR dependency, images stay on the VPS, no network egress for pulls. Images are
built on the server with Docker (which remains installed for building) and pushed to `localhost:5000`.

**k3s registries.yaml configuration:**
```yaml
mirrors:
  "registry.localhost:5000":
    endpoint:
      - "http://registry.localhost:5000"
```

**Alternative considered:** k3s embedded registry (experimental) — not stable enough for production.

### 4. Bundled sub-charts for all stateful services

**Decision:** Use Bitnami sub-charts for PostgreSQL and Redis (already in Chart.yaml). Add
OpenSearch and RabbitMQ as optional sub-charts or standalone StatefulSets.

**Why:** Single-node VPS, no managed services available. Everything runs in-cluster. Bundled
sub-charts provide ready-made StatefulSets with persistence, probes, and sensible defaults.

**OpenSearch consideration:** Bitnami does not have an OpenSearch chart. Options:
- OpenSearch Helm chart from opensearch-project (official)
- Standalone StatefulSet in our chart
- **Decision:** Use the official OpenSearch Helm chart as a dependency

### 5. Image build strategy

**Decision:** Build images on the VPS using Docker, tag as `registry.localhost:5000/acp/<service>`,
push to local registry. k3s containerd pulls from local registry.

**Why:** Docker is already installed on the VPS for the current deployment. Building locally avoids
CI image push costs and bandwidth. The `deploy/build-and-push.sh` script automates this.

**CI/CD flow:**
1. GitHub Actions SSHes into VPS
2. `git pull` to get latest code
3. Run `deploy/build-and-push.sh` to build changed images and push to local registry
4. Run `helm upgrade --install` to deploy

### 6. TLS with cert-manager + Let's Encrypt

**Decision:** Install cert-manager in k3s, use ClusterIssuer with Let's Encrypt HTTP-01 challenge
via Traefik.

**Why:** Automated TLS renewal, no manual certificate management. Traefik handles the ACME challenge
routing.

## RAM Budget

Total: 8 GB. OS + k3s overhead: ~1 GB. Available for workloads: ~7 GB.

| Service | Requests | Limits | Notes |
|---------|----------|--------|-------|
| PostgreSQL (Bitnami) | 256 Mi | 512 Mi | Primary database |
| Redis (Bitnami) | 64 Mi | 128 Mi | Cache + sessions |
| OpenSearch | 512 Mi | 1 Gi | Reduced heap (`-Xms512m -Xmx512m`) |
| RabbitMQ | 128 Mi | 256 Mi | Message broker |
| Core | 256 Mi | 512 Mi | PHP/Symfony main app |
| Core Scheduler | 128 Mi | 256 Mi | Background scheduler |
| LiteLLM | 256 Mi | 384 Mi | LLM proxy |
| Knowledge Agent | 128 Mi | 256 Mi | PHP agent |
| Knowledge Worker | 128 Mi | 256 Mi | Message consumer |
| Hello Agent | 64 Mi | 128 Mi | PHP agent |
| Wiki Agent | 64 Mi | 128 Mi | Node.js agent |
| News Maker Agent | 128 Mi | 256 Mi | Python agent |
| Dev Reporter Agent | 64 Mi | 128 Mi | PHP agent |
| Langfuse (web+worker) | 256 Mi | 512 Mi | Observability |
| OpenClaw | 64 Mi | 128 Mi | Telegram gateway |
| Local Registry | 64 Mi | 128 Mi | Image storage |
| cert-manager | 32 Mi | 64 Mi | TLS automation |
| **Total** | **~2.7 Gi** | **~5.0 Gi** | Within 7 Gi budget |

Requests total ~2.7 Gi leaves ~4.3 Gi headroom for burst and OS page cache (critical for
OpenSearch and PostgreSQL performance).

## Risks / Trade-offs

- **8 GB is tight** → Mitigation: conservative resource requests, dev-agent disabled by default,
  monitor with `kubectl top`. Upgrade to CX42 (16 GB) if needed.
- **Single point of failure** → Mitigation: k3s auto-restarts pods, PostgreSQL PVC persists data,
  `helm rollback` for bad deploys. Not HA, but acceptable for current scale.
- **Downtime during migration** → Mitigation: planned maintenance window, pre-backup, tested
  procedure. Expected downtime: 30-60 minutes.
- **Local registry data loss on disk failure** → Mitigation: images can be rebuilt from source.
  Registry is a cache, not source of truth.
- **OpenSearch on 512 MB** → Mitigation: adequate for current index sizes. Monitor heap usage,
  upgrade VPS if indices grow.

## Migration Plan

### Pre-migration
1. Announce maintenance window
2. Backup PostgreSQL: `docker compose exec postgres pg_dumpall -U app > /root/pg-backup-$(date +%Y%m%d).sql`
3. Note current service versions and config

### Migration (30-60 min downtime)
1. Stop Docker Compose stack: `docker compose down`
2. Install k3s: `curl -sfL https://get.k3s.io | sh -`
3. Install Helm
4. Deploy local registry
5. Build and push all images
6. Create K8s namespace and secrets
7. `helm upgrade --install` with `values-hetzner.yaml`
8. Wait for PostgreSQL pod to be ready
9. Restore backup into k3s PostgreSQL
10. Verify all pods running, health endpoints responding

### Rollback
If k3s deployment fails:
1. `helm uninstall acp -n acp`
2. Stop k3s: `systemctl stop k3s`
3. Start Docker Compose: `docker compose up -d`
4. PostgreSQL data intact in Docker volumes (not removed)

## Open Questions

- Should we keep Docker installed alongside k3s for image building, or switch to buildah/nerdctl?
  **Current decision:** keep Docker, it's already there and works.
- Do we need Langfuse in the first deployment, or can it come in a follow-up iteration?
  **Current decision:** include it, it's part of the full stack scope.
