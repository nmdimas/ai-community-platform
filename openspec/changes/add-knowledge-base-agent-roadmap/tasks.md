# Tasks: add-knowledge-base-agent-roadmap

This change is a planning artifact only. Tasks will be broken into separate, targeted proposals when each item is prioritized for implementation. No implementation tasks are listed here.

## Roadmap Items to Track

- [ ] R1 Auto-deduplication via LLM similarity check before indexing
- [ ] R2 Confidence scoring (0–1) per extracted entry with review queue
- [ ] R3 Search usage feedback loop + entry staleness tracking
- [ ] R4 Multi-language extraction support (Ukrainian + English)
- [ ] R5 Real-time `message.created` event ingestion
- [ ] R6 Auto-trigger extraction on moderator emoji reaction
- [ ] R7 Telegram forward message ingestion with attribution
- [ ] R8 Scheduled re-extraction for low-quality entries
- [ ] R9 LLM query expansion before hybrid search
- [ ] R10 Related entries panel on knowledge entry page
- [ ] R11 Faceted filtering in web encyclopedia (tags, date, confidence)
- [ ] R12 Personalized search ranking based on browsing history
- [ ] R13 OpenSearch alias-based zero-downtime reindex workflow
- [ ] R14 Embedding cache with Redis (content hash key)
- [ ] R15 RabbitMQ-driven worker autoscaling
- [ ] R16 Cost dashboard in admin (LLM tokens, embedding cost, volume)
- [ ] R17 Community voting on knowledge entries (upvote/downvote)
- [ ] R18 "Запропонувати збереження" flow from zero-result searches
- [ ] R19 Knowledge export: JSON / Markdown / PDF
- [ ] R20 Embeddable search widget for external tools
