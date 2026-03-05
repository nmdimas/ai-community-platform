# Design: Knowledge Base Agent

## Context

The platform MVP uses Postgres as primary storage and originally scoped knowledge search to basic full-text. This proposal elevates the Knowledge Extractor agent into a full Knowledge Base Agent with vector + keyword hybrid search, asynchronous ingestion, a public web encyclopedia, and admin management.

The agent is a standalone PHP Symfony service (`apps/knowledge-agent/`) that communicates with the core platform via A2A contract and processes message chunks via RabbitMQ.

## Goals / Non-Goals

### Goals
- Automatically extract structured knowledge from message batches using LLM (neuron-ai)
- Provide hybrid search (BM25 + kNN) in OpenSearch with message-level link metadata
- Expose a knowledge tree for hierarchical navigation
- Publish a Ukrainian-language web encyclopedia
- Provide an admin panel for agent configuration and knowledge CRUD
- Integrate with the platform A2A protocol for on-demand agent calls
- Keep chunk processing idempotent and fault-tolerant

### Non-Goals
- Replace Postgres as primary platform storage (OpenSearch is for knowledge only)
- Real-time streaming search results in MVP
- Multi-tenant / multi-chat knowledge separation in first release
- Automatic moderation or trust scoring of extracted knowledge (advisory only)
- Complex RBAC beyond admin / member

---

## Architecture

```
Telegram Chat
     │
     ▼
[Core Platform]  ──A2A──▶  [KnowledgeBaseAgent]
     │                            │
     │  upload /api/messages       ├── neuron-ai Workflow
     ▼                            │    ├── AnalyzeMessages (LLM)
[knowledge-api]                   │    ├── ExtractKnowledge (LLM)
     │                            │    └── EnrichMetadata
     ▼                            │
[RabbitMQ]  ◀────────────────────┘
     │
     ▼
[KnowledgeWorker]
     │
     ├── dedup check (Postgres processed_chunks table)
     ├── LLM analysis (neuron-ai)
     │
     ▼
[OpenSearch]
  index: knowledge_entries
  fields:
    - id, title, body, tags[], category, source_type
    - source_message_id, message_link (direct Telegram link)
    - embedding (dense_vector, 1536 dims)
    - created_at, updated_at, created_by
    - tree_path (e.g. "Technology/PHP/Symfony")
```

---

## neuron-ai Workflow Design

```php
class KnowledgeExtractionWorkflow extends Workflow
{
    public function __construct(array $messageChunk)
    {
        parent::__construct(new WorkflowState([
            'messages'   => $messageChunk,
            'knowledge'  => [],
        ]));
    }

    protected function nodes(): array
    {
        return [
            new AnalyzeMessages(),    // LLM: is this chunk worth extracting?
            new ExtractKnowledge(),   // LLM: extract title, body, tags, category
            new EnrichMetadata(),     // attach message_link, source_message_id
        ];
    }
}
```

`AnalyzeMessages` node returns `is_valuable: bool` — if false, the workflow short-circuits.
`ExtractKnowledge` node returns structured JSON matching the OpenSearch document schema.
`EnrichMetadata` node resolves message links using chat/message ID from chunk metadata.

---

## OpenSearch Index Design

```json
{
  "mappings": {
    "properties": {
      "title":       { "type": "text", "analyzer": "ukrainian" },
      "body":        { "type": "text", "analyzer": "ukrainian" },
      "tags":        { "type": "keyword" },
      "category":    { "type": "keyword" },
      "tree_path":   { "type": "keyword" },
      "embedding":   { "type": "knn_vector", "dimension": 1536,
                       "method": { "name": "hnsw", "space_type": "cosinesimil" } },
      "source_message_id": { "type": "keyword" },
      "message_link":      { "type": "keyword", "index": false },
      "created_at":        { "type": "date" },
      "created_by":        { "type": "keyword" }
    }
  }
}
```

### Hybrid Search Strategy

Use OpenSearch's `hybrid` query combining:
1. `match` (BM25) on `title` + `body` — keyword relevance
2. `knn` on `embedding` — semantic similarity

Score normalization via `min_max_score` normalizer and `arithmetic_mean` combiner (weights: BM25=0.4, kNN=0.6).

---

## RabbitMQ Chunking Strategy

### Chunk Formation Rules
- **Time window**: messages within the same 15-minute window group together
- **Size cap**: max 50 messages per chunk regardless of time window
- **Overlap**: consecutive chunks share last 5 messages for context continuity

### Idempotency
- Each chunk gets a deterministic hash: `sha256(sorted message_ids)`
- `processed_chunks` Postgres table stores `(chunk_hash, status, processed_at)`
- Worker skips chunks where `status = completed`
- Failed chunks are retried up to 3 times, then moved to dead-letter queue

### Queue Topology
- Exchange: `knowledge.direct`
- Queue: `knowledge.chunks` (main processing)
- Queue: `knowledge.dlq` (dead-letter, manual review)

---

## A2A Integration

The `KnowledgeBaseAgent` implements the platform A2A contract:

```json
{
  "agent": "knowledge-base",
  "request": {
    "intent": "search_knowledge | extract_from_messages | get_tree",
    "payload": { ... },
    "request_id": "uuid",
    "trace_id": "uuid"
  },
  "response": {
    "status": "completed | needs_clarification | failed | queued",
    "result": { ... },
    "request_id": "uuid"
  }
}
```

---

## Web Architecture

**Web Encyclopedia** (`/wiki`):
- Symfony Twig or lightweight SPA (Vue 3 + Vite)
- Left sidebar: knowledge tree (category → subcategory → entry)
- Center: rendered knowledge entry (Markdown)
- Top: hybrid search bar
- Language: Ukrainian

**Admin Panel** (`/admin/knowledge`):
- Extends existing admin stub
- Settings page: toggle web encyclopedia visibility
- Agent Instructions page: base instructions editor (textarea + save), security instructions (locked textarea, always appended to LLM prompt)
- "Chat-like" instruction tester: send test input → see agent interpretation
- Knowledge CRUD: list, create, edit, delete entries (same tree view as web)

---

## API Design

Base path: `/api/v1/knowledge`

| Method | Path | Description |
|--------|------|-------------|
| POST | `/api/v1/knowledge/upload` | Upload message batch for async processing |
| GET | `/api/v1/knowledge/tree` | Get full knowledge tree |
| GET | `/api/v1/knowledge/entries/{id}` | Get single knowledge page |
| GET | `/api/v1/knowledge/search?q=&mode=hybrid` | Hybrid search |
| GET | `/api/v1/knowledge/entries` | List entries (paginated, filterable) |
| POST | `/api/v1/knowledge/entries` | Create entry (admin) |
| PUT | `/api/v1/knowledge/entries/{id}` | Update entry (admin) |
| DELETE | `/api/v1/knowledge/entries/{id}` | Delete entry (admin) |

OpenAPI spec: `openspec/knowledge-api.yaml` (generated from annotations or hand-authored).

---

## Decisions

### Decision: OpenSearch over Postgres for knowledge
- **Why**: Need kNN vector search + full-text with Ukrainian analyzer in one query; Postgres pgvector adds complexity, OpenSearch has native hybrid search
- **Alternative**: pgvector + tsvector in Postgres — simpler infra but poorer hybrid query API and no built-in HNSW

### Decision: RabbitMQ for chunk processing
- **Why**: Decouples ingestion rate from LLM processing rate; enables retries, DLQ, and monitoring without polling loops
- **Alternative**: Symfony Messenger with Postgres transport — acceptable for MVP but harder to scale and monitor

### Decision: neuron-ai PHP framework
- **Why**: Project is PHP/Symfony; neuron-ai provides Workflow abstraction, LLM provider switching, and Inspector monitoring without requiring Python services
- **Alternative**: Direct HTTP calls to OpenAI/Anthropic — simpler but no workflow abstraction, harder to maintain multi-step pipelines

### Decision: Ukrainian language for web encyclopedia
- **Why**: Target audience is Ukrainian-speaking community; all product-facing text in Ukrainian per project conventions

---

## Risks / Trade-offs

| Risk | Mitigation |
|------|-----------|
| OpenSearch + RabbitMQ increase infra complexity | Add both to compose.yaml as development services; production config managed separately |
| LLM extraction quality depends on prompt | Admin panel allows base instruction editing + security instruction enforcement |
| Embedding cost for large message history | Batch embedding calls; add cost tracking; allow disabling auto-extraction |
| Chunk context loss at boundaries | 5-message overlap between consecutive chunks |
| OpenSearch mapping changes requiring reindex | Version index name (`knowledge_entries_v1`); support reindex migrations |

---

## Migration Plan

1. Add OpenSearch and RabbitMQ to `compose.yaml`
2. Create `knowledge_entries_v1` index with mapping
3. Create `processed_chunks` Postgres table
4. Deploy `knowledge-agent` service
5. Enable via agent registry: `knowledge-base: enabled`
6. Backfill existing messages via `/api/v1/knowledge/upload` (admin action)

---

## Open Questions

- Should the web encyclopedia require authentication for read access, or be fully public? (Current assumption: publicly readable, admin-toggleable)
- Which embedding model to use by default? (Current assumption: OpenAI `text-embedding-3-small` at 1536 dims; configurable via admin)
- Should the admin "chat-like" instruction interface call the live agent or a preview mode? (Current assumption: preview mode, not live)
