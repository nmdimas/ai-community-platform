## ADDED Requirements

### Requirement: Telegram Webhook Receiver
The platform SHALL expose a webhook endpoint at `POST /api/v1/webhook/telegram/{bot_id}` that receives Telegram Bot API Update objects and enqueues them for processing.

#### Scenario: Valid webhook update received
- **WHEN** Telegram sends a POST request with a valid Update JSON body and correct `X-Telegram-Bot-Api-Secret-Token` header
- **THEN** the platform returns `200 OK` immediately
- **AND** the update is enqueued for normalization and dispatch

#### Scenario: Invalid webhook secret
- **WHEN** a POST request arrives with an incorrect or missing `X-Telegram-Bot-Api-Secret-Token` header
- **THEN** the platform returns `401 Unauthorized`
- **AND** no processing occurs

#### Scenario: Unknown bot_id
- **WHEN** a POST request arrives for a `{bot_id}` not registered in `telegram_bots` table
- **THEN** the platform returns `404 Not Found`

#### Scenario: Duplicate update_id
- **WHEN** an update arrives with an `update_id` already processed for this bot
- **THEN** the platform returns `200 OK` without re-processing (idempotent)

---

### Requirement: Telegram Update Normalizer
The platform SHALL normalize raw Telegram Update objects into platform-agnostic `NormalizedEvent` DTOs containing: `event_type`, `platform`, `bot_id`, `chat`, `sender`, `message`, `trace_id`, `request_id`, and `raw_update_id`.

#### Scenario: Text message normalized
- **WHEN** a Telegram Update contains a `message` with `text` field
- **THEN** the normalizer produces a `NormalizedEvent` with `event_type: "message_created"`, sender details, chat details including `thread_id` if present, and the message text

#### Scenario: Edited message normalized
- **WHEN** a Telegram Update contains an `edited_message` field
- **THEN** the normalizer produces a `NormalizedEvent` with `event_type: "message_edited"` and both old and new message content where available

#### Scenario: Bot command detected
- **WHEN** a Telegram Update contains a message with a `bot_command` entity
- **THEN** the normalizer produces a `NormalizedEvent` with `event_type: "command_received"` and parses the command name and arguments from the entity offset

#### Scenario: Member joined
- **WHEN** a Telegram Update contains `new_chat_members`
- **THEN** the normalizer produces one `NormalizedEvent` per member with `event_type: "member_joined"`

#### Scenario: Member left
- **WHEN** a Telegram Update contains `left_chat_member`
- **THEN** the normalizer produces a `NormalizedEvent` with `event_type: "member_left"`

#### Scenario: Forum topic message
- **WHEN** a Telegram Update contains a message with `message_thread_id` and `is_topic_message: true`
- **THEN** the normalizer includes `chat.thread_id` with the forum topic ID

#### Scenario: Media message
- **WHEN** a Telegram Update contains a message with media (photo, document, video, voice, sticker)
- **THEN** the normalizer sets `message.has_media: true` and `message.media_type` to the media kind
- **AND** extracts `caption` as the message text if present

---

### Requirement: Telegram Bot Registry
The platform SHALL maintain a `telegram_bots` database table that stores bot configurations including encrypted tokens, webhook secrets, community assignments, and per-bot settings. The `TelegramBotRegistry` service SHALL provide lookup by `bot_id`.

#### Scenario: Bot configuration loaded
- **WHEN** the platform receives a webhook for a registered and enabled bot
- **THEN** the `TelegramBotRegistry` returns the bot configuration with decrypted token and webhook secret for validation

#### Scenario: Disabled bot rejected
- **WHEN** the platform receives a webhook for a registered but disabled bot
- **THEN** the platform returns `200 OK` but does not process the update

---

### Requirement: Telegram Chat Tracker
The platform SHALL track all chats where the bot is present in a `telegram_chats` table, updating metadata (title, type, thread support, member count, last activity) as events are received.

#### Scenario: Bot added to new group
- **WHEN** the bot receives a `member_joined` event where the new member is itself
- **THEN** a new chat record is created with `joined_at` timestamp, chat type, and title

#### Scenario: Bot removed from group
- **WHEN** the bot receives a `member_left` event where the left member is itself
- **THEN** the chat record is updated with `left_at` timestamp and processing for this chat stops

#### Scenario: Chat metadata updated
- **WHEN** a message arrives from a tracked chat with updated title or new thread support
- **THEN** the chat record is updated with current metadata

---

### Requirement: Polling Mode for Local Development
The platform SHALL provide a CLI command `app:telegram:poll` that uses Telegram's `getUpdates` API for long-polling, feeding updates into the same normalization pipeline as the webhook receiver.

#### Scenario: Polling mode receives messages
- **WHEN** the `app:telegram:poll` command is running and a user sends a message in the bot's chat
- **THEN** the message is received via `getUpdates`, normalized, and dispatched through the Event Bus

#### Scenario: Graceful shutdown
- **WHEN** the polling command receives SIGINT or SIGTERM
- **THEN** the current polling cycle completes and the command exits cleanly

---

### Requirement: Webhook Management CLI
The platform SHALL provide CLI commands for managing Telegram webhooks: `app:telegram:set-webhook`, `app:telegram:delete-webhook`, and `app:telegram:webhook-info`.

#### Scenario: Webhook registered
- **WHEN** an admin runs `app:telegram:set-webhook --bot=main`
- **THEN** the platform calls Telegram's `setWebhook` API with the configured URL, secret token, and allowed update types
- **AND** the webhook URL is stored in the bot's database record

#### Scenario: Webhook info displayed
- **WHEN** an admin runs `app:telegram:webhook-info --bot=main`
- **THEN** the platform calls Telegram's `getWebhookInfo` API and displays the URL, pending update count, last error date, and max connections
