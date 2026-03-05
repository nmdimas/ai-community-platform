# Design: Telegram Bot Integration via OpenClaw

## Architecture Choices

**1. OpenClaw as the Single Entry Point (Orchestrator)**
Instead of each specialized agent (like `news-maker` or `knowledge-base`) polling Telegram directly, OpenClaw acts as the unified frontend.

- **Pros:** A single conversational history, unified tone of voice, centralized security/rate-limiting, and the ability to combine multiple agents' outputs into one response.
- **Cons:** OpenClaw becomes a single point of failure for chat interfaces.

**2. Tool Discovery vs Hardcoded Calls**
OpenClaw will _not_ have hardcoded endpoints to `hello-world` or other agents. Instead, it relies on the `add-openclaw-agent-discovery` feature. It asks Core "what tools do you have?", reads the tool descriptions, and uses its LLM to decide when to call them.

**3. Telegram Webhooks vs Polling**

- **Local Dev:** `ngrok` is cumbersome for rapid iteration. OpenClaw will use Telegram's `getUpdates` (long-polling) when running locally.
- **Production:** OpenClaw will register a webhook path via Traefik to receive pushes from Telegram, ensuring low latency and scale.

**4. E2E Flow (Hello-World Use Case)**

- User Telegram Chat -> OpenClaw Telegram Channel
- OpenClaw LLM sees "What do you want to say?"
- OpenClaw executes `call_tool(hello-world)`
- Request hits `Core` API -> `Core` checks if `hello-world` is enabled in `agent_registry`.
- `Core` proxies request to `http://hello-agent/a2a`.
- `hello-agent` responds "Hello, World!".
- OpenClaw formats the final message: "The agent says: Hello, World!" -> Telegram.
