# Migrations & RLS Bootstrap

This project uses Doctrine migrations to manage the PostgreSQL schema.

## Running the migrations

```bash
bin/console doctrine:database:create
bin/console doctrine:migrations:migrate -n
```

## Quick Row Level Security check

```sql
-- substitute with your tenant UUID
SET app.current_tenant = '<TENANT_UUID>';
INSERT INTO tenants (id, slug, name, status, tier, config)
VALUES ('<TENANT_UUID>', 'acme', 'Acme Inc.', 'active', 'pro', '{}');

-- switching to another tenant should prevent access
SET app.current_tenant = '<OTHER_TENANT_UUID>';
SELECT * FROM tenants; -- returns 0 rows
INSERT INTO products (tenant_id, sku, type, name, status, metadata)
VALUES ('<TENANT_UUID>', 'SKU1', 'simple', 'Demo', 'active', '{}'); -- fails
```

## Inspecting indexes

```sql
\d+ products
EXPLAIN ANALYZE SELECT * FROM products WHERE tenant_id = '<TENANT_UUID>' ORDER BY created_at DESC LIMIT 50;
```

The database default tenant can be set once with:

```sql
ALTER DATABASE <db_name> SET app.current_tenant TO '00000000-0000-0000-0000-000000000000';
```
