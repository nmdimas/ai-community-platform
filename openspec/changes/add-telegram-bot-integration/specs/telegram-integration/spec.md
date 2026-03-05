# Telegram Integration

This document holds the specifications for OpenClaw's Telegram Channel Integration.

## ADDED Requirements

### Requirement: OpenClaw MUST serve as the unified Telegram entry point

OpenClaw SHALL act as the single backend receiver for all Telegram channel traffic, managing the chat history and session context for the platform.

#### Scenario: Local polling

The operator configures `TELEGRAM_BOT_TOKEN` in the OpenClaw `.env` file without setting a webhook URL. OpenClaw connects to Telegram using its built-in long-polling mode, meaning traffic does not require an exposed public port/webhook in local development.

#### Scenario: Production webhook

The operator deploys OpenClaw with `TELEGRAM_BOT_TOKEN` and `TELEGRAM_WEBHOOK_URL`. OpenClaw automatically registers this webhook and processes incoming HTTPS pushes from Telegram via Traefik.

### Requirement: Orchestrator MUST identify intent and use core agents as tools

OpenClaw SHALL utilize its system prompt and LLM reasoning to map natural language requests from users to the appropriate registered capabilities exposed by Core agents.

#### Scenario: Hello world tool usage

A user sends "what do you want to say to this world?" via Telegram. OpenClaw receives the message, reads its system prompt, interprets the intent, and matches the request to the dynamically discovered `hello-world` agent tool from Core (provided by `add-openclaw-agent-discovery`).

#### Scenario: Execution wrapper

OpenClaw dispatches the payload via the A2A proxy (`/api/v1/agents/invoke`). The request is authenticated and routed to the `hello-world` agent. OpenClaw receives the underlying response ("Hello, World!"), wraps it into a native, natural-language Telegram reply, and dispatches it back to the chat.
