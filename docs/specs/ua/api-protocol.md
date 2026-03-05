# API-Протокол І Вимоги До Контрактів

## Мета

`API`-шар є системним контрактом між core-platform, UI, агентами та зовнішніми інтеграціями. Він повинен бути стабільним, документованим і контрольованим.

## Базові Принципи

- API належить core-platform і є platform-owned контрактом
- API не повинен залежати від випадкової внутрішньої структури UI
- API має мати чітке versioning-правило
- кожен endpoint повинен мати передбачувані request/response контракти

## Обов'язкові Категорії API

- platform management API
- agent configuration API
- data access API для knowledge, locations, digests, fraud signals
- internal integration API для platform modules

## OpenAPI Є Обов'язковою

Для всіх HTTP API в проєкті `OpenAPI`-документація є обов'язковою.

Це означає:

- кожен публічний або інтеграційний endpoint повинен бути описаний в OpenAPI
- схема повинна включати request body, params, response, error cases і auth expectations
- OpenAPI повинна оновлюватися разом зі змінами API-контракту
- undocumented endpoint не вважається повноцінною частиною контракту

## Мінімальні Вимоги До Endpoint-ів

- стабільний URL і зрозуміле призначення
- явний HTTP method
- структурований JSON response для API-викликів
- стандартизований error payload
- ідентифікований auth mode
- version-aware зміни без "тихого" лому контракту

## Auth І Доступ

- API повинен чітко розділяти public, admin і internal доступ
- endpoint повинен мати явно визначену модель авторизації
- доступ до admin і internal API без належної авторизації заборонений

## Вимоги До Агента Як API-Споживача

- агент повинен працювати тільки з documented API-контрактом
- агент не повинен покладатися на приховані поля або неописану поведінку
- зміни API без оновлення документації вважаються некоректними

## Версіонування

- breaking changes повинні бути явними
- нові поля можуть додаватися лише backward-compatible способом
- deprecated endpoint-и повинні бути позначені в документації

## Out Of Scope Для MVP

- повноцінний external developer portal
- публічний API marketplace
- складний multi-version API gateway
