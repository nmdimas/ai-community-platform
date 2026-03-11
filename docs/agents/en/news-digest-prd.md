# PRD: News Digest

> TODO: translate from Ukrainian version (`docs/agents/ua/news-digest-prd.md`)

## 1. Summary

The agent compiles important updates into a short digest so the community can review key changes
without rereading long message streams.

## 2. Goal

Provide the community with a structured "what changed" summary instead of manual thread review.

## 3. Users and Jobs-to-be-Done

- `admin`: trigger a digest manually and validate its usefulness
- `member`: read the main updates quickly
- `moderator`: reduce duplicate discussion around the same news

## 4. Scope

### In Scope

- manual digest trigger by command
- manual digest trigger from the `news-maker-agent` admin UI
- collecting messages for the selected time window
- minimal deduplication of similar topics

### Out of Scope

- advanced topic classification
- personalized digests
- scheduled auto-posting in the first version

## 5. Inputs

- `command.received`
- `schedule.tick` (reserved for a later stage)
- `messages`

## 6. Outputs

- record in `digests`
- channel post delivered through `Core A2A -> openclaw.send_message`

## 7. UX / Commands

- `/digest now`
- `Digest now` button in the `news-maker-agent` admin UI

## 8. Data Model Usage

### Reads

- `messages`
- `digests`

### Writes

- `digests(period_start, period_end, body, created_at)`

## 9. Rules / Heuristics

- use the last 24 hours or 7 days depending on configuration
- group similar messages
- exclude system noise and technical commands
- keep the response compact: 3-7 items

## 10. Failure Modes

- if there is too little data, return a short message instead of an empty digest
- if generation fails, log the error and avoid duplicate posting
- if channel delivery fails after digest creation, keep the digest persisted and log a warning

## 11. Success Metrics

- number of manual digest runs
- reactions/replies to digest messages
- fewer repeated questions about recent changes
