# PRD: Core Agent (OpenClaw Runtime)

## Goal

Використати `OpenClaw` як runtime для `core-agent`, який оркеструє діалогову логіку, маршрутизує запити до спеціалізованих агентів і повертає узгоджену відповідь у чат.

## Role In Architecture

`OpenClaw` у цьому проєкті не є core-platform. Він розглядається як один із технічних варіантів для шару `core-agent / orchestrator`.

Розподіл відповідальності:

- `core-platform` володіє даними, правилами, ролями, registry, moderation і platform API
- `core-agent` інтерпретує намір користувача, викликає потрібні модулі й формує відповідь
- `OpenClaw` може бути runtime-движком для `core-agent`, якщо це прискорює MVP

## Why Consider OpenClaw

- швидший `time-to-demo` для чатового сценарію
- готові механізми для agent orchestration
- потенційно зручний runtime для tool-based workflows

## Recommended MVP Position

Рекомендований варіант для MVP:

- використовувати `OpenClaw` тільки як `core-agent runtime`
- не передавати йому роль `core-platform`
- не робити дані, permissions або product routing залежними від внутрішньої моделі `OpenClaw`

## Integration Boundary

### Core Platform Owns

- Telegram/platform gateway
- event ingestion
- roles and permissions
- storage and audit data
- local/dev LLM gateway configuration and request observability via `LiteLLM`
- knowledge, locations, digests, fraud signals
- agent registry and enable/disable state
- moderation flows

### Core Agent Owns

- intent routing
- clarification loop
- multi-step orchestration
- response composition
- choosing which model alias to call through the platform-owned `LiteLLM` gateway

### OpenClaw May Provide

- runtime for conversation/session handling
- tool invocation layer for the core-agent
- orchestration shell around specialized agents

## Recommended Channel Strategy

Цільова архітектура:

- `Telegram -> Platform Gateway -> internal events -> Core Agent`

Допустимий тимчасовий варіант для швидкого демо:

- `Telegram -> OpenClaw -> Core Agent -> Platform API`

Але це слід вважати короткостроковим компромісом, а не фінальною архітектурою.

## Risks

- надмірна залежність від routing/session model `OpenClaw`
- розмиття межі між platform ownership і assistant runtime
- security-ризики при підключенні сторонніх skills/extensions

## Security Requirements

- не підключати сторонні skills без рев'ю
- запускати runtime в ізоляції
- мінімізувати доступні tools та permissions
- не виносити в `OpenClaw` критичне platform ownership
- у локальній розробці не ходити напряму в provider API; LLM-виклики мають іти через `LiteLLM` для аудиту й дебагу

## Out Of Scope

- використання `OpenClaw` як джерела truth для platform state
- передача адміністрування ком'юніті в `OpenClaw`
- залежність всієї продуктної моделі від OpenClaw-specific каналів

## Success Criteria

- `core-agent` може приймати події та формувати відповіді через platform integration
- спеціалізовані агенти викликаються через уніфікований внутрішній контракт
- відключення `OpenClaw` runtime не ламає platform ownership model
