# Change: Add AI News Maker Agent

## Why

The platform needs a dedicated agent that continuously collects AI news from open web sources, filters the most relevant items, rewrites them into a publication-ready format, and exposes both operator controls and public output.

The requested workflow is broader than the current manual `News Digest` concept: it introduces external web crawling, a two-stage AI editorial pipeline, a dedicated Postgres database, admin-managed guardrails, and a public news feed.

## What Changes

- **New service: `news-maker-agent`** — standalone Python service (FastAPI-based) for crawling, evaluation, rewriting, scheduling, and publication APIs
- **New storage boundary: dedicated Postgres database** — separate database for the agent, isolated from the core platform schema
- **New capability: universal source ingestion** — crawl open websites through an AI-friendly crawler/parser adapter without site-specific schemas; store parsed items in a temporary raw-news table
- **New capability: two-stage editorial pipeline** — first AI agent scores and selects up to 10 best raw items per run; second AI agent translates, normalizes style, enriches references, and prepares publication-ready news cards
- **New capability: publication API** — endpoint to fetch items with `ready` status and endpoint to mark an item as `published`
- **New capability: admin console** — flexible settings for prompts, guardrails, source registry, crawl schedules, retention windows, model/resource selection, and optional proxy configuration (disabled by default)
- **New capability: public news web UI** — client-facing page that lists all published news items
- **New capability: retention and scheduler controls** — cron-driven cleanup of temporary raw items and configurable crawl / cleanup frequency

## Impact

- Affected specs: news-ingestion, news-curation, news-publication-api, news-admin, news-web, news-retention
- Affected code: new `apps/news-maker-agent/` service, admin integration, public web routes, deployment/runtime config, `openspec/`
- **New external dependencies**: Python runtime, FastAPI stack, Postgres connection for a dedicated database, AI-capable crawler/parser library (Crawl4AI-class or equivalent), LLM provider(s)
- **BREAKING (architecture)**: extends the current MVP assumption of a single database by introducing one additional dedicated Postgres database for the news-maker agent
