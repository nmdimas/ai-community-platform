# Tasks

- [ ] Add `TELEGRAM_BOT_TOKEN` to `docker/openclaw/.env.example` and `docker/openclaw/docker-compose.yml` environment configurations.
- [ ] Add `TELEGRAM_WEBHOOK_URL` to the compose configuration (empty by default for local dev polling).
- [ ] Write OpenClaw `system_prompt` documentation detailing its persona and instructions for routing user messages to discovered tools.
- [ ] Update `LOCAL_DEV.md` with instructions on how to create a Telegram Bot via BotFather and inject the token into the local OpenClaw `.env`.
- [ ] Document the production deployment webhook steps in `docs/plans/telegram-openclaw-integration-plan.md`.
