# RLS Bootstrap

This application uses PostgreSQL Row Level Security (RLS) to isolate tenant data.
For every HTTP request and Messenger worker we set the session variable
`app.tenant_id` and enable a Doctrine `TenantFilter` that guards against accidental
cross-tenant access.

## Adding a new tenant-scoped table

1. Ensure the table has a `tenant_id UUID NOT NULL` column referencing `tenants(id)`.
2. Enable RLS on the table and create four policies:
   - `SELECT` policy using `tenant_id = current_setting('app.tenant_id')::uuid`
   - `INSERT` policy with a matching `WITH CHECK`
   - `UPDATE` policy with both `USING` and `WITH CHECK`
   - `DELETE` policy using the same condition
3. Create helpful indexes, e.g. `CREATE INDEX ON <table>(tenant_id);`
4. Run `php bin/console doctrine:migrations:diff` and commit the migration.

## Common errors

- **"tenant_not_found"** – the `TenantContextResolver` could not resolve a tenant
  for the current request. Ensure a JWT, custom domain, subdomain or `X-API-Key`
  is present.
- **"ERROR:  new row violates row-level security policy"** – an INSERT/UPDATE was
  attempted with a different tenant id than the session variable.
- **No data returned** – the `app.tenant_id` variable was not set; check that the
  `SetTenantRlsSessionListener` or worker subscriber ran.

## Local testing

```bash
php bin/console doctrine:migrations:migrate
```

After seeding tenants and rows you can verify the current tenant with:

```sql
SELECT current_setting('app.tenant_id', true);
```
