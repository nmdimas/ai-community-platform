# Change: Add Telegram Bot Integration to OpenClaw

## Why

We need a unified entry point for users to interact with our agents via Telegram. OpenClaw is the central orchestrator (core-agent layer) responsible for interfacing with users, understanding intent, and delegating tasks to specialized agents registered in the Core platform. This integration binds the Telegram API to OpenClaw and establishes the end-to-end flow from user message to backend agent execution (e.g., calling the `hello-world` agent).

## What Changes

- **Telegram Channel Setup:** Configuration instructions and `.env` scaffolding for connecting OpenClaw to a Telegram Bot via the BotFather token.
- **Webhook/Polling Configuration:** Support for long-polling in local dev and webhooks in production.
- **System Prompt Definition:** A base system prompt for OpenClaw instructing it on its persona, how to handle Telegram formatting, and how to utilize tools (discovered agents).
- **Core Call Configuration:** Validating that OpenClaw is authorized to communicate with Core's `/api/v1/agents/invoke` endpoint when dispatching requests (A2A bridge).

## Impact

- Affected specs: `telegram-integration` (new)
- Affected code: `docker/openclaw/docker-compose.yml`, `docker/openclaw/.env.example`
- Depends on: `add-openclaw-agent-discovery` (OpenClaw needs to discover the tools), `add-hello-world-agent` (we need a mock agent to test the e2e flow).
