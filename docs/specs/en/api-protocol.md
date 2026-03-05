# API Protocol And Contract Requirements

## Goal

The `api` layer is the system contract between the core-platform, UI layers, agents, and external integrations. It must remain stable, documented, and controlled.

## Core Principles

- the API is owned by the core-platform and is a platform-owned contract
- the API must not depend on incidental UI internals
- the API must have clear versioning rules
- every endpoint should expose predictable request/response contracts

## Required API Categories

- platform management API
- agent configuration API
- data access API for knowledge, locations, digests, and fraud signals
- internal integration API for platform modules

## OpenAPI Is Mandatory

`OpenAPI` documentation is mandatory for all HTTP APIs in the project.

This means:

- every public or integration-facing endpoint must be described in OpenAPI
- the schema must include request bodies, params, responses, error cases, and auth expectations
- OpenAPI must be updated together with API contract changes
- an undocumented endpoint is not considered a complete contract

## Minimum Endpoint Requirements

- a stable URL and clear purpose
- an explicit HTTP method
- structured JSON responses for API calls
- a standardized error payload
- an identified auth mode
- version-aware changes without silent contract breakage

## Auth And Access

- the API must clearly separate public, admin, and internal access
- each endpoint must have an explicit authorization model
- admin and internal APIs must not be accessible without proper authorization

## Agent Requirements As API Consumers

- an agent must work only with documented API contracts
- an agent must not rely on hidden fields or undocumented behavior
- API changes without documentation updates should be treated as invalid

## Versioning

- breaking changes must be explicit
- new fields may be added only in a backward-compatible way
- deprecated endpoints must be marked in documentation

## Out Of Scope For MVP

- a full external developer portal
- a public API marketplace
- a complex multi-version API gateway
