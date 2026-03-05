# Development Plan: Anti-fraud Signals

## 1. Goal

Запустити пояснюваний rule-based антифрод-агент, який підсвічує ризики без автоматичних санкцій.

## 2. Dependencies

- platform event handling
- таблиця `messages`
- таблиця `fraud_signals`

## 3. Work Breakdown

### Phase 1: Design

- [ ] Визначити початковий набір fraud rules
- [ ] Визначити score thresholds
- [ ] Узгодити, де показувати сигнал: публічно чи тільки модераторам

### Phase 2: Data

- [ ] Описати схему `fraud_signals`
- [ ] Описати формат `reasons_json`

### Phase 3: Implementation

- [ ] Реалізувати аналіз `message.created`
- [ ] Реалізувати збереження fraud score
- [ ] Реалізувати `/fraud why` для reply-повідомлення
- [ ] Додати rate-limit, щоб агент не шумів

### Phase 4: Validation

- [ ] Перевірити спрацювання на тестових scam-like повідомленнях
- [ ] Перевірити, що низькі score не породжують шум
- [ ] Перевірити зрозумілість explanations для модератора

## 4. Risks

- фальшиві спрацювання на легітимних повідомленнях
- недовіра, якщо формулювання причин буде занадто загальним

## 5. Exit Criteria

- модератор бачить signal з причинами
- агент не робить автоматичних каральних дій
