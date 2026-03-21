## Context

The platform needs outbound message delivery for scheduled jobs, alerts, and agent-initiated notifications. Today's architecture is purely reactive — agents only respond to user-initiated requests via OpenClaw. Multiple delivery transports (Telegram, Slack, Teams, WhatsApp, SMS, webhooks) must be supported without coupling agents or the scheduler to any specific transport.

## Goals / Non-Goals

- Goals:
  - Channel-agnostic delivery abstraction — agents and scheduler never know which transport is used
  - Pluggable adapter architecture — adding a new channel = one class implementing `ChannelAdapterInterface`
  - Full audit trail of every delivery attempt
  - Idempotent delivery — safe retries without duplicate messages
  - Per-channel rate limiting to respect external API quotas
  - Admin UI for channel management and delivery monitoring
- Non-Goals:
  - Two-way conversation via push (push is fire-and-forget, not a chat session)
  - Rich media beyond text/markdown/card (no file uploads, no voice)
  - Channel auto-discovery or OAuth flows for Slack/Teams setup
  - Replacing OpenClaw's existing inbound → outbound flow

## Decisions

### 1. Adapter pattern with interface contract
- **Decision**: Each channel type implements `ChannelAdapterInterface::send(DeliveryPayload): DeliveryResult`
- **Why**: Clean separation, easy to test, easy to add new channels. Core's `DeliveryService` resolves the adapter at runtime based on `channel.type`.
- **Alternatives considered**: Strategy pattern with closures (less testable), event-based dispatch (over-engineered for synchronous HTTP calls).

### 2. Channel registry in database, not config files
- **Decision**: Channels stored in `delivery_channels` table, managed via admin UI.
- **Why**: Channels are operational data (endpoints change, tokens rotate, channels get added/removed). Config files would require redeployment for every change.
- **Alternatives considered**: YAML config (requires redeploy), env vars (doesn't scale to N channels).

### 3. Idempotency at delivery layer
- **Decision**: Every delivery carries an `idempotency_key`. `delivery_log` has a unique index on it. DeliveryService checks before sending.
- **Why**: Scheduler retries and network failures can cause duplicate dispatches. Dedup at delivery layer is the last line of defense.

### 4. Per-channel auth tokens
- **Decision**: Each channel record stores its own `auth_token` (encrypted at rest in future). Never reuse `OPENCLAW_GATEWAY_TOKEN`.
- **Why**: Blast radius containment. Compromised Slack webhook token shouldn't affect Telegram delivery.

### 5. Rate limiting per channel
- **Decision**: Simple token-bucket per channel (configurable `rate_limit_per_minute` column). DeliveryService checks before send, returns `rate_limited` status if exceeded.
- **Why**: External APIs (Telegram, Slack, Twilio) have rate limits. Platform should respect them proactively rather than hammering until 429.

## Channel Adapter Matrix

| Adapter | Type | Auth | Payload Format | Notes |
|---------|------|------|----------------|-------|
| `WebhookAdapter` | `webhook` | Bearer / HMAC / none | Raw JSON POST | Generic fallback |
| `OpenClawAdapter` | `openclaw` | Bearer token | Platform push envelope | Requires `add-openclaw-push-endpoint` |
| `SlackAdapter` | `slack` | Webhook URL has token | Slack Block Kit JSON | Incoming Webhook |
| `TeamsAdapter` | `teams` | Webhook URL has token | Adaptive Card JSON | Incoming Webhook |

Future adapters (not in this change): `WhatsAppAdapter`, `SmsAdapter`, `EmailAdapter`.

## Data Model

### delivery_channels

| Column | Type | Notes |
|--------|------|-------|
| id | UUID PK | |
| name | VARCHAR(128) NOT NULL | Human-readable label |
| type | VARCHAR(32) NOT NULL | `webhook`, `openclaw`, `slack`, `teams` |
| endpoint | TEXT NOT NULL | URL to POST to |
| auth_scheme | VARCHAR(32) DEFAULT 'bearer' | `bearer`, `hmac`, `none` |
| auth_token | TEXT | Per-channel secret |
| capabilities | JSONB DEFAULT '["text"]' | `["text","markdown","card"]` |
| rate_limit_per_minute | INTEGER DEFAULT 60 | Token bucket limit |
| enabled | BOOLEAN DEFAULT TRUE | |
| metadata | JSONB DEFAULT '{}' | Channel-specific config |
| created_at | TIMESTAMPTZ DEFAULT now() | |
| updated_at | TIMESTAMPTZ DEFAULT now() | |

### delivery_log

| Column | Type | Notes |
|--------|------|-------|
| id | UUID PK | |
| channel_id | UUID FK → delivery_channels | |
| idempotency_key | VARCHAR(256) NOT NULL UNIQUE | Dedup key |
| status | VARCHAR(32) NOT NULL | `delivered`, `failed`, `rate_limited`, `duplicate` |
| content_type | VARCHAR(32) | `text`, `markdown`, `card` |
| content_preview | TEXT | First 500 chars |
| target_address | VARCHAR(256) | chat_id / channel / phone |
| external_message_id | VARCHAR(256) | ID returned by transport |
| error_message | TEXT | On failure |
| trace_id | VARCHAR(64) | Correlation |
| request_id | VARCHAR(64) | |
| duration_ms | INTEGER | |
| created_at | TIMESTAMPTZ DEFAULT now() | |

## Risks / Trade-offs

- Adapters make synchronous HTTP calls — if external service is slow, delivery blocks.
  - Mitigation: per-adapter timeout (configurable, default 10s). Future: async dispatch via ReactPHP if volume demands it.
- Auth tokens stored in DB — needs encryption at rest for production.
  - Mitigation: phase 1 stores plaintext (same as current `OPENCLAW_GATEWAY_TOKEN` in env). Phase 2: add column-level encryption.
- Rate limiter is in-memory per process — not shared across multiple Core instances.
  - Mitigation: acceptable for single-instance MVP. Future: Redis-backed rate limiter.
