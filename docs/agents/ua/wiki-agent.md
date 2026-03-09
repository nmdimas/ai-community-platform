# Wiki Agent

`wiki-agent` це окремий TypeScript-сервіс, який додає публічну wiki та окремий `wiki-admin`, не змінюючи існуючий `knowledge-agent`.

## Що він робить

- показує публічну wiki на `/wiki`
- віддає сторінки за стабільними URL `/wiki/page/{slug}`
- надає окремий `wiki-admin` для CRUD і публікації сторінок
- індексує опубліковані сторінки у власний OpenSearch index
- дає вбудований AI chat, який відповідає тільки з опублікованої wiki

## Межі сервісу

- мова: TypeScript / Node.js
- окремий контейнер: `wiki-agent`
- Postgres: спільна база, окрема схема `wiki_agent`
- OpenSearch: окремий index `wiki_agent_pages`
- RabbitMQ: зарезервований власний namespace черг для подальшої автоматизації

## Основні маршрути

- `GET /wiki`
- `GET /wiki/page/{slug}`
- `POST /api/v1/wiki/chat`
- `GET /wiki-admin`
