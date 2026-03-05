# Plan: OpenClaw Observability Rollout (Multi-Agent, Multi-Stack)

## 1. Objective

Establish end-to-end tracing and debugging for agent flows where `OpenClaw` is the entrypoint and requests fan out through `core` to `PHP`, `Python`, and `TypeScript` agents.

## 2. Target Outcome

For a single user request, operators can see one connected trace showing:

- who called whom (`OpenClaw` -> `core` -> target agents)
- model calls, tools, token usage, and latency per hop
- failures and retries with preserved correlation IDs

## 3. Architecture

1. `OpenClaw` creates root span and correlation headers.
2. `core` propagates context to downstream A2A calls.
3. each agent emits local `llm.inference`, `tool.call`, and `a2a.call` spans.
4. telemetry is exported via OTLP to a central collector and then to Langfuse.

## 4. Implementation Phases

## Phase 0: Contracts (1-2 days)

- approve correlation header contract and A2A metadata fields
- publish platform-wide requirements in `docs/agent-requirements/`
- align naming of span attributes across languages

## Phase 1: OpenClaw Instrumentation (2-3 days)

- add root request span creation in `OpenClaw`
- add propagation of `traceparent/tracestate/baggage`
- attach `x-request-id`, `x-agent-run-id`, `x-a2a-hop`
- instrument routing/tool invocation spans

## Phase 2: Core Propagation Layer (2-3 days)

- ensure `core` keeps incoming trace context
- propagate context to all A2A downstream calls
- enforce hop increment and idempotent `request_id` behavior
- add guard logs when required headers are missing

## Phase 3: Agent Instrumentation (3-5 days)

- PHP agents: add middleware/observer for A2A + LLM + tool spans
- Python agents: add OTEL middleware and Langfuse callbacks
- TypeScript agents: add OTEL + Langfuse processors/callbacks
- normalize span attributes and token/cost fields

## Phase 4: Validation & Operations (2-3 days)

- create smoke test: one synthetic request crossing at least 2 agents
- verify a single connected trace in Langfuse
- add dashboards/alerts for error rate, p95 latency, token spikes
- add runbook for trace-based incident debugging

## 5. Deliverables

- shared observability requirements document
- propagated trace context across `OpenClaw`/`core`/agents
- baseline trace dashboard in Langfuse
- troubleshooting runbook for on-call use

## 6. Acceptance Criteria

1. A request entering `OpenClaw` appears as one trace in Langfuse across all hops.
2. Every A2A hop includes `x-request-id` and `x-agent-run-id`.
3. Each LLM call reports model + input/output tokens.
4. At least one tool call per agent appears as child span when used.
5. Failure in downstream agent is visible in parent trace with correlated IDs.

## 7. Risks and Mitigations

- Risk: inconsistent attribute names across stacks.
  - Mitigation: enforce a shared attribute dictionary in docs and lint checks.
- Risk: sensitive data leakage in traces.
  - Mitigation: default redaction and environment-specific prompt logging controls.
- Risk: partial propagation causing trace breaks.
  - Mitigation: middleware-level propagation tests in CI.

## 8. Rollback Strategy

- keep instrumentation behind env flags per service
- if instability appears, disable new exporters first, keep correlation headers
- preserve request handling path even when telemetry pipeline is degraded
