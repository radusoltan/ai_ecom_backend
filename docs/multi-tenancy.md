# Multi-tenancy

This application scopes data per tenant using a Doctrine SQL filter and a PostgreSQL session variable.

## Per-request flow

1. `TenantContextResolver` resolves the tenant (JWT claim, domain, subdomain, `X-API-Key`).
2. `SetTenantRlsSessionListener` stores the tenant id in `TenantContext`, enables the `tenant_filter` and runs `SET app.tenant_id = :tenant` on the connection.
3. API responses expose the tenant id under `meta.tenant_id`.

## Disabling the filter

For console commands or maintenance tasks run with:

```bash
php bin/console --tenant=<uuid>
```

or disable the filter explicitly:

```php
$em->getFilters()->disable('tenant_filter');
```

Messenger workers must stamp messages with the tenant id and apply the filter before handling.
