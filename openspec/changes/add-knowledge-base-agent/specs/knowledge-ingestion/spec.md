## ADDED Requirements

### Requirement: Message Batch Upload
The system SHALL accept a batch of chat messages via REST API and enqueue them for asynchronous knowledge extraction processing.

#### Scenario: Valid message batch accepted
- **WHEN** an authorized client sends POST `/api/v1/knowledge/upload` with a JSON array of messages
- **THEN** the system returns `202 Accepted` with a `batch_id` and enqueues the messages for chunking

#### Scenario: Empty batch rejected
- **WHEN** the client sends an empty messages array
- **THEN** the system returns `422 Unprocessable Entity` with a validation error

#### Scenario: Batch too large rejected
- **WHEN** the client sends more than 10,000 messages in a single request
- **THEN** the system returns `413 Payload Too Large` with guidance to split the batch

---

### Requirement: Message Chunking
The system SHALL split uploaded message batches into overlapping chunks before enqueuing, using time-window and count-based rules.

#### Scenario: Time-window chunking
- **WHEN** messages span more than a 15-minute gap
- **THEN** the system creates a new chunk boundary at the gap

#### Scenario: Size cap chunking
- **WHEN** a time-window group contains more than 50 messages
- **THEN** the system splits it into sub-chunks of at most 50 messages each

#### Scenario: Overlap applied
- **WHEN** consecutive chunks are created
- **THEN** each chunk includes the last 5 messages of the previous chunk for context continuity

---

### Requirement: Chunk Idempotency
The system SHALL assign each chunk a deterministic hash and skip re-processing of already completed chunks.

#### Scenario: Duplicate chunk skipped
- **WHEN** a chunk with a hash matching a `completed` record in `processed_chunks` is received by the worker
- **THEN** the worker logs the skip and does not call the LLM

#### Scenario: Failed chunk retried
- **WHEN** a chunk has `status = failed` with fewer than 3 attempts
- **THEN** the worker re-enqueues it for processing

#### Scenario: Exhausted chunk dead-lettered
- **WHEN** a chunk has failed 3 or more times
- **THEN** the worker moves it to `knowledge.dlq` and updates status to `dead_lettered`

---

### Requirement: A2A On-Demand Extraction
The system SHALL accept A2A requests from the core-agent with intent `extract_from_messages` for synchronous single-chunk extraction.

#### Scenario: On-demand A2A extraction success
- **WHEN** core-agent sends A2A request with intent `extract_from_messages` and a message array
- **THEN** the agent runs the neuron-ai workflow synchronously and returns `status: completed` with extracted knowledge entries

#### Scenario: Non-valuable chunk skipped
- **WHEN** the `AnalyzeMessages` workflow node determines the chunk contains no extractable knowledge
- **THEN** the agent returns `status: completed` with `result.entries: []` and `result.skipped_reason: not_valuable`
