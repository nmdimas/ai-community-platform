# Agent Migration Playbook: Moving from apps/ to External Repository

This playbook describes how to move a bundled agent from `apps/<agent-name>/` to its own
external repository while preserving full platform compatibility.

## Pilot Agent: hello-agent

The `hello-agent` is the designated pilot for this migration. It was chosen because:

- Simplest bundled agent (PHP/Symfony, no database, no background workers)
- No storage provisioning required (no Postgres, Redis, or OpenSearch dependencies)
- Self-contained: no cross-agent dependencies
- Small codebase — easy to validate the full migration path before applying to larger agents

## Pre-Migration Checklist

Before migrating any agent, verify:

- [ ] Agent has no undocumented dependencies on platform-internal paths or shared volumes
- [ ] Agent manifest endpoint returns a valid Agent Card
- [ ] Agent health endpoint returns `{"status":"ok"}`
- [ ] Agent passes `make conventions-test`
- [ ] Agent has its own test suite that can run independently

## Migration Steps

### 1. Create the External Repository

```bash
# Create a new repository (e.g. on GitHub)
# Repository name convention: ai-community-platform-<agent-name>
# Example: ai-community-platform-hello-agent
```

### 2. Copy Agent Source

```bash
# In the platform repository
cp -r apps/hello-agent/ /tmp/hello-agent-export/

# In the new repository
git init
cp -r /tmp/hello-agent-export/* .
git add .
git commit -m "Initial import from ai-community-platform apps/hello-agent"
```

### 3. Add Dockerfile

The agent repository needs its own Dockerfile. Copy from the platform:

```bash
cp docker/hello-agent/Dockerfile .
```

Update the Dockerfile to use a self-contained build context (no references to platform root):

```dockerfile
FROM php:8.3-apache
# ... agent-specific build steps
COPY . /var/www/html
```

### 4. Add compose.fragment.yaml

Create `compose.fragment.yaml` in the agent repository root:

```yaml
services:
  hello-agent:
    build:
      context: ./projects/hello-agent
      dockerfile: Dockerfile
    labels:
      - ai.platform.agent=true
      - traefik.enable=true
      - traefik.http.routers.hello-agent.rule=PathPrefix(`/`)
      - traefik.http.routers.hello-agent.entrypoints=hello
      - traefik.http.routers.hello-agent.middlewares=edge-auth@docker
      - traefik.http.services.hello-agent.loadbalancer.server.port=80
    environment:
      LANGFUSE_ENABLED: "true"
      LANGFUSE_BASE_URL: http://langfuse-web:3000
      LANGFUSE_PUBLIC_KEY: lf_pk_local_dev
      LANGFUSE_SECRET_KEY: lf_sk_local_dev
      LANGFUSE_ENV: local
      LITELLM_BASE_URL: http://litellm:4000
      LITELLM_API_KEY: dev-key
      LLM_MODEL: minimax/minimax-m2.5
      OPENSEARCH_URL: http://opensearch:9200
    env_file:
      - path: ./projects/hello-agent/.env.local
        required: false
    depends_on:
      - opensearch
      - litellm
    networks:
      - dev-edge
```

### 5. Add .env.local.example

```bash
# =============================================================================
# hello-agent — Local Development Secrets
# =============================================================================
# Copy to .env.local and fill in your values.
# =============================================================================

# No secrets required for hello-agent in local dev.
# All configuration is provided via compose.fragment.yaml environment block.
```

### 6. Validate the External Checkout

```bash
# In the platform workspace
make external-agent-clone repo=https://github.com/your-org/hello-agent name=hello-agent
make external-agent-up name=hello-agent

# Verify
curl -s http://localhost:8085/health
curl -s http://localhost:8085/api/v1/manifest
make agent-discover
AGENT_URL=http://localhost:8085 make conventions-test
```

### 7. Update Platform Repository

Once the external checkout is validated:

1. Remove `apps/hello-agent/` from the platform repository
2. Remove `compose.agent-hello.yaml` from the platform repository
3. Remove `docker/hello-agent/` from the platform repository
4. Update `Makefile` to remove `hello-*` targets
5. Update `docs/` to reference the external repository
6. Add a note in `docs/guides/external-agents/` pointing to the agent repository

### 8. Compatibility Rules During Transition

While any agent is in transition (partially migrated), enforce:

| Rule | Reason |
|------|--------|
| No mixed service names | `hello-agent` in `apps/` and `projects/` cannot coexist |
| Stable manifest schema | `name` and `version` fields must not change during migration |
| Stable admin URLs | `admin_url` in manifest must remain the same |
| Stable A2A endpoint paths | `/api/v1/a2a` path must not change |

## Rollback

If the external checkout fails validation:

```bash
# Stop the external agent
make external-agent-down name=hello-agent

# Remove the fragment
rm compose.fragments/hello-agent.yaml

# Restore the bundled agent (if not yet removed from platform repo)
# The bundled compose.agent-hello.yaml is auto-discovered by the Makefile
make agent-up name=hello-agent
```

## Post-Migration

After a successful migration:

1. Archive the old `compose.agent-<name>.yaml` in git history (do not keep it)
2. Update `docs/guides/deployment/` to reference the external agent onboarding flow
3. Add the agent repository URL to the platform README
4. Tag the agent repository with the first stable release version
