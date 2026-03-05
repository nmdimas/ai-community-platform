# Change: Add Admin Agent Registry

## Why

The platform architecture defines an Agent Registry that stores available agents, their manifests, config, and enabled/disabled state. Without a formal manifest contract and registry implementation, the admin panel cannot manage agents, and the Event Bus and Command Router have no source of truth for routing decisions. This proposal formalizes the agent manifest format, implements the platform-owned registry, and adds admin UI to control agent lifecycle.

## What Changes

- **Formal agent manifest schema** — JSON structure every agent must provide: `name`, `version`, `description`, `permissions`, `commands`, `events`, `config_schema`, `a2a_endpoint`, and `capabilities`
- **Agent Registry data model** — Postgres table `agent_registry` storing installed agents, their manifest, enabled state, resolved config, and audit timestamps
- **Registry API** — platform-internal endpoints to register, enable, disable, and read agent state; used by agent services at boot and by admin panel
- **Event Bus integration** — Event Bus checks agent registry before dispatching events; disabled agents receive no events
- **Command Router integration** — Command Router resolves agent commands only for enabled agents; unknown commands return graceful fallback
- **Admin UI: Agent Management page** — list all registered agents with status badges, enable/disable toggle, expand to view manifest and config schema, link to agent-specific settings (e.g., `/admin/knowledge` for knowledge-base agent)
- **knowledge-base agent manifest** — concrete manifest for `knowledge-base` agent wired to this registry

## Impact

- Affected specs: agent-manifest (new), agent-registry (new), admin-agent-management (new)
- Affected code: `apps/core/` (registry service, event bus, command router), `apps/knowledge-agent/` (manifest file), admin UI routes, Postgres migration
- Depends on: `add-admin-web-login` (admin auth must exist), `bootstrap-platform-foundation` (Symfony + Doctrine in place)
- Extends: `add-knowledge-base-agent` (knowledge-base agent registers itself via this registry)
- No breaking changes to existing platform contracts
