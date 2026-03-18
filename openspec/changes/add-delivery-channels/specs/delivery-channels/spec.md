## ADDED Requirements

### Requirement: Delivery Channel Registry
The platform SHALL maintain a registry of outbound delivery channels in the `delivery_channels` database table. Each channel SHALL have a unique name, type, endpoint URL, authentication configuration, and capability declaration.

#### Scenario: Admin creates a new delivery channel
- **WHEN** an admin submits a new channel via the admin UI or API with name, type, endpoint, and auth config
- **THEN** the platform SHALL persist the channel in `delivery_channels`
- **AND** the channel SHALL be enabled by default

#### Scenario: Admin disables a delivery channel
- **WHEN** an admin disables a channel
- **THEN** the platform SHALL set `enabled = FALSE` on the channel record
- **AND** the `DeliveryService` SHALL skip disabled channels during delivery attempts

### Requirement: Channel Adapter Interface
The platform SHALL define a `ChannelAdapterInterface` with a `send(DeliveryPayload): DeliveryResult` method. Each supported transport type SHALL have a corresponding adapter implementation.

#### Scenario: Delivery to a channel with a registered adapter
- **WHEN** `DeliveryService` receives a delivery request targeting a channel of type `T`
- **THEN** the service SHALL resolve the adapter for type `T`
- **AND** the adapter SHALL format the payload according to the transport's requirements
- **AND** the adapter SHALL POST to the channel's configured endpoint

#### Scenario: Delivery to a channel with unknown type
- **WHEN** `DeliveryService` receives a delivery request for a channel whose type has no registered adapter
- **THEN** the service SHALL return a `DeliveryResult` with status `failed` and error `unknown_channel_type`
- **AND** the failure SHALL be logged in `delivery_log`

### Requirement: Webhook Adapter
The platform SHALL include a `WebhookAdapter` for generic HTTP POST delivery with configurable authentication (Bearer token, HMAC signature, or none).

#### Scenario: Delivery via webhook with Bearer auth
- **WHEN** a delivery targets a channel of type `webhook` with `auth_scheme = bearer`
- **THEN** the adapter SHALL POST the delivery payload to the endpoint with `Authorization: Bearer <token>` header
- **AND** the adapter SHALL include `X-Idempotency-Key` header

#### Scenario: Delivery via webhook with HMAC auth
- **WHEN** a delivery targets a channel of type `webhook` with `auth_scheme = hmac`
- **THEN** the adapter SHALL compute HMAC-SHA256 of the request body using the channel's `auth_token`
- **AND** the adapter SHALL include the signature in `X-Signature-256` header

### Requirement: OpenClaw Adapter
The platform SHALL include an `OpenClawAdapter` that pushes messages to a Telegram chat via the OpenClaw Gateway push endpoint.

#### Scenario: Delivery via OpenClaw
- **WHEN** a delivery targets a channel of type `openclaw`
- **THEN** the adapter SHALL POST the platform push envelope to the OpenClaw push endpoint
- **AND** the payload SHALL include `chat_id`, `content`, and `idempotency_key`

### Requirement: Slack Adapter
The platform SHALL include a `SlackAdapter` that delivers messages via Slack Incoming Webhooks.

#### Scenario: Delivery via Slack
- **WHEN** a delivery targets a channel of type `slack`
- **THEN** the adapter SHALL POST a Slack-formatted payload (Block Kit JSON) to the webhook URL
- **AND** text content SHALL be converted to Slack mrkdwn format

### Requirement: Teams Adapter
The platform SHALL include a `TeamsAdapter` that delivers messages via Microsoft Teams Incoming Webhooks.

#### Scenario: Delivery via Teams
- **WHEN** a delivery targets a channel of type `teams`
- **THEN** the adapter SHALL POST an Adaptive Card JSON payload to the webhook URL
- **AND** markdown content SHALL be rendered within the card body

### Requirement: Delivery Idempotency
Every delivery attempt SHALL carry an `idempotency_key`. The `delivery_log` table SHALL enforce uniqueness on this key. `DeliveryService` SHALL check for existing entries before sending.

#### Scenario: Duplicate delivery attempt
- **WHEN** `DeliveryService` receives a delivery with an `idempotency_key` that already exists in `delivery_log`
- **THEN** the service SHALL NOT send the message again
- **AND** the service SHALL return a `DeliveryResult` with status `duplicate`

#### Scenario: First delivery attempt
- **WHEN** `DeliveryService` receives a delivery with a new `idempotency_key`
- **THEN** the service SHALL proceed with delivery
- **AND** the service SHALL record the attempt in `delivery_log` with the key

### Requirement: Per-Channel Rate Limiting
Each delivery channel SHALL have a configurable `rate_limit_per_minute`. `DeliveryService` SHALL enforce this limit using a token-bucket algorithm before attempting delivery.

#### Scenario: Rate limit exceeded
- **WHEN** a delivery request arrives for a channel that has exhausted its per-minute quota
- **THEN** the service SHALL return a `DeliveryResult` with status `rate_limited`
- **AND** the service SHALL log the rate-limited attempt in `delivery_log`

#### Scenario: Rate limit not exceeded
- **WHEN** a delivery request arrives for a channel within its rate limit
- **THEN** the service SHALL proceed with delivery normally

### Requirement: Delivery Audit Log
Every delivery attempt (successful, failed, rate-limited, or duplicate) SHALL be recorded in the `delivery_log` table with channel reference, status, timing, content preview, trace context, and error details.

#### Scenario: Successful delivery logged
- **WHEN** a delivery completes successfully
- **THEN** the platform SHALL insert a `delivery_log` entry with status `delivered`, `duration_ms`, and `external_message_id` if returned by the transport

#### Scenario: Failed delivery logged
- **WHEN** a delivery fails (HTTP error, timeout, adapter exception)
- **THEN** the platform SHALL insert a `delivery_log` entry with status `failed` and `error_message`

### Requirement: Delivery Channels Admin UI
The admin panel SHALL include a "Канали доставки" page for managing delivery channels and viewing delivery logs.

#### Scenario: Admin views channel list
- **WHEN** an admin navigates to `/admin/delivery-channels`
- **THEN** the page SHALL display all channels with name, type, endpoint, enabled status, and delivery stats

#### Scenario: Admin sends test message
- **WHEN** an admin clicks "Тест" on a channel row
- **THEN** the platform SHALL send a test message through the channel
- **AND** the result (success or error) SHALL be displayed inline

#### Scenario: Admin views delivery logs
- **WHEN** an admin navigates to `/admin/delivery-channels/{id}/logs`
- **THEN** the page SHALL display paginated delivery log entries with status badges, timing, and content preview
