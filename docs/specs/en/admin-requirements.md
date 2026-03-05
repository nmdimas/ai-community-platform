# Admin Layer Requirements

## Goal

The `admin` layer is the internal interface for managing the platform, agents, configurations, and moderation-related workflows.

## Core Requirements

- the admin layer must remain logically and technically separated from the public web
- the admin layer must be accessible only to authenticated users with the correct roles
- all critical platform operations should be available either through admin or controlled chat commands

## Required Admin Scenarios

- view the list of agents and their statuses
- enable / disable agents
- inspect agent configurations
- view moderation queues, when present
- inspect integration health and background task state

## Access And Roles

- `admin` has full access to platform management
- `moderator` may have limited access to moderation and knowledge-related operations
- `user` must not have access to the admin layer

## Audit And Control

- all meaningful admin actions must be logged
- agent configuration changes must be traceable
- dangerous operations must not execute without explicit role context

## Agent Requirements

- agents must not bypass admin decisions directly
- if agent behavior is configuration-driven, that configuration must be managed through a platform-owned path
- agent state must not exist only in UI memory without platform persistence

## Security

- the admin layer must be protected by authentication and authorization
- admin routes must not be exposed as public endpoints without enforcement
- access to admin APIs should be limited by role-aware rules

## Out Of Scope For MVP

- complex custom workflow builders
- granular RBAC beyond the baseline role model
- a separate multi-stage approval engine
