# Change: Add standalone documentation and GHCR support to external agent repositories

## Why

Agents have been extracted into standalone GitHub repositories (a2a-hello-agent, a2a-wiki-agent)
with CI that publishes Docker images to GHCR. However, the agent repos themselves lack
documentation: no README, no compose.fragment.yaml, no .env.local.example, and no explanation
of how to connect the agent to the platform. The platform docs also need updating to reflect
the GHCR image-first approach (pull pre-built images instead of always building locally).

## What Changes

- Add README.md to each agent repository explaining:
  - what the agent does
  - how to run it standalone (for development)
  - how to connect it to the AI Community Platform
  - GHCR image usage
- Add compose.fragment.yaml to each agent repository (the integration contract)
- Add .env.local.example documenting all required environment variables
- Update platform onboarding docs to document the GHCR pull-first workflow:
  - `image: ghcr.io/nmdimas/<agent>:main` with `build:` fallback
  - when to pull vs when to build locally
- Update platform external-agents guide with real examples from hello-agent and wiki-agent

## Impact

- Affected repos: nmdimas/a2a-hello-agent, nmdimas/a2a-wiki-agent
- Affected platform docs: docs/guides/external-agents/
- Affected specs: external-agent-onboarding (MODIFIED — adds GHCR image contract)
- No breaking changes
