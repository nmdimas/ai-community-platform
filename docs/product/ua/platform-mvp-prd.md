# PRD: AI Community Platform MVP

## 1. Context

Ком'юніті-чати швидко перетворюються на неструктуровану стрічку, де:

- корисні знання губляться
- важливі новини дублюються або зникають
- перевірені контакти та сервіси не мають єдиного місця
- зростає ризик скаму, маніпуляцій і шуму

## 2. Problem Statement

Адміни та модератори не мають простого інструмента, який:

- структурує знання прямо поверх чату
- дає модульну функціональність без окремої розробки під кожний кейс
- дозволяє запускати та вимикати окремі сценарії автоматизації

## 3. Product Goal

Запустити MVP платформи модульних агентів для одного Telegram-ком'юніті, де кожен агент вирішує окрему задачу, а core-платформа дає спільну інфраструктуру для підключення, конфігурації та виконання.

## 4. Success Criteria

- у чаті працює одна інтеграція з Telegram
- агенти можна вмикати та вимикати командами
- мінімум 2 агенти створюють помітну користь у першому пілоті
- модератор розуміє, чому агент зробив конкретну дію

## 5. MVP Scope

### In Scope

- один Telegram-чат
- подієва шина для повідомлень, команд, редагувань і видалень
- реєстр агентів з конфігами та статусом `enabled/disabled`
- ролі `admin`, `moderator`, `user`
- Postgres як базове сховище
- базовий full-text пошук
- chat-команди для керування агентами
- стартові агенти:
  - Knowledge Extractor / Community Wiki
  - Locations Catalog
  - News Digest
  - Anti-fraud Signals (lite)

### Out of Scope

- мульти-тенантність
- web admin panel
- складна RBAC/ACL модель
- зовнішній маркетплейс агентів
- складні зовнішні інтеграції

## 6. Users

### Admin / Owner

Хоче швидко запускати модулі та контролювати поведінку бота без окремого інтерфейсу.

### Moderator

Хоче зменшити шум, маркувати корисну інформацію та мати сигнали ризику.

### Member

Хоче швидко знаходити релевантні відповіді та апдейти без перечитування всього чату.

## 7. Core User Stories

- Як `admin`, я хочу побачити список агентів і їх статуси, щоб керувати платформою в чаті.
- Як `admin`, я хочу ввімкнути або вимкнути агента командою, щоб швидко тестувати сценарії.
- Як `moderator`, я хочу зберегти корисне повідомлення у wiki, щоб знання не губились.
- Як `member`, я хочу знайти відповідь командою, щоб не ставити ті самі питання повторно.
- Як `moderator`, я хочу бачити причини fraud signal, щоб не покладатись на "чорний ящик".

## 8. Functional Requirements

### Platform

- Приймати події з Telegram.
- Нормалізувати події у внутрішній формат.
- Доставляти події активним агентам.
- Зберігати конфіг та стан агентів.
- Валідовувати доступ до команд за роллю.

### Core-Platform Stack

- `PHP 8.5`
- `Symfony 7`
- `Codeception` для тестів
- `PHPStan` для статичного аналізу
- `PHP CS Fixer` для стилю коду
- `GitLab CI` для CI pipeline
- `glab` для роботи з GitLab з CLI

### Commands

- `/help`
- `/agents`
- `/agent enable <name>`
- `/agent disable <name>`

### Shared Services

- storage
- search
- audit logging
- scheduler для періодичних задач

## 9. Data Model

- `communities(id, name, channel_id, created_at)`
- `agents(id, community_id, name, enabled, config_json, created_at)`
- `messages(id, community_id, platform_msg_id, user_id, text, ts, meta_json)`
- `knowledge(id, community_id, title, body, tags, source_msg_id, created_by, created_at)`
- `locations(id, community_id, name, description, tags, address_text, contact_text, source_msg_id, status, created_by, created_at)`
- `digests(id, community_id, period_start, period_end, body, created_at)`
- `fraud_signals(id, community_id, msg_id, score, reasons_json, created_at)`

## 10. Non-Functional Requirements

- проста команда має відповідати в межах 2-3 секунд
- автоматичні дії мають бути пояснюваними
- жодних автоматичних банів у MVP
- structured logs та healthcheck обов'язкові

## 11. Risks

- шум від агентів, якщо пороги будуть занадто агресивними
- низька довіра до антифрод-сигналів
- низька якість wiki без ручної модерації

## 12. Open Questions

- Яка мова інтерфейсу першого пілоту: тільки UA чи UA/EN?
- Чи потрібен LLM вже в MVP, чи стартуємо з rule-based логікою?
- Чи digest публікується автоматично, чи тільки вручну на старті?
