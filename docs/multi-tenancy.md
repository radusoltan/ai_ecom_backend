# Multi-tenancy

This application scopes data per tenant using a Doctrine SQL filter and a PostgreSQL session variable.

## Per-request flow

1. `TenantRequestSubscriber` resolves the tenant (JWT claim, domain, subdomain, `X-API-Key`).
2. The tenant id is stored in `TenantContext` and exposed in API responses under `meta.tenant_id`.
3. Doctrine filter `tenant` is enabled and bound with the tenant id.
4. The PostgreSQL connection executes `SET app.tenant_id = :tenant` to align with RLS policies.

## Disabling the filter

For console commands or maintenance tasks run with:

```bash
php bin/console --tenant=<uuid>
```

or disable the filter explicitly:

```php
$em->getFilters()->disable('tenant');
```

Messenger workers must stamp messages with the tenant id and apply the filter before handling.
