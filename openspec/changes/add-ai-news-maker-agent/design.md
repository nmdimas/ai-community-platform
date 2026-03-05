# Design: AI News Maker Agent

## Context

The requested feature is not a simple digest over existing community messages. It is a new external-news workflow that:

- crawls open AI-related websites
- stores parsed raw items in a temporary table
- runs a first AI agent to score and select up to 10 items
- runs a second AI agent to translate and rewrite selected items into a publication-ready format
- exposes a publication API and a public published-news page
- lets operators tune prompts, guardrails, schedules, retention, resources, and proxy settings

This scope crosses storage, crawling, AI orchestration, admin UX, and public web delivery, so it needs a dedicated service and its own storage boundary.

## Goals / Non-Goals

### Goals

- Build a standalone Python service dedicated to AI news ingestion and publication preparation
- Use a dedicated Postgres database for isolation from core platform data
- Support open web sources through a schema-agnostic crawler/parser adapter
- Keep raw crawled items in temporary storage with automatic expiry
- Provide a two-stage AI editorial flow with clear status transitions
- Make prompts and guardrails configurable from admin UI
- Provide an internal API for ready-to-publish items and status updates
- Provide a public UI for already published items

### Non-Goals

- Full social posting automation to third-party channels in the first version
- Multi-tenant separation across many communities
- Human-in-the-loop editorial approval workflow beyond status updates
- Source-specific scraper templates for every website in the first version
- Real-time streaming ingestion; scheduled batches are sufficient for MVP

## Architecture

```text
Open Web Sources
      |
      v
[CrawlerAdapter]
      |
      v
[Raw Extractor]
      |
      v
Postgres DB: ai_news_maker
  - news_sources
  - raw_news_items
  - curated_news_items
  - agent_settings
  - scheduler_runs
      |
      +--> [Agent 1: Ranker] --> select up to 10
      |           |
      |           v
      +--> [Agent 2: Rewriter/Translator]
                  |
                  v
          curated_news_items(status=ready)
                  |
          +-------+--------+
          |                |
          v                v
  Internal Publication API   Public Web UI
```

## Service Shape

`news-maker-agent` is a standalone Python service. A practical baseline is:

- `FastAPI` for admin/public/API endpoints
- `SQLAlchemy` + migrations for Postgres
- a scheduler with cron-style expressions stored in the database
- an adapter boundary for crawler/parser and LLM providers

This keeps the implementation independent from the PHP core while remaining easy to integrate through HTTP.

## Data Model

### Dedicated Database

Use one separate Postgres database, for example `ai_news_maker`, on the same Postgres server or a separate instance. This proposal intentionally isolates the news-maker schema from the core platform database.

### Tables

#### `news_sources`

- `id`
- `name`
- `base_url`
- `topic_scope` (default: `ai`)
- `enabled`
- `crawl_priority`
- `last_success_at`
- `last_error_at`
- `proxy_enabled_override` (optional)

#### `raw_news_items`

- `id`
- `source_id`
- `source_url`
- `canonical_url`
- `title`
- `raw_text`
- `excerpt`
- `published_at_source`
- `language`
- `status` (`new`, `scored`, `selected`, `expired`, `discarded`)
- `score`
- `dedup_hash`
- `crawl_run_id`
- `expires_at`
- `created_at`

This is the temporary table requested by the workflow. Items live here only until they are discarded, selected, or expired.

#### `curated_news_items`

- `id`
- `raw_news_item_id`
- `title`
- `summary`
- `body`
- `language` (default output: Ukrainian)
- `style_profile`
- `status` (`draft`, `ready`, `published`, `rejected`)
- `reference_title`
- `reference_url`
- `reference_domain`
- `published_at`
- `created_at`

#### `agent_settings`

- prompt templates per stage (`crawler_cleanup`, `ranker`, `rewriter`)
- guardrail prompts per stage (always appended)
- scheduler expressions
- raw item TTL
- proxy defaults
- selected models/adapters

#### `scheduler_runs`

- `id`
- `job_name`
- `started_at`
- `finished_at`
- `status`
- `items_seen`
- `items_selected`
- `error_message`

## Ingestion Flow

1. Scheduler starts a crawl run using enabled sources.
2. `CrawlerAdapter` fetches source pages without relying on hardcoded per-site schemas.
3. The parser extracts candidate article blocks, normalizes content, and computes a dedup hash.
4. New candidates are stored in `raw_news_items` with `status = new` and `expires_at` based on retention settings.
5. Duplicate or invalid items are skipped or marked `discarded`.

## Crawler / Parser Strategy

To avoid tight coupling to HTML schemas, the service uses a replaceable adapter:

- default: AI-native crawler/parser library (Crawl4AI-class or equivalent)
- fallback: standard HTTP fetch + readability-style extraction

The adapter contract should return normalized candidate items:

- title
- body text
- canonical URL
- source publication time when available
- language hint

This keeps source onboarding lightweight and avoids custom scraper logic for each site in the first version.

## Editorial Pipeline

### Agent 1: Ranker

The first agent processes newly ingested raw items and decides which ones deserve promotion.

Responsibilities:

- score relevance to the AI topic
- down-rank duplicates, thin content, marketing noise, and low-signal reposts
- select no more than 10 items per run
- persist scores and selection rationale

Output:

- selected items move to `status = selected`
- non-selected items move to `status = scored` or `discarded`

### Agent 2: Rewriter / Translator

The second agent processes only selected items.

Responsibilities:

- translate to the output language (default: Ukrainian)
- normalize title/body into the project's publication style
- create concise summary text for feeds/cards
- preserve attribution and a link to the original full article
- reject malformed outputs that violate guardrails

Output:

- create or update `curated_news_items`
- set `status = ready` when publication payload is valid

## Prompt and Guardrail Model

Each stage uses:

- editable base prompt
- append-only guardrail prompt

Guardrails are operator-managed in admin but are always applied after the base prompt. This lets the team iterate on tone and filtering without weakening safety or output structure constraints.

Recommended stage split:

- ranker prompt + ranker guardrails
- rewriter prompt + rewriter guardrails
- optional crawler cleanup prompt + crawler guardrails

## Publication API

### Internal Endpoints

- `GET /api/v1/news/ready`
  - returns curated items where `status = ready`
  - supports limit/order filters for publication consumers

- `POST /api/v1/news/{id}/publish`
  - marks a curated item as `published`
  - stores `published_at`
  - is idempotent for already published items

This API is intentionally small: one read endpoint for ready items, one write endpoint for status transition.

## Public Web UI

The client-facing page lists only `published` items.

Each card/page should show:

- rewritten title
- short summary
- publication timestamp
- link to the original source
- source domain/name

Older items can be paginated; drafts and ready-but-unpublished items must never appear in the public UI.

## Scheduler and Retention

Two cron-like jobs are required:

- crawl job
- cleanup job

### Crawl Job

- frequency is configurable in admin
- can be triggered manually by operators

### Cleanup Job

- removes or hard-expires raw items after configured TTL
- frequency is configurable in admin
- should clean only temporary `raw_news_items`, not curated or published content

This directly matches the requirement to drop temporary news on a schedule.

## Admin UX

Admin should provide:

- source registry management
- prompt and guardrail editors
- crawl cadence and cleanup cadence controls
- raw TTL control
- proxy settings with a master enable/disable flag
- resource inventory: active sources, selected models, crawler adapter, scheduler health
- manual actions: run crawl now, run cleanup now

Proxy usage defaults to off. If enabled, the proxy can be global and optionally overridden per source.

## Decisions

### Decision: Separate Python service

- **Why**: the requested crawler and two-stage AI workflow align better with a Python ecosystem and avoid coupling experimental ingestion logic to the core platform runtime
- **Alternative**: extend the PHP platform directly, which would reduce service count but conflict with the explicit Python requirement

### Decision: Dedicated Postgres database

- **Why**: isolates crawler/editorial data and avoids schema collision with core platform entities
- **Alternative**: shared schema in the existing database, which is simpler operationally but weaker in isolation

### Decision: Schema-agnostic crawling

- **Why**: source coverage must remain broad and not depend on bespoke parsers
- **Alternative**: source-specific scrapers, which are more reliable per site but too expensive to maintain at this stage

### Decision: Two separate AI stages

- **Why**: ranking and rewriting are different concerns and need different prompts, guardrails, and failure handling
- **Alternative**: one monolithic prompt, which is simpler but harder to tune and audit

## Risks / Trade-offs

- Some websites will still parse poorly without source-specific tuning.
  - Mitigation: keep the crawler adapter replaceable and store failure metrics per source.
- A separate database increases operational overhead.
  - Mitigation: keep it within the same Postgres cluster initially.
- Aggressive cleanup can remove raw evidence before operators inspect failures.
  - Mitigation: make TTL and cleanup cadence configurable and log cleanup runs.
- Poor prompt tuning can select weak articles or distort source meaning.
  - Mitigation: expose editable prompts and non-bypassable guardrails in admin.

## Migration Plan

1. Add the Python service scaffold and runtime wiring.
2. Provision a dedicated Postgres database.
3. Create migrations and base settings.
4. Implement source ingestion and raw storage.
5. Implement the two AI stages.
6. Expose admin, publication API, and public web routes.
7. Enable scheduled crawl and cleanup jobs.

## Open Questions

- Should the public web UI support only a list page in MVP, or also individual detail pages?
- Should `published` status be set only via API, or also manually from admin UI?
- Which LLM/provider should be the default for ranking versus rewriting?
- Do we need per-source language filters at launch, or is AI-topic filtering enough for the first version?
