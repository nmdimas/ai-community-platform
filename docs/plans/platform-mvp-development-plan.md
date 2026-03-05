# Development Plan: Platform MVP

## 1. Goal

Реалізувати базове ядро платформи, яке вміє приймати події з Telegram, маршрутизувати команди та запускати агентів через єдиний registry.

## 2. Dependencies

- зафіксований стек: `PHP 8.5`, `Symfony 7`
- dev tooling: `Codeception`, `PHPStan`, `PHP CS Fixer`
- delivery tooling: `GitLab CI`, `glab`
- доступ до Telegram Bot API
- Postgres schema для базових таблиць

## 3. Work Breakdown

### Phase 1: Foundation

- [ ] Ініціалізувати `Symfony 7`-проєкт під `PHP 8.5`
- [ ] Зафіксувати формат внутрішніх подій
- [ ] Описати manifest contract для агентів
- [ ] Підключити базову конфігурацію `PHPStan`
- [ ] Підключити базову конфігурацію `PHP CS Fixer`
- [ ] Підключити базову конфігурацію `Codeception`

### Phase 2: Core Platform

- [ ] Реалізувати Telegram adapter
- [ ] Реалізувати command router
- [ ] Реалізувати agent registry з enable/disable
- [ ] Додати role checks для команд

### Phase 3: Shared Infrastructure

- [ ] Описати і створити первинну схему БД
- [ ] Додати storage abstraction
- [ ] Додати full-text search для knowledge
- [ ] Додати structured logs і healthcheck

### Phase 4: Validation

- [ ] Перевірити `/help`, `/agents`, `/agent enable`, `/agent disable`
- [ ] Перевірити, що вимкнений агент не отримує події
- [ ] Перевірити базовий happy path з одним агентом
- [ ] Налаштувати `GitLab CI` pipeline для тестів, аналізу і code style
- [ ] Зафіксувати базові `glab`-команди для локальної роботи з pipeline/MR

## 4. Risks

- рання прив'язка до стеку потребує дисципліни щодо сумісності пакетів
- відсутність чітких контрактів між core і агентами
- складнощі з Telegram webhook/polling flow

## 5. Exit Criteria

- один Telegram чат підключений
- платформа вміє керувати агентами командами
- принаймні один агент працює через спільний контракт
