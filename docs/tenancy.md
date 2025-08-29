# Multi-Tenant Context

This application isolates data by tenant. Each HTTP request resolves a tenant identifier using the following order:

1. JWT `tenant_id` claim
2. Custom domain mapping
3. Subdomain mapping
4. `X-API-Key` header

If none match, the request is rejected with `TenantNotFound` (400).

The resolved tenant id is exposed through the `TenantContext` service and request attributes. Database isolation is enforced by:

* Setting `app.tenant_id` on the PostgreSQL session.
* Enabling the Doctrine `TenantFilter` with the current tenant id.

API responses include `meta.tenant_id` in the unified response envelope.
