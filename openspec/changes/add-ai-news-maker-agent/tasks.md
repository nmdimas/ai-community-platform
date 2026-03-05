# Tasks: add-ai-news-maker-agent

## 0. Foundation

- [ ] 0.1 Create `apps/news-maker-agent/` as a standalone Python service scaffold
- [ ] 0.2 Define runtime stack (FastAPI, scheduler, ORM/migrations, HTTP client, LLM client)
- [ ] 0.3 Add local runtime wiring for the new service and a dedicated Postgres database
- [ ] 0.4 Document environment variables for database, crawler, proxy, and AI providers

## 1. Data Model

- [ ] 1.1 Create migrations for `news_sources`, `raw_news_items`, `curated_news_items`, `agent_settings`, `scheduler_runs`
- [ ] 1.2 Define raw item lifecycle fields: `status`, `expires_at`, `crawl_run_id`, `source_url`, dedup hash
- [ ] 1.3 Define curated item lifecycle fields: `status` (`draft`, `ready`, `published`, `rejected`), `published_at`, canonical references
- [ ] 1.4 Add indexes for status-based polling, deduplication, and retention cleanup

## 2. Source Ingestion

- [ ] 2.1 Implement source registry CRUD and source-level enable/disable
- [ ] 2.2 Implement universal crawler adapter using an AI-native parser/crawler library behind a replaceable interface
- [ ] 2.3 Add raw extraction pipeline: fetch source, normalize article candidates, deduplicate, store in `raw_news_items`
- [ ] 2.4 Add optional proxy support with default `disabled`
- [ ] 2.5 Add tests for source fetch, parse normalization, and deduplication

## 3. Editorial Pipeline

- [ ] 3.1 Implement agent 1: score and rank newly crawled raw items
- [ ] 3.2 Enforce selection cap of 10 items per editorial run
- [ ] 3.3 Implement agent 2: translate, rewrite to house style, generate summary, and attach source references
- [ ] 3.4 Persist rewritten results in `curated_news_items` with `ready` status
- [ ] 3.5 Add tests for ranking, top-10 enforcement, prompt/guardrail application, and structured output validation

## 4. Admin Configuration

- [ ] 4.1 Build admin UI for source registry and crawl status overview
- [ ] 4.2 Build settings UI for prompts and immutable/append-only guardrails per stage
- [ ] 4.3 Build scheduler settings UI for crawl cadence and raw-news cleanup cadence
- [ ] 4.4 Build retention settings UI for raw item TTL
- [ ] 4.5 Build proxy settings UI with enable toggle and credential fields
- [ ] 4.6 Build resource inventory UI for configured models, crawler adapter, and enabled sources

## 5. Scheduler and Cleanup

- [ ] 5.1 Implement cron-like scheduler for crawl runs
- [ ] 5.2 Implement cron-like scheduler for expired raw item cleanup
- [ ] 5.3 Record scheduler run history and failures in `scheduler_runs`
- [ ] 5.4 Add manual admin actions to trigger crawl and cleanup immediately
- [ ] 5.5 Add tests for schedule updates, manual triggers, and retention cleanup

## 6. Publication API

- [ ] 6.1 Implement GET endpoint for publication-ready items
- [ ] 6.2 Implement POST/PATCH endpoint to mark an item as `published`
- [ ] 6.3 Prevent duplicate publication updates for already published items
- [ ] 6.4 Add auth strategy for internal/admin publication API access
- [ ] 6.5 Add API tests for happy path, idempotency, and validation errors

## 7. Public Web UI

- [ ] 7.1 Build client-facing route/page for published news listing
- [ ] 7.2 Show title, short summary, publication date, and references to original sources
- [ ] 7.3 Support pagination or incremental loading for older published items
- [ ] 7.4 Add frontend tests for published-only visibility and source-link rendering

## 8. Quality and Documentation

- [ ] 8.1 Validate OpenSpec change and keep docs aligned with implementation choices
- [ ] 8.2 Add/update product docs for the AI news maker workflow
- [ ] 8.3 Add runbook notes for scheduler, proxy usage, and cleanup operations
- [ ] 8.4 Run backend, API, and web test suites for the final implementation
