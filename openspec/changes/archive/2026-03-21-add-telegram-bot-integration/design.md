## Context

The AI Community Platform is designed as a Telegram-first community management tool. The entire agent ecosystem (A2A Gateway, OpenClaw frontdesk, Event Bus, Agent Registry) is built but lacks the Telegram connection layer. This change bridges that gap by adding full bidirectional Telegram Bot API integration: reading all group messages passively, processing commands, and sending messages to chats and forum threads.

OpenClaw already handles Telegram as a messaging channel (bot token, polling/webhook, LLM routing). This design adds the **Core platform layer** that normalizes Telegram events into platform events, completes the Event Bus dispatch, and provides direct outbound messaging capabilities independent of OpenClaw.

### Two Paths for Telegram Messages

1. **Via OpenClaw (conversational)**: User mentions bot or sends DM → OpenClaw handles intent → invokes Core A2A tools → response flows back through OpenClaw → formatted reply to Telegram
2. **Via Core directly (passive + commands)**: All group messages → Core webhook → normalize → Event Bus → agents observe passively; `/commands` → Command Router → handler → direct Telegram reply

Both paths coexist. OpenClaw handles conversational AI interactions. Core handles passive observation, platform commands, and proactive outbound messaging.

## Goals / Non-Goals

### Goals
- Full bidirectional Telegram integration: read all messages, send to any chat/thread
- Normalize Telegram events into platform-agnostic event format for the Event Bus
- Support Telegram supergroups with forum topics (threads)
- Platform commands (`/help`, `/agents`, `/agent enable|disable`) work directly without OpenClaw
- Agents can passively observe all community messages (knowledge extraction, anti-fraud)
- Proactive outbound messaging for digests, alerts, scheduled content
- Multi-bot support (different bots for different communities or personas)
- Local development without public URL (long-polling mode)

- Channel posting — bot as sole publisher in read-only channels with interactive buttons
- Conversational DM flows — multi-step input collection via private chat (announcements, proposals)
- Moderation pipeline — user submits via DM → moderator approves via buttons → bot publishes
- Media sending — photos, albums (sendMediaGroup), document sharing
- Mini App support (optional) — rich HTML forms inside Telegram for complex input
- Inline keyboard interactions — callback_query routing, button state management

### Non-Goals
- Handling Telegram payments or Telegram Stars
- Building a Telegram client (only Bot API, not MTProto/TDLib)
- Multi-tenant chat routing (MVP = single community)
- Inline mode or inline query handling
- Voice/video note transcription (metadata only, no content processing)
- Bot commands menu auto-registration via BotFather API (manual setup)
- Telegram Passport or identity verification

## Decisions

### Decision 1: Dual-path architecture (OpenClaw + Core webhook)
- **Why**: OpenClaw excels at conversational AI routing (LLM intent detection, tool selection, response composition). But passive message observation and platform commands don't need LLM — they're deterministic. Running all traffic through OpenClaw would add latency and cost for simple operations. Core receives all updates directly via webhook, handles commands and passive events, while OpenClaw handles the AI-conversational subset.
- **Alternative**: Route everything through OpenClaw and have it call Core for all events. Rejected: unnecessary LLM cost for passive observation, higher latency for simple commands, OpenClaw becomes a bottleneck.
- **Alternative**: Drop OpenClaw entirely, handle everything in Core. Rejected: would require building LLM orchestration, intent routing, and conversational state management from scratch.

### Decision 2: Webhook-first with polling fallback
- **Why**: Telegram webhooks are more efficient (no polling overhead, instant delivery, less API calls). Polling is needed only for local dev where public URLs aren't available.
- **Implementation**: `TelegramWebhookController` for production, `app:telegram:poll` CLI command for local dev. Both feed into the same `TelegramUpdateNormalizer`.
- **Webhook URL pattern**: `POST /api/v1/webhook/telegram/{bot_id}` — bot_id allows multiple bots on same Core instance.

### Decision 3: Disable privacy mode for community bot
- **Why**: Telegram bots in privacy mode only receive: commands, messages that mention the bot, replies to bot messages, and service messages. For passive knowledge extraction and anti-fraud, the bot must read ALL group messages. Privacy mode must be disabled via BotFather.
- **Trade-off**: Users can see the bot is added to the group. The bot reads all messages. This must be disclosed in bot description and group onboarding.
- **Mitigation**: Clear documentation, configurable per-bot privacy setting in registry, data retention policy.

### Decision 4: Normalized event envelope
- **Why**: Agents should not depend on Telegram-specific data structures. A normalized envelope enables future multi-platform support (Slack, Discord) without changing agent code.
- **Format**:
```json
{
  "event_type": "message_created",
  "platform": "telegram",
  "bot_id": "main",
  "chat": {
    "id": "-1001234567890",
    "title": "AI Community",
    "type": "supergroup",
    "thread_id": "42"
  },
  "sender": {
    "id": "123456789",
    "username": "john_doe",
    "first_name": "John",
    "role": "member",
    "is_bot": false
  },
  "message": {
    "id": "789",
    "text": "Has anyone tried the new cafe on Main St?",
    "reply_to_message_id": "456",
    "has_media": false,
    "media_type": null,
    "forward_from": null,
    "timestamp": "2026-03-18T14:30:00Z"
  },
  "trace_id": "trace_...",
  "request_id": "req_...",
  "raw_update_id": 123456789
}
```
- **Alternative**: Pass raw Telegram Update JSON. Rejected: couples agents to Telegram, makes multi-platform impossible.

### Decision 5: Thread-aware messaging
- **Why**: Telegram supergroups with forum mode use topics (threads). Each topic has a `message_thread_id`. The bot must send replies to the correct thread, not the general chat.
- **Implementation**: `thread_id` is a first-class field in the normalized event envelope. `TelegramSender` always includes `message_thread_id` when present. Agents receiving events see the thread context and can specify reply target.

### Decision 6: Role mapping from Telegram to platform
- **Why**: Telegram has its own permission system (creator, administrators with granular rights, restricted, banned). Platform needs simple role mapping for command authorization.
- **Mapping**:
  - Telegram `creator` → platform `admin`
  - Telegram `administrator` → platform `moderator`
  - Telegram `member` → platform `user`
  - Telegram `restricted` → platform `user` (limited)
  - Telegram `left`/`kicked` → no platform role
- **Override**: Admin UI can assign custom platform roles to specific Telegram user IDs (stored in `telegram_bots.role_overrides` JSONB column).

### Decision 7: Multi-bot with shared Core
- **Why**: Different communities or personas may need different bots. Template already exists (`compose.openclaw.multi-bot.example.yaml`). Core should handle multiple bots on a single instance.
- **Implementation**: `telegram_bots` table stores per-bot config. Webhook URL includes `{bot_id}`. Each bot maps to one community. OpenClaw instances (if used) are per-bot with separate frontdesk policies.
- **MVP scope**: Support 1 bot in production, infrastructure ready for N bots.

### Decision 8: Idempotent webhook processing
- **Why**: Telegram may send duplicate updates (webhook retries on timeout). Processing the same update twice could cause duplicate agent invocations.
- **Implementation**: Track `update_id` per bot in Redis. If seen, skip. `update_id` is monotonically increasing — also use for gap detection (log warning if IDs are not sequential).

### Decision 9: Outbound via both direct API and delivery-channels
- **Why**: Two use cases: (a) immediate command responses need direct `sendMessage` call; (b) scheduled/proactive messages (digests, alerts) should go through the delivery-channels abstraction for audit, retry, and rate limiting.
- **Implementation**: `TelegramSender` for direct replies (used by CommandRouter and EventBus responses). `TelegramDeliveryAdapter` implements `ChannelAdapterInterface` for delivery-channels system.

### Decision 10: Read-only channels with bot as sole publisher
- **Why**: Common community pattern — a curated channel (announcements, news, classifieds) where only the bot posts, but users interact via buttons. Telegram supports this natively: create a channel, add bot as admin with `can_post_messages`, restrict other members from posting.
- **Implementation**: `TelegramChannelManager` handles channel posts. Bot receives `channel_post` updates via webhook (separate from group `message` updates). Each post can include `InlineKeyboardMarkup` with action buttons. A pinned "welcome" message provides persistent entry-point buttons.
- **Channel vs Group**: Channels are broadcast-only (no user messages). Groups/supergroups allow user messages. Both are supported — channels for curated content, groups for community discussion.

### Decision 11: Callback → DM conversational flow pattern
- **Why**: When a user clicks a button in a channel (where they can't type), the bot needs another surface to collect input. Telegram's standard pattern: button press → bot opens DM → multi-step conversation → result published back to channel/group.
- **Implementation**:
  1. Inline button with `callback_data: "flow:create_ad:channel_123"`
  2. `TelegramCallbackRouter` matches pattern, calls `answerCallbackQuery` with redirect hint
  3. If user hasn't started DM, button `url: "t.me/botname?start=create_ad_channel_123"` serves as fallback (deep-link)
  4. `TelegramConversationManager` initializes state machine in DM: `collect_text → collect_photo → preview → confirm`
  5. On confirm → if moderation enabled, send draft to moderator chat with approve/reject buttons
  6. On moderator approve → `TelegramSender::sendMessage` (or `sendPhoto`/`sendMediaGroup`) to target channel
- **State storage**: Redis hash per `(bot_id, user_id, flow_type)` with TTL. Cleaned up on completion, timeout, or `/cancel`.
- **Alternative**: Use Mini App for input form. Better UX for complex forms but requires HTTPS and more frontend work. Supported as optional upgrade path.

### Decision 12: Moderation pipeline for user-generated content
- **Why**: User-submitted content (announcements, proposals) should not appear publicly without review. Community moderators need a clear approve/reject workflow.
- **Implementation**:
  1. User completes DM flow → bot assembles draft post
  2. Bot sends draft to a configured "moderation chat" (a private group with moderators) with preview + buttons: "✅ Опублікувати" / "❌ Відхилити" / "✏️ Редагувати"
  3. Moderator presses button → `callback_query` routed to moderation handler
  4. On approve: bot publishes to target channel/chat/thread, notifies user in DM "Ваше оголошення опубліковано!"
  5. On reject: bot notifies user with optional reason, draft discarded
  6. On edit: bot sends text back to user in DM for revision → re-submit cycle
- **Data model**: `telegram_drafts` table stores pending drafts with state, author, target, content, moderator actions.
- **Timeout**: Unmoderated drafts older than configurable TTL (default 48h) are auto-rejected with notification.

### Decision 13: Media support for outbound messages
- **Why**: Announcements, news digests, and user-generated content often include images. Bot API supports `sendPhoto` (single image + caption up to 1024 chars), `sendMediaGroup` (album of 2-10 photos/documents with individual captions), and `copyMessage` (forward without "forwarded from" label).
- **Implementation**: `TelegramSender` supports all three methods. `TelegramMediaBuilder` constructs `InputMediaPhoto`/`InputMediaDocument` arrays for albums. Media is referenced by URL (no file upload to platform storage — Telegram hosts the files).
- **Caption limit**: `sendPhoto` caption is 1024 chars (not 4096 like `sendMessage`). `TelegramSender` auto-detects and splits long content: photo with short caption + follow-up text message if needed.

### Decision 14: Mini App as optional rich form interface
- **Why**: Multi-step DM flows work but feel clunky for complex input (e.g., announcement with category, tags, schedule, multiple photos). Telegram Mini Apps open an HTML page inside Telegram, providing full form UX.
- **Implementation**: Optional — deployed only when needed. `TelegramMiniAppController` serves lightweight HTML forms. Button with `WebAppInfo: {url: "https://platform/telegram/mini-app/create-ad"}` opens the form. On submit, Telegram sends `web_app_data` message to bot. Form data is JSON-encoded.
- **Progressive enhancement**: Start with DM flows, add Mini Apps later for specific high-value forms.
- **HTTPS requirement**: Mini Apps require valid HTTPS. In production, Traefik handles TLS. For local dev, use ngrok or Traefik with self-signed cert.

## Data Model

### telegram_drafts

| Column | Type | Notes |
|--------|------|-------|
| id | UUID PK | |
| bot_id | VARCHAR(32) FK → telegram_bots | |
| author_user_id | BIGINT NOT NULL | Telegram user who created draft |
| target_chat_id | BIGINT NOT NULL | Where to publish |
| target_thread_id | BIGINT | Optional forum topic |
| flow_type | VARCHAR(32) NOT NULL | `announcement`, `proposal`, `suggestion` |
| content_text | TEXT | Draft text content |
| content_media | JSONB DEFAULT '[]' | Array of `{type, url, file_id, caption}` |
| content_markup | JSONB | Optional inline keyboard for the published post |
| status | VARCHAR(32) DEFAULT 'pending' | `pending`, `approved`, `rejected`, `published`, `expired` |
| moderator_user_id | BIGINT | Who moderated |
| moderator_chat_message_id | BIGINT | Message ID in moderation chat (for button cleanup) |
| rejection_reason | TEXT | Optional reason from moderator |
| published_message_id | BIGINT | Telegram message_id after publishing |
| created_at | TIMESTAMPTZ DEFAULT now() | |
| moderated_at | TIMESTAMPTZ | |
| published_at | TIMESTAMPTZ | |

### telegram_bots

| Column | Type | Notes |
|--------|------|-------|
| id | VARCHAR(32) PK | Bot identifier (e.g., "main", "support") |
| bot_username | VARCHAR(64) NOT NULL | Telegram bot @username |
| bot_token_encrypted | TEXT NOT NULL | AES-256-GCM encrypted token |
| webhook_secret | VARCHAR(64) NOT NULL | Telegram webhook secret_token |
| community_id | UUID FK → communities | Assigned community |
| privacy_mode | BOOLEAN DEFAULT FALSE | Whether privacy mode is enabled |
| polling_mode | BOOLEAN DEFAULT FALSE | True = long-polling, False = webhook |
| role_overrides | JSONB DEFAULT '{}' | `{"telegram_user_id": "admin"}` |
| config | JSONB DEFAULT '{}' | Bot-specific settings |
| enabled | BOOLEAN DEFAULT TRUE | |
| last_update_id | BIGINT DEFAULT 0 | Last processed update_id |
| webhook_url | TEXT | Registered webhook URL |
| created_at | TIMESTAMPTZ DEFAULT now() | |
| updated_at | TIMESTAMPTZ DEFAULT now() | |

### telegram_chats

| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT PK | Telegram chat_id |
| bot_id | VARCHAR(32) FK → telegram_bots | Which bot is in this chat |
| title | VARCHAR(256) | Chat title |
| type | VARCHAR(32) NOT NULL | `group`, `supergroup`, `private` |
| has_threads | BOOLEAN DEFAULT FALSE | Forum/topics enabled |
| member_count | INTEGER | Last known count |
| joined_at | TIMESTAMPTZ | When bot joined |
| left_at | TIMESTAMPTZ | When bot was removed (soft delete) |
| metadata | JSONB DEFAULT '{}' | Additional chat info |
| last_message_at | TIMESTAMPTZ | Activity tracking |

## Message Flow Diagrams

### Passive Message Observation
```
Telegram Group Message
    → Telegram Bot API (webhook)
    → POST /api/v1/webhook/telegram/{bot_id}
    → TelegramWebhookController
    → TelegramUpdateNormalizer
    → TelegramEventPublisher
    → EventBus::dispatch("message_created", normalizedPayload)
    → foreach enabled agent with "message_created" subscription:
        → A2AClient::sendMessage(agent.a2a_endpoint, payload)
        → Agent processes (e.g., knowledge-agent stores message)
```

### Platform Command
```
User sends: /agents
    → Telegram webhook
    → TelegramUpdateNormalizer (detects command entity)
    → TelegramCommandRouter
    → Validates sender role (member → user)
    → Executes built-in "agents" handler
    → Queries AgentRegistry::findAll()
    → Formats agent list with status
    → TelegramSender::sendMessage(chat_id, thread_id, formatted_text)
    → User sees agent list in Telegram
```

### OpenClaw Conversational Flow
```
User sends: @bot what's in the wiki about cafes?
    → OpenClaw (via its own Telegram channel)
    → LLM intent detection → knowledge_search tool
    → POST /api/v1/a2a/send-message {tool: "knowledge.search", input: {query: "cafes"}}
    → Core A2A bridge → knowledge-agent
    → Response → OpenClaw → Telegram reply
```

### Proactive Outbound (Digest)
```
Scheduler triggers news-digest job
    → Agent generates digest content
    → Agent calls Core delivery API (or returns result to scheduler)
    → DeliveryService resolves TelegramDeliveryAdapter
    → TelegramDeliveryAdapter::send(payload)
    → TelegramSender::sendMessage(target_chat_id, target_thread_id, digest_text)
    → DeliveryLog records success
```

### Channel Button → DM → Moderation → Publish
```
Channel has pinned message with button: [Розмістити оголошення]
    → User presses button
    → callback_query {data: "flow:create_ad:channel_-100123"}
    → TelegramCallbackRouter
    → answerCallbackQuery("Перейдіть у бот для створення оголошення")
    → Bot sends DM to user: "Надішліть текст оголошення"
    → User sends text in DM
    → ConversationManager: state = collect_photo
    → Bot: "Додайте фото (або /skip)"
    → User sends photo
    → ConversationManager: state = preview
    → Bot sends preview: "Ваше оголошення:\n{text}\n{photo}\n\n[Опублікувати] [Редагувати] [Скасувати]"
    → User presses [Опублікувати]
    → Bot sends draft to moderation chat:
        "Нове оголошення від @user:\n{text}\n{photo}\n\n[✅ Опублікувати] [❌ Відхилити]"
    → Moderator presses [✅ Опублікувати]
    → Bot publishes to channel via sendPhoto/sendMediaGroup
    → Bot notifies user in DM: "Ваше оголошення опубліковано! ✅"
    → DeliveryLog records publication
```

### Post-Level Proposal Flow
```
Published post in channel with button: [Написати пропозицію]
    → User presses button
    → callback_query {data: "flow:proposal:post_456"}
    → TelegramCallbackRouter
    → Bot starts DM: "Напишіть вашу пропозицію до оголошення #{456}"
    → User sends text
    → ConversationManager: preview → confirm
    → Bot notifies post author (or moderators) with proposal text
    → Optional: bot updates original post button counter [Написати пропозицію (3)]
```

### Deep-Link Fallback (user hasn't started DM)
```
User presses button in channel → bot can't send DM (user never started chat)
    → Button configured as URL: t.me/botname?start=create_ad_channel_-100123
    → User clicks → Telegram opens bot DM with /start create_ad_channel_-100123
    → TelegramCommandRouter detects deep-link payload
    → ConversationManager initializes flow from start parameter
    → Normal DM flow continues
```

## Queue Architecture

Based on `symfony.messenger.openclaw.example.yaml`:

| Queue | Purpose | Retry Strategy |
|-------|---------|----------------|
| `telegram_inbound` | Normalize + dispatch Telegram updates | 3 retries, 500ms × 2 backoff |
| `agent_invoke` | Route A2A calls to agents | 2 retries, 1s delay |
| `telegram_outbound` | Send messages via Telegram Bot API | 5 retries, 300ms × 2 backoff |

**Per-chat serialization**: Process one inbound message per `(bot_id, chat_id)` at a time to maintain ordering. Agent invoke and outbound queues are parallel.

## Risks / Trade-offs

| Risk | Mitigation |
|------|-----------|
| Bot reads all messages — privacy concern | Clear disclosure in bot bio, configurable privacy mode per bot, data retention policy |
| Telegram webhook may miss updates | Idempotent processing with `update_id` tracking, gap detection, fallback to `getUpdates` for recovery |
| High message volume in active groups | Queue-based async processing, per-chat rate limiting, agents can opt out of `message_created` events |
| Dual path (OpenClaw + Core) creates confusion | Clear separation: OpenClaw for AI conversations (mentions/DMs), Core for commands + passive observation |
| Bot API rate limits (30 msg/sec) | Token-bucket rate limiter in `TelegramSender`, queue backpressure for outbound |
| Thread/topic support complexity | Progressive: start with basic message threading, add full forum topic management later |
| Multiple bots on same Core instance | Per-bot webhook routing via `{bot_id}` path, per-bot config isolation |

| DM conversation state lost (Redis restart) | TTL-based cleanup + user can restart flow anytime with /start |
| Moderation queue bottleneck (slow moderators) | Auto-expire after 48h, notify user, configurable escalation |
| User spam via DM flows | Per-user rate limit on flow initiation (max 3 active drafts) |
| Channel button confusion (user expects to type in channel) | Clear button labels + answerCallbackQuery with instruction text |
| Mini App HTTPS requirement for local dev | Optional feature, ngrok or Traefik self-signed cert as documented workaround |
| Media file size limits (Telegram: 50MB bot upload, 20MB download) | Document limits, reject oversized uploads with helpful message |

## Open Questions

- Should the bot automatically join threads/topics created by members, or only respond in threads where it's mentioned?
- What is the data retention policy for stored messages? 30 days? Configurable per community?
- Should passive message observation be rate-limited (e.g., skip messages from bots or very high-frequency senders)?
- Should the bot respond to commands in DMs (private chats) or only in groups?
- Should moderation be optional per flow type (e.g., proposals always moderated, announcements only for non-admins)?
- What's the maximum number of concurrent DM flows per user?
- Should published posts include author attribution ("Оголошення від @user") or be anonymous?
