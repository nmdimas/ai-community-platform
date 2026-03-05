# ADR 0002: OpenClaw Role

## Status

Accepted for MVP planning

## Context

Проєкт будує власну `core-platform` для ком'юніті-чатів: зберігання даних, модульність, permissions, moderation, platform APIs і керування агентами.

Одночасно є потреба в `core-agent`, який:

- інтерпретує користувацькі наміри
- маршрутизує задачі до агентів
- веде clarification loop
- формує фінальну відповідь у чат

`OpenClaw` привабливий як готовий runtime для такого оркестратора, але він не повинен визначати продуктні межі платформи.

## Decision

Для MVP `OpenClaw` розглядається як `runtime for core-agent`, але не як `core-platform`.

Це означає:

- platform gateway, data ownership, permissions, moderation і registry залишаються в нашій платформі
- `OpenClaw` може використовуватись для orchestration/session/tool runtime
- інтеграція з чатами в цільовій архітектурі має бути owned платформою, а не `OpenClaw`

## Consequences

### Positive

- швидше демо без передачі ядра продукту зовнішньому runtime
- зберігається контроль над даними і правилами
- легше замінити runtime у майбутньому без зламу продуктної моделі

### Negative

- потрібно самим підтримувати чітку межу інтеграції
- з'являється додатковий integration layer між platform API і `OpenClaw`
- потрібна окрема security discipline для skills/tools

## Operational Rules

- сторонні skills/extensions підлягають рев'ю перед використанням
- runtime має запускатися з мінімальними правами
- platform data не повинні ставати `OpenClaw` source of truth

## Follow-Up

- зафіксувати внутрішній контракт між `core-platform` і `core-agent`
- визначити, чи для MVP Telegram йде напряму через platform gateway, чи тимчасово через `OpenClaw`
