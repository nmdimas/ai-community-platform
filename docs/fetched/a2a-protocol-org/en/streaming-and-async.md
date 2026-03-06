---
source: https://a2a-protocol.org/latest/topics/streaming-and-async/
fetched: 2026-03-06
lang: en
---

# Streaming & Asynchronous Operations

The Agent2Agent (A2A) protocol handles tasks that may not complete immediately. Many AI-driven operations are long-running, involve multiple steps, produce incremental results, or require human intervention. A2A provides mechanisms for managing such asynchronous interactions, ensuring clients receive updates effectively whether continuously connected or operating in a disconnected manner.

## Streaming with Server-Sent Events (SSE)

For tasks producing incremental results or ongoing status updates, A2A supports real-time communication using Server-Sent Events (SSE).

### Key Features

- **Server Capability:** The A2A Server indicates streaming support by setting `capabilities.streaming: true` in its Agent Card.
- **Initiating a Stream:** Clients use the `SendStreamingMessage` RPC method to send an initial message and subscribe to task updates simultaneously.
- **Server Response:** Upon successful subscription, the server responds with HTTP 200 OK and `Content-Type: text/event-stream`, keeping the connection open to push events.
- **Event Structure:** The server sends events containing JSON-RPC 2.0 Response objects, typically `SendStreamingMessageResponse`. The result field includes:
  - `Task`: Current state of work
  - `TaskStatusUpdateEvent`: Lifecycle state changes and intermediate messages
  - `TaskArtifactUpdateEvent`: New or updated artifacts, streamed in chunks
- **Stream Termination:** When tasks reach terminal or interrupted states (COMPLETED, FAILED, CANCELED, REJECTED, INPUT_REQUIRED), the server closes the stream.
- **Resubscription:** Clients can reconnect using the `SubscribeToTask` RPC method if the connection breaks prematurely.

### When to Use Streaming

- Real-time progress monitoring of long-running tasks
- Receiving large results incrementally
- Interactive exchanges requiring immediate feedback
- Applications needing low-latency agent updates

## Push Notifications for Disconnected Scenarios

For very long-running tasks or when clients cannot maintain persistent connections, A2A supports asynchronous updates via push notifications.

### Key Features

- **Server Capability:** Servers indicate support by setting `capabilities.pushNotifications: true` in their Agent Card.
- **Configuration:** Clients provide `PushNotificationConfig` to the server, either within initial `SendMessage`/`SendStreamingMessage` requests or separately using `CreateTaskPushNotificationConfig`. The configuration includes a webhook URL, optional token, and optional authentication details.
- **Notification Trigger:** The server sends notifications on significant state changes.
- **Notification Payload:** HTTP body payloads are `StreamResponse` objects containing `task`, `message`, `statusUpdate`, or `artifactUpdate`.
- **Client Action:** Upon receiving verified notifications, clients typically use `GetTask` RPC to retrieve the complete updated `Task` object.

### When to Use Push Notifications

- Very long-running tasks lasting minutes, hours, or days
- Clients unable to maintain persistent connections (mobile apps, serverless functions)
- Scenarios requiring notification only on significant state changes

## Client-Side Push Notification Service

The `PushNotificationConfig.url` points to a client-side Push Notification Service responsible for receiving HTTP POST notifications from the A2A Server. This service authenticates incoming notifications, validates relevance, and relays content to appropriate client application logic.

## Security Considerations for Push Notifications

### A2A Server Security

**Webhook URL Validation:** Servers must not blindly trust client-provided URLs to prevent SSRF or DDoS attacks. Mitigation strategies include domain allowlisting, ownership verification, and network egress controls.

**Authenticating to Client Webhooks:** The A2A Server must authenticate according to the scheme in `PushNotificationConfig.authentication` (Bearer Tokens, API keys, HMAC signatures, or mutual TLS).

### Client Webhook Receiver Security

**Authenticating the A2A Server:** Verify incoming notification authenticity using signature/token verification and the optional `PushNotificationConfig.token`.

**Preventing Replay Attacks:**
- Include timestamps and reject overly old notifications
- Use unique identifiers (JWT `jti` claims, event IDs) for critical notifications

**Key Management:** Implement secure practices including regular key rotation. Protocols like JWKS facilitate asymmetric key rotation.

### Example Asymmetric Key Flow (JWT + JWKS)

1. Client creates `PushNotificationConfig` with `authentication.scheme: "Bearer"` and expected issuer/audience for the JWT.
2. A2A Server generates a JWT signed with its private key, including claims (`iss`, `aud`, `iat`, `exp`, `jti`, `taskId`), and makes public keys available through a JWKS endpoint.
3. Client Webhook verifies the JWT signature using the public key from the JWKS endpoint, validates claims, and checks the optional token.
