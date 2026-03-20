# Change: Add Multitenancy and User Management

## Why
The platform currently assumes a single-tenant MVP. To support multiple communities independently and securely, we need a multitenancy model. A Tenant will be tied to a User rather than a domain. We also need to manage Agent instance sharing between tenants and introduce proper RBAC to allow administrators to manage multiple tenants cleanly.

## What Changes
- Add User and Tenant relationships.
- Introduce Role-Based Access Control (RBAC/ACL) via Symfony Security (Voters & Role Hierarchy).
- Add Tenant Management (CRUD), with constraints preventing deletion if active agents or crons exist.
- Add a tenant switcher to the Admin panel navigation.
- Update Agent Registry: tie agent installations to a Tenant.
- Introduce the concept of a "Shared Agent" vs "Dedicated Agent".
- Prevent installing a non-shared agent in multiple tenants (prompting user to create a new instance).

## Impact
- Affected specs: `tenant-management` (new)
- Affected code: `core` user models, admin panel routing, agent installation logic, security configuration.
