## Context
We are migrating from a single-tenant MVP to a multi-tenant platform where a Tenant is tied to a User rather than a domain structure. We need to define how permissions (RBAC) work in Symfony and how Agents are securely isolated or shared across tenants.

## Goals / Non-Goals
- Goals: User-to-Tenant relationship modeling, Symfony RBAC setup, Tenant CRUD with safety guards, admin tenant switcher, Agent tenant isolation and 'Shared' toggle.
- Non-Goals: Physical database isolation per tenant in MVP (we will use logical isolation via `tenant_id` for now).

## Decisions
- Decision: **Symfony Security RBAC.** We will use Symfony's native Role Hierarchy (`ROLE_USER`, `ROLE_TENANT_ADMIN`, `ROLE_SUPER_ADMIN`) alongside attribute-based Voters (`TenantVoter`, `AgentVoter`) for fine-grained access control instead of complex ACL packages.
- Decision: **Tenant Ownership.** A Tenant belongs to (or has many) Users. A User can belong to multiple Tenants, thus requiring a Tenant Switcher in the Admin UI.
- Decision: **Agent Isolation.** Agent installations belong to a specific Tenant. If an agent is not marked as `shared: true`, an attempt by another tenant to install it is blocked, enforcing separate instances per tenant.
- Decision: **Safe Deletion.** A validator/service will verify no active crons or running agents exist before allowing a Tenant to be deleted.

## Risks / Trade-offs
- Risk: Cross-tenant data leakage. Mitigation: Enforce `TenantContext` in queries and Doctrine filters.
- Risk: "Shared" agents might access sensitive per-tenant data if not careful. Mitigation: Shared agents must still process messages strictly within a designated Tenant context.

## Migration Plan
Since this is an early MVP phase, no massive data migration is planned. New schemas will be created via standard Doctrine migrations.

## Open Questions
- Should "shared agents" be global or only shared among a specific group of tenants? (MVP assumes globally shared if checkbox is checked).
