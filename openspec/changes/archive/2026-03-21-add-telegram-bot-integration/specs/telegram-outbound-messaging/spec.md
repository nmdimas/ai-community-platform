## ADDED Requirements

### Requirement: Direct Telegram Message Sending
The platform SHALL provide a `TelegramSender` service capable of sending messages to any Telegram chat or thread where the bot is present, supporting text, MarkdownV2, and HTML formatting.

#### Scenario: Send text message to group
- **WHEN** the platform sends a message to a tracked group chat
- **THEN** `TelegramSender` calls the Telegram Bot API `sendMessage` method with the `chat_id` and `text`
- **AND** the sent message's `message_id` is returned for reference

#### Scenario: Send reply to specific message
- **WHEN** the platform sends a message with `reply_to_message_id` specified
- **THEN** the Telegram message is sent as a reply to the referenced message

#### Scenario: Send to forum topic
- **WHEN** the platform sends a message with `thread_id` specified
- **THEN** the message is posted in the specified forum topic using `message_thread_id`

#### Scenario: Message exceeds 4096 characters
- **WHEN** the outbound message text exceeds Telegram's 4096 character limit
- **THEN** the message is split at paragraph or sentence boundaries
- **AND** each part is sent as a separate message in order

---

### Requirement: Telegram Message Formatting
The platform SHALL format outbound messages using Telegram MarkdownV2 with proper character escaping, falling back to HTML parse mode when MarkdownV2 formatting fails.

#### Scenario: MarkdownV2 formatting applied
- **WHEN** an outbound message contains markdown formatting (bold, italic, code blocks, links)
- **THEN** special characters are escaped per Telegram MarkdownV2 rules (`_`, `*`, `[`, `]`, `(`, `)`, `~`, `` ` ``, `>`, `#`, `+`, `-`, `=`, `|`, `{`, `}`, `.`, `!` outside of formatting entities)

#### Scenario: Fallback to HTML
- **WHEN** MarkdownV2 formatting produces a Telegram API error (400 Bad Request with "can't parse entities")
- **THEN** the message is re-sent using HTML parse mode as fallback

---

### Requirement: Telegram Bot API Rate Limiting
The platform SHALL respect Telegram Bot API rate limits by implementing a token-bucket rate limiter: maximum 30 messages per second globally, and maximum 20 messages per minute to the same group chat.

#### Scenario: Rate limit reached for group
- **WHEN** the platform has sent 20 messages to the same group chat within the last minute
- **THEN** subsequent messages are queued and delayed until the rate window resets

#### Scenario: Global rate limit
- **WHEN** the platform has sent 30 messages across all chats within the last second
- **THEN** subsequent messages are queued with backpressure applied to the outbound transport

#### Scenario: Rate limit error from Telegram
- **WHEN** Telegram returns HTTP 429 (Too Many Requests) with a `retry_after` value
- **THEN** the platform pauses outbound messages to the affected chat for the specified duration and retries

---

### Requirement: Telegram Delivery Channel Adapter
The platform SHALL provide a `TelegramDeliveryAdapter` implementing `ChannelAdapterInterface` that enables the delivery-channels system to send messages via Telegram Bot API for scheduled and proactive use cases.

#### Scenario: Scheduled digest delivered via Telegram
- **WHEN** the scheduler triggers a news digest delivery with a Telegram delivery channel configured
- **THEN** `TelegramDeliveryAdapter` resolves the target `chat_id` (and optional `thread_id`) from `DeliveryTarget.address`
- **AND** sends the formatted content via `TelegramSender`
- **AND** returns a `DeliveryResult` with `status: "delivered"` and Telegram's `message_id` as `external_message_id`

#### Scenario: Delivery target format
- **WHEN** a delivery channel of type `telegram` is configured
- **THEN** the `target_address` field uses format `chat_id` for group-level delivery or `chat_id:thread_id` for topic-level delivery

#### Scenario: Delivery failure
- **WHEN** the Telegram API returns an error (bot removed from chat, chat not found, insufficient permissions)
- **THEN** the adapter returns `DeliveryResult` with `status: "failed"` and the error message
- **AND** the delivery is logged in `delivery_log`

---

### Requirement: Inline Keyboard Support
The platform SHALL support sending messages with Telegram inline keyboards for interactive agent responses (confirmation buttons, pagination, action selection).

#### Scenario: Message with inline keyboard
- **WHEN** an agent response includes a `reply_markup` with inline keyboard buttons
- **THEN** `TelegramSender` includes the `InlineKeyboardMarkup` in the `sendMessage` call
- **AND** button callback data is routed back through the webhook as `callback_query` events

#### Scenario: Callback query answered
- **WHEN** a user presses an inline keyboard button
- **THEN** the callback query is normalized as a `callback_query` event
- **AND** the platform calls Telegram's `answerCallbackQuery` to dismiss the loading indicator
