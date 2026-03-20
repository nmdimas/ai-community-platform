## 1. Documentation Updates
- [ ] 1.1 Update Agent creation documentation (`docs/`) to clarify the usefulness of multiple instances for multitenancy.
- [ ] 1.2 Detail the 'shared agent' feature and limits in documentation. Maintain Ukrainian and `.en.md` mirrors for user-facing docs.

## 2. Platform Foundation (Models & Auth)
- [ ] 2.1 Create User and Tenant Doctrine entities. Establish the relationship (a User belongs to/manages Tenants).
- [ ] 2.2 Configure Symfony Security Role Hierarchy (e.g., `ROLE_TENANT_ADMIN`, `ROLE_SUPER_ADMIN`).
- [ ] 2.3 Implement Security Voters (`TenantVoter`, `AgentVoter`) to provide RBAC and ensure users can only modify their own tenants.

## 3. Tenant Management (CRUD)
- [ ] 3.1 Implement Tenant creation and updating endpoints or controllers.
- [ ] 3.2 Implement Tenant deletion logic with safe guards (prevent deletion if there are active agents or cron jobs tied to the tenant).

## 4. Admin UI Updates
- [ ] 4.1 Update the top navigation bar to include a Tenant Switcher dropdown.
- [ ] 4.2 Add Tenant Management views (CRUD forms) to the admin panel.

## 5. Agent Isolation
- [ ] 5.1 Add a `shared` boolean property (rendered as a checkbox in UI) to the Agent model/manifest.
- [ ] 5.2 Tie Agent installations strictly to a specific `tenant_id`.
- [ ] 5.3 Enforce installation constraint: if an agent is non-shared (`shared: false`) and is already installed in a tenant, prevent its installation elsewhere. Show an error directing the user to create a new agent instance (linking to GitHub docs on multi-instance setups).

## 6. Documentation
- [ ] 6.1 Update relevant internal developer docs outlining how `TenantContext` is enforced.
- [ ] 6.2 Add `.en.md` mirror for any Ukrainian user-facing docs added or modified.
- [ ] 6.3 Update `docs/agent-requirements/` if testing or configuration contracts changed.
