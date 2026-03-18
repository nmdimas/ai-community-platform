## ADDED Requirements

### Requirement: Command Router
The platform SHALL intercept `command_received` events and route them to the appropriate handler based on command name, validating the sender's role before execution.

#### Scenario: Known platform command routed
- **WHEN** a user sends `/agents` in a Telegram group where the bot is present
- **THEN** the `TelegramCommandRouter` matches the command to the built-in `/agents` handler
- **AND** the handler executes and sends a formatted response to the same chat and thread

#### Scenario: Agent-declared command forwarded
- **WHEN** a user sends a command that matches an agent's `manifest.commands[]` entry (e.g., `/wiki search <query>`)
- **THEN** the command is forwarded to the agent via A2A with the command name, arguments, and sender context

#### Scenario: Unknown command
- **WHEN** a user sends a command not recognized by the platform or any enabled agent
- **THEN** the bot replies with "Unknown command. Use /help to see available commands."

---

### Requirement: Role-Based Command Access
The platform SHALL map Telegram chat member status to platform roles and enforce role requirements for command execution.

#### Scenario: Admin command by group creator
- **WHEN** the group creator (Telegram `creator` status) sends `/agent enable knowledge-agent`
- **THEN** the command executes because `creator` maps to platform role `admin`

#### Scenario: Admin command by regular member
- **WHEN** a regular group member (Telegram `member` status) sends `/agent enable knowledge-agent`
- **THEN** the bot replies with "You don't have permission to use this command." because `member` maps to platform role `user`, which lacks admin privileges

#### Scenario: Admin command by Telegram administrator
- **WHEN** a Telegram group administrator sends `/agent disable news-digest`
- **THEN** the command executes because `administrator` maps to platform role `moderator`, which has agent management privileges

#### Scenario: Role override
- **WHEN** a specific Telegram user ID is listed in `telegram_bots.role_overrides` with role `admin`
- **THEN** that user has `admin` privileges regardless of their Telegram chat member status

---

### Requirement: Help Command
The platform SHALL provide a `/help` command that lists all available commands with descriptions, including both platform and agent-declared commands.

#### Scenario: Help command executed
- **WHEN** a user sends `/help` in a group chat
- **THEN** the bot replies with a formatted list of available commands grouped by category (Platform, Agent) with brief descriptions

---

### Requirement: Agents List Command
The platform SHALL provide an `/agents` command that displays all registered agents with their enabled/disabled status and descriptions.

#### Scenario: Agents command executed
- **WHEN** a user sends `/agents` in a group chat
- **THEN** the bot replies with a formatted list showing each agent's name, status (enabled/disabled), and one-line description from its manifest

---

### Requirement: Agent Enable Command
The platform SHALL provide an `/agent enable <name>` command that enables a disabled agent, requiring `admin` or `moderator` role.

#### Scenario: Agent enabled successfully
- **WHEN** a moderator sends `/agent enable knowledge-agent` and the agent exists but is disabled
- **THEN** the agent is enabled in the registry
- **AND** the bot confirms in chat: "Agent knowledge-agent enabled."

#### Scenario: Agent already enabled
- **WHEN** a moderator sends `/agent enable knowledge-agent` and the agent is already enabled
- **THEN** the bot replies: "Agent knowledge-agent is already enabled."

#### Scenario: Unknown agent name
- **WHEN** a moderator sends `/agent enable nonexistent-agent`
- **THEN** the bot replies: "Agent nonexistent-agent not found. Use /agents to see available agents."

---

### Requirement: Agent Disable Command
The platform SHALL provide an `/agent disable <name>` command that disables an enabled agent, requiring `admin` or `moderator` role.

#### Scenario: Agent disabled successfully
- **WHEN** a moderator sends `/agent disable news-digest` and the agent is currently enabled
- **THEN** the agent is disabled in the registry
- **AND** the bot confirms in chat: "Agent news-digest disabled."
- **AND** the agent stops receiving events from the Event Bus

---

### Requirement: Command Response Formatting
All command responses SHALL be formatted for Telegram using MarkdownV2 or HTML parse mode with proper character escaping, and SHALL be sent to the same chat and thread where the command was issued.

#### Scenario: Response in forum topic
- **WHEN** a command is sent in a forum topic (thread)
- **THEN** the response is sent to the same `message_thread_id`

#### Scenario: Long response split
- **WHEN** a command response exceeds 4096 characters
- **THEN** the response is split at paragraph boundaries and sent as multiple messages
