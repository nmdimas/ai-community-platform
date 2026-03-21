# Proposal: add-pipeline-cost-tracker

## Summary

Додати модуль підрахунку приблизної вартості використання AI провайдерів (Anthropic, OpenAI, Google, OpenRouter) в builder pipeline. Модуль читає token usage з `.meta.json` файлів, розраховує вартість за прайсинг провайдера, і показує % використаного ліміту підписки в pipeline monitor.

## Motivation

Builder pipeline витрачає токени через кілька провайдерів (Anthropic Claude, OpenAI Codex, Google Gemini, OpenRouter). Зараз немає способу відстежувати:
- Скільки коштує кожна задача та кожен агент
- Який % підписки/бюджету вже використано
- Коли pipeline наближається до rate limit

## Scope

### In scope
- Bash-модуль `builder/cost-tracker.sh` з функціями підрахунку
- ENV конфігурація тарифного плану (`PIPELINE_PROVIDER_*` змінні)
- `.env.local.example` з коментарями по тарифних планах
- Інтеграція в pipeline.sh (emit cost events) та monitor Activity tab
- Підрахунок на основі token counts з `.meta.json` × pricing per 1M tokens

### Out of scope
- Реальне API до провайдерів для перевірки лімітів (їх немає)
- Точний підрахунок (тільки приблизний, на основі публічних цін)
- Billing інтеграції чи платіжні системи

## Design

Модуль буде standalone bash script `builder/cost-tracker.sh` який:
1. Читає pricing config з ENV (`PIPELINE_PLAN_ANTHROPIC`, `PIPELINE_PLAN_OPENAI`, etc.)
2. Парсить `.meta.json` файли з поточного batch
3. Розраховує cost per agent step: `(input_tokens × input_price + output_tokens × output_price + cache_read × cache_price) / 1_000_000`
4. Агрегує total cost per provider та % від ліміту підписки
5. Експортує результати для monitor через events.log

## Dependencies

- Існуючі `.meta.json` файли (вже є, мають `tokens.input_tokens`, `tokens.output_tokens`, `tokens.cache_read`, `tokens.cost`)
- `builder/pipeline.sh` (emit events)
- `builder/monitor/pipeline-monitor.sh` (display)
