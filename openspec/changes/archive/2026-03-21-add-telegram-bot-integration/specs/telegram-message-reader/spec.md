## ADDED Requirements

### Requirement: Passive Group Message Observation
The platform SHALL read all messages in groups and supergroups where the bot is added (with privacy mode disabled), normalizing them as `message_created` events for agent consumption without requiring explicit bot mention or command prefix.

#### Scenario: Regular group message observed
- **WHEN** any member sends a text message in a group where the bot is present with privacy mode disabled
- **THEN** the message is normalized and dispatched to all agents subscribed to `message_created`
- **AND** agents can process the message content for knowledge extraction, anti-fraud analysis, or other passive tasks

#### Scenario: Privacy mode enabled
- **WHEN** a bot has `privacy_mode: true` in its configuration
- **THEN** only messages that contain a bot command, mention the bot, or are replies to bot messages are processed
- **AND** other group messages are ignored

#### Scenario: Reply chain context
- **WHEN** a message is a reply to another message (`reply_to_message` field present)
- **THEN** the normalized event includes `reply_to_message_id` so agents can understand conversational context

#### Scenario: Forwarded message
- **WHEN** a message is forwarded from another chat or channel
- **THEN** the normalized event includes `forward_from` metadata (original sender or channel name where available)

---

### Requirement: Forum Topic (Thread) Support
The platform SHALL support Telegram supergroups with forum mode enabled, correctly routing messages to and from specific forum topics identified by `message_thread_id`.

#### Scenario: Message in forum topic
- **WHEN** a message is sent in a forum topic
- **THEN** the normalized event includes `chat.thread_id` matching the `message_thread_id`
- **AND** agents receive the thread context to provide topic-specific responses

#### Scenario: General topic message
- **WHEN** a message is sent in the "General" topic of a forum supergroup
- **THEN** the normalized event has `chat.thread_id` set to the general topic's `message_thread_id` (typically 1)

#### Scenario: New topic created
- **WHEN** a `forum_topic_created` service message is received
- **THEN** the `telegram_chats` record is updated with `has_threads: true` if not already set

---

### Requirement: Media Message Metadata
The platform SHALL extract metadata from media messages (photo, document, video, voice, sticker) without downloading the actual files, making the metadata available to agents in the normalized event.

#### Scenario: Photo with caption
- **WHEN** a user sends a photo with a caption in a tracked group
- **THEN** the normalized event has `message.text` set to the caption, `message.has_media: true`, and `message.media_type: "photo"`

#### Scenario: Document shared
- **WHEN** a user sends a document (PDF, spreadsheet, etc.)
- **THEN** the normalized event includes `message.has_media: true`, `message.media_type: "document"`, and document file name in metadata

#### Scenario: Voice message
- **WHEN** a user sends a voice message
- **THEN** the normalized event includes `message.has_media: true`, `message.media_type: "voice"`, and duration in metadata
- **AND** `message.text` is empty (no transcription in MVP)

---

### Requirement: Message Edit and Delete Tracking
The platform SHALL track message edits and deletions in observed groups, dispatching `message_edited` and `message_deleted` events to subscribed agents.

#### Scenario: Message edited
- **WHEN** a user edits a previously sent message
- **THEN** an `edited_message` update is received and normalized as `event_type: "message_edited"` with the new text
- **AND** subscribed agents are notified of the change

#### Scenario: Message deleted
- **WHEN** a service message indicates message deletion (where available via Telegram API)
- **THEN** a `message_deleted` event is dispatched to subscribed agents with the chat_id and message_id
