# Documentation Index

Agent-facing memory index for `AI Community Platform`. Always load this file first.

## Product

- `docs/product/ua/brainstorm.md` — raw brainstorming and first product framing
- `docs/product/ua/platform-mvp-prd.md` — PRD for the platform MVP
- `docs/product/ua/architecture-overview.md` — baseline architecture, modularity model, and MVP boundaries
- `docs/product/ua/core-agent-openclaw.md` — OpenClaw as a runtime for the core-agent, not the core-platform

## Interface Specs

- `docs/specs/ua/README.md` — index of interface and protocol specifications
- `docs/specs/ua/web-requirements.md` — requirements for the public web layer
- `docs/specs/ua/admin-requirements.md` — requirements for the admin layer
- `docs/specs/ua/api-protocol.md` — API contract and mandatory OpenAPI documentation
- `docs/specs/ua/a2a-protocol.md` — internal agent-to-agent protocol

## Agent PRDs

- `docs/agents/ua/hello-agent.md` — Hello Agent
- `docs/agents/ua/knowledge-extractor-prd.md` — PRD for Community Wiki / Knowledge Extractor
- `docs/agents/ua/locations-catalog-prd.md` — PRD for the locations, services, and trusted contacts catalog
- `docs/agents/ua/news-digest-prd.md` — PRD for News Digest
- `docs/agents/ua/anti-fraud-signals-prd.md` — PRD for Anti-fraud Signals

## Agent Requirements

- `docs/agent-requirements/conventions.md` — baseline technical conventions for all agents
- `docs/agent-requirements/test-cases.md` — convention compliance test suite
- `docs/agent-requirements/e2e-testing.md` — end-to-end testing guide
- `docs/agent-requirements/observability-requirements.md` — tracing/logging requirements for multi-agent and multi-stack flows

## Architecture Decisions

- `docs/decisions/adr_0002_openclaw_role.md` — ADR for the OpenClaw positioning decision

## Delivery Plans

- `docs/plans/platform-mvp-development-plan.md` — overall platform delivery plan
- `docs/plans/knowledge-extractor-development-plan.md` — plan for Knowledge Extractor
- `docs/plans/locations-catalog-development-plan.md` — plan for Locations Catalog
- `docs/plans/news-digest-development-plan.md` — plan for News Digest
- `docs/plans/anti-fraud-signals-development-plan.md` — plan for Anti-fraud Signals
- `docs/plans/openclaw-observability-rollout-plan.md` — rollout plan for end-to-end observability via OpenClaw
- `docs/plans/telegram-openclaw-integration-plan.md` — Telegram-OpenClaw integration plan

## Frameworks

- `docs/neuron-ai/index.md` — Neuron AI framework documentation for building AI agents

## Templates

- `docs/templates/agent-prd-template.md` — template for new agent PRDs
- `docs/templates/development-plan-template.md` — template for development plans

## Local Runtime

- `LOCAL_DEV.md` — local Docker Compose development environment

## Recommended Build Order

1. Core platform
2. Knowledge Extractor
3. Locations Catalog
4. News Digest
5. Anti-fraud Signals
