# Development Plan: Knowledge Extractor

## 1. Goal

Запустити перший корисний агент, який дозволяє зберігати повідомлення у wiki та знаходити їх через пошук.

## 2. Dependencies

- platform command router
- таблиці `messages` і `knowledge`
- full-text search

## 3. Work Breakdown

### Phase 1: Design

- [ ] Уточнити UX команди `/wiki add`
- [ ] Визначити формат збереження `title/body/tags`

### Phase 2: Data

- [ ] Описати схему таблиці `knowledge`
- [ ] Додати індекси для пошуку

### Phase 3: Implementation

- [ ] Реалізувати handler для `/wiki add`
- [ ] Реалізувати handler для `/wiki <query>`
- [ ] Додати валідацію reply context
- [ ] Додати логування помилок і подій збереження

### Phase 4: Validation

- [ ] Перевірити збереження reply-повідомлення
- [ ] Перевірити пошук по релевантних ключових словах
- [ ] Перевірити сценарій "нічого не знайдено"

## 4. Risks

- слабка релевантність пошуку на чистому full-text
- низька дисципліна модераторів щодо ручного додавання знань

## 5. Exit Criteria

- модератор може зберегти knowledge entry з чату
- учасник може знайти knowledge entry командою
