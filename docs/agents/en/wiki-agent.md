# Wiki Agent

`wiki-agent` is a standalone TypeScript service that adds a public wiki and a dedicated `wiki-admin` surface without changing the existing `knowledge-agent`.

## Responsibilities

- serve the public wiki at `/wiki`
- serve page detail URLs at `/wiki/page/{slug}`
- provide `wiki-admin` login and CRUD
- index published pages into an agent-owned OpenSearch index
- answer public questions with a grounded wiki chat

## Runtime Boundaries

- language: TypeScript / Node.js
- container: dedicated `wiki-agent`
- Postgres: shared database, isolated schema `wiki_agent`
- OpenSearch: isolated index namespace `wiki_agent_pages`
- RabbitMQ: reserved agent-local exchange/queue namespace for later async flows

## Public Surface

- `GET /wiki`
- `GET /wiki/page/{slug}`
- `POST /api/v1/wiki/chat`

## Required Agent Endpoints

- `GET /health`
- `GET /api/v1/manifest`
- `POST /api/v1/a2a`
