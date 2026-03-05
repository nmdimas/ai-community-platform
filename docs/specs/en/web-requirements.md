# Web Layer Requirements

## Goal

The `web` layer is the public-facing interface of the platform. It should provide clear access to community data without requiring direct chat-command usage or admin-level access.

## Core Requirements

- the public web must remain separate from the admin layer
- the web layer must not expose internal admin functions
- the web layer should read data only through platform-owned APIs or internal application services
- the web layer should support stable URLs for key public modules

## Baseline Public Surface

- locations catalog pages
- knowledge list / detail pages
- public digest views, if enabled

## URL And Navigation

- the public web must not use admin-prefixed routes
- public routes should be predictable, short, and stable
- deep links from chat should resolve in the public web without admin context

## Security And Access

- the public web does not require admin authentication by default
- write operations through the public web are either disallowed or tightly constrained in MVP
- access to private or moderation-only data through the public web is forbidden

## Data Requirements

- the web layer is not the source of truth for data
- all data mutations happen through the core-platform
- the web layer should expose only publishable state

## Agent Requirements

- agents may generate deep links into the public web
- agents must not rely on unstable or temporary UI routes
- if an agent links to the web layer, that URL should be treated as a stable contract

## Out Of Scope For MVP

- advanced personalized user accounts
- self-service admin behavior in the public web
- complex client-side permission models
