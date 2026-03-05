# Real OpenClaw (Docker)

This directory stores the local config and env files for the real `OpenClaw` services that now run inside the root `ai-community-platform` Docker Compose stack.

The root stack exposes:

- `http://localhost:8082/` through `Traefik`
- `http://localhost:18789/` directly from the `openclaw-gateway` container

## Files

- `.env` - local gateway token
- `../../.local/openclaw/state/` - persistent local OpenClaw state mounted into the containers

Important persisted files:

- `../../.local/openclaw/state/openclaw.json` - main OpenClaw config
- `../../.local/openclaw/state/logs/config-audit.jsonl` - config change audit log

## Start

From the repository root:

```bash
docker compose up -d openclaw-gateway openclaw-cli
docker compose ps openclaw-gateway openclaw-cli
```

Expected result:

- `openclaw-gateway` is `Up (healthy)`
- `openclaw-cli` is `Up`

Note:

- right after restart, `openclaw-gateway` may stay in `health: starting` for up to about 3 minutes because the image healthcheck runs every 180 seconds

## First Login

Open:

```text
http://localhost:8082/
```

Or use the direct container port:

```text
http://localhost:18789/
```

The first page may show `unauthorized: gateway token missing`.

That is expected because the gateway runs with token auth enabled.

Use the token from `docker/openclaw/.env`:

```bash
grep '^OPENCLAW_GATEWAY_TOKEN=' docker/openclaw/.env
```

Paste that token into the Control UI settings in the browser.

## Rotate The Gateway Token

Generate a new token:

```bash
openssl rand -hex 32
```

Update `OPENCLAW_GATEWAY_TOKEN` in `docker/openclaw/.env`, then recreate the containers:

```bash
docker compose up -d --force-recreate openclaw-gateway openclaw-cli
```

If the browser was already open, reconnect with the new token.

## Add Your Provider Tokens

The simplest path is the interactive wizard:

```bash
docker compose exec openclaw-cli openclaw onboard
```

What to do in the wizard:

- choose local mode
- keep the gateway on port `18789`
- use token auth
- reuse the same gateway token from `docker/openclaw/.env`
- add the provider keys you actually use (`OpenAI`, `Anthropic`, `OpenRouter`, `LiteLLM`, etc.)
- skip channels if you only need local agent/model usage for now

You can also re-open only the config wizard later:

```bash
docker compose exec openclaw-cli openclaw configure --section model
docker compose exec openclaw-cli openclaw configure --section gateway
docker compose exec openclaw-cli openclaw configure --section skills
```

## Non-Interactive Example

If you want to preload a provider token without stepping through prompts:

```bash
docker compose exec openclaw-cli openclaw onboard \
  --non-interactive \
  --accept-risk \
  --mode local \
  --flow quickstart \
  --gateway-auth token \
  --gateway-bind lan \
  --gateway-port 18789 \
  --gateway-token '<same value as OPENCLAW_GATEWAY_TOKEN in .env>' \
  --openai-api-key 'sk-...' \
  --skip-channels \
  --skip-skills
```

You can swap `--openai-api-key` for other supported flags such as:

- `--anthropic-api-key`
- `--openrouter-api-key`
- `--litellm-api-key`
- `--mistral-api-key`
- `--gemini-api-key`

## Inspect Or Edit Config

Show the active config file path:

```bash
docker compose exec openclaw-cli openclaw config file
```

Read the current config:

```bash
sed -n '1,200p' ../../.local/openclaw/state/openclaw.json
```

Set a config value without opening the wizard:

```bash
docker compose exec openclaw-cli openclaw config set some.path someValue
```

After manual config changes, restart the gateway:

```bash
docker compose restart openclaw-gateway
```

## Logs And Health

```bash
docker compose logs -f openclaw-gateway
docker compose exec openclaw-cli openclaw --version
```

The Docker image includes an HTTP healthcheck on `http://127.0.0.1:18789/healthz`.

## Stop

```bash
docker compose down
```
