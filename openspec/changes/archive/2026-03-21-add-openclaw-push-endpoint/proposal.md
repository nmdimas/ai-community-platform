# Change: Add push message endpoint to OpenClaw Gateway

## Why

The platform's delivery channel abstraction (see `add-delivery-channels`) needs a way to push messages into Telegram chats via OpenClaw. Currently OpenClaw only handles inbound user messages and outbound LLM responses within a conversation session. There is no API for external systems to inject a message into a specific Telegram chat. This change adds a push endpoint to the OpenClaw Gateway plugin so Core can deliver scheduled job results, alerts, and notifications to Telegram users and groups.

## What Changes

- **New endpoint** in OpenClaw platform-tools plugin: `POST /api/v1/push` — accepts a platform push envelope and sends the message to the specified Telegram chat via OpenClaw's bot API
- **New auth token** `OPENCLAW_PUSH_TOKEN` — separate from `OPENCLAW_GATEWAY_TOKEN`, used exclusively for push authentication
- **Idempotency handling** — plugin tracks recent `idempotency_key` values in memory (LRU cache, 1h TTL) to prevent duplicate sends
- **Push payload validation** — validates required fields (`chat_id`, `content.body`), rejects malformed requests
- **OpenSearch logging** — all push attempts logged with `event_name: openclaw.push.*` events for admin visibility
- **Docker env update** — add `OPENCLAW_PUSH_TOKEN` to OpenClaw service env in compose files

## What Does NOT Change

- Existing tool discovery and invocation flow — unchanged
- Existing message lifecycle hooks (message:preprocessed, message:sent) — unchanged
- OpenClaw state management — push messages are stateless, no session created
- Telegram bot token or bot identity — same bot sends push messages

## Impact

- Affected specs: new capability `openclaw-push`
- Affected code:
  - `docker/openclaw/plugins/platform-tools/index.js` (modified — add push route handler)
  - `docker/openclaw/.env` (modified — add `OPENCLAW_PUSH_TOKEN`)
  - `compose.openclaw.yaml` (modified — pass new env var)
  - `compose.openclaw.multi-bot.yaml` (modified — pass new env var)
- Depends on: OpenClaw's internal `api.sendMessage()` or equivalent bot API method for sending to arbitrary chat IDs
