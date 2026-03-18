## ADDED Requirements

### Requirement: Push Message Endpoint
The OpenClaw Gateway SHALL expose a `POST /api/v1/push` endpoint that accepts a platform push envelope and delivers the message content to the specified Telegram chat.

#### Scenario: Successful push to Telegram chat
- **WHEN** Core sends a valid push request with `chat_id`, `content.type`, and `content.body`
- **THEN** OpenClaw SHALL send the message to the specified Telegram chat via the bot API
- **AND** OpenClaw SHALL return HTTP 200 with `{ "status": "delivered", "message_id": "<telegram_msg_id>" }`

#### Scenario: Push to invalid chat ID
- **WHEN** Core sends a push request with a `chat_id` that the bot cannot reach (not a member, blocked, invalid)
- **THEN** OpenClaw SHALL return HTTP 422 with `{ "status": "failed", "error": "<telegram_error>" }`
- **AND** the failure SHALL be logged to OpenSearch

#### Scenario: Push with missing required fields
- **WHEN** Core sends a push request without `chat_id` or `content.body`
- **THEN** OpenClaw SHALL return HTTP 400 with `{ "status": "failed", "error": "validation_error", "details": [...] }`

### Requirement: Push Authentication
The push endpoint SHALL require a dedicated `OPENCLAW_PUSH_TOKEN` for authentication, separate from the existing `OPENCLAW_GATEWAY_TOKEN`.

#### Scenario: Valid push token
- **WHEN** a request to `/api/v1/push` includes `Authorization: Bearer <OPENCLAW_PUSH_TOKEN>`
- **THEN** OpenClaw SHALL accept and process the request

#### Scenario: Invalid or missing push token
- **WHEN** a request to `/api/v1/push` has no Authorization header or an invalid token
- **THEN** OpenClaw SHALL return HTTP 401 with `{ "status": "failed", "error": "unauthorized" }`

#### Scenario: Gateway token used for push
- **WHEN** a request to `/api/v1/push` uses `OPENCLAW_GATEWAY_TOKEN` instead of `OPENCLAW_PUSH_TOKEN`
- **THEN** OpenClaw SHALL reject the request with HTTP 401
- **AND** this ensures token blast radius containment

### Requirement: Push Idempotency
The push endpoint SHALL support idempotent delivery using the `idempotency_key` field in the request payload.

#### Scenario: First push with idempotency key
- **WHEN** a push request includes an `idempotency_key` not seen before
- **THEN** OpenClaw SHALL deliver the message and cache the key

#### Scenario: Duplicate push with same idempotency key
- **WHEN** a push request includes an `idempotency_key` that was already processed (within 1h TTL)
- **THEN** OpenClaw SHALL return HTTP 200 with `{ "status": "duplicate" }` without re-sending
- **AND** the duplicate attempt SHALL be logged

### Requirement: Push Observability
All push attempts SHALL be logged to OpenSearch with structured event data for admin visibility.

#### Scenario: Push attempt logged
- **WHEN** any push request is received (successful, failed, duplicate, or unauthorized)
- **THEN** OpenClaw SHALL write a log entry to OpenSearch with `event_name` prefixed `openclaw.push.`
- **AND** the log entry SHALL include `chat_id`, `status`, `duration_ms`, `trace_id`, and `idempotency_key`

### Requirement: Push Payload Format
The push endpoint SHALL accept a standardized payload envelope with content type declaration and trace context.

#### Scenario: Text content push
- **WHEN** a push request has `content.type = "text"` and `content.body = "Hello"`
- **THEN** OpenClaw SHALL send a plain text message to the Telegram chat

#### Scenario: Markdown content push
- **WHEN** a push request has `content.type = "markdown"` and `content.body` contains Markdown
- **THEN** OpenClaw SHALL send the message with Telegram MarkdownV2 formatting enabled

#### Scenario: Push with metadata
- **WHEN** a push request includes `content.metadata` with agent/skill/job context
- **THEN** OpenClaw SHALL include this metadata in the OpenSearch log entry
- **AND** the metadata SHALL NOT be included in the Telegram message body
