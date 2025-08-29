<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Initial platform schema with multi-tenant RLS policies.
 *
 * Helper SQL for manual bootstrap:
 *   -- Set default tenant for sessions without explicit tenant
 *   -- ALTER DATABASE <db_name> SET app.tenant_id TO '00000000-0000-0000-0000-000000000000';
 *   -- Seed tenant for testing
 *   -- INSERT INTO tenants(id, slug, name, status, tier, config)
 *   -- VALUES ('00000000-0000-0000-0000-000000000000', 'demo', 'Demo', 'active', 'free', '{}');
 */
final class Version20250829100100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Baseline tenants, products, orders, stock_items tables with RLS';
    }

    public function up(Schema $schema): void
    {
        // Enable pgcrypto extension for gen_random_uuid
        $this->addSql('CREATE EXTENSION IF NOT EXISTS pgcrypto');

        // Tenants table
        $this->addSql(<<<'SQL'
CREATE TABLE tenants (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    slug VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    status VARCHAR(50) NOT NULL,
    tier VARCHAR(50) NOT NULL,
    config JSONB NOT NULL DEFAULT '{}',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(slug)
)
SQL);
        $this->addSql('ALTER TABLE tenants ENABLE ROW LEVEL SECURITY');
        $this->addSql(<<<'SQL'
CREATE POLICY tenants_select ON tenants FOR SELECT USING (id = current_setting('app.tenant_id')::uuid)
SQL);
        $this->addSql(<<<'SQL'
CREATE POLICY tenants_insert ON tenants FOR INSERT WITH CHECK (id = current_setting('app.tenant_id')::uuid)
SQL);
        $this->addSql(<<<'SQL'
CREATE POLICY tenants_update ON tenants FOR UPDATE USING (id = current_setting('app.tenant_id')::uuid) WITH CHECK (id = current_setting('app.tenant_id')::uuid)
SQL);
        $this->addSql(<<<'SQL'
CREATE POLICY tenants_delete ON tenants FOR DELETE USING (id = current_setting('app.tenant_id')::uuid)
SQL);
        $this->addSql("COMMENT ON POLICY tenants_select ON tenants IS 'TODO: allow platform admin bypass';");
        $this->addSql("COMMENT ON POLICY tenants_insert ON tenants IS 'TODO: allow platform admin bypass';");
        $this->addSql("COMMENT ON POLICY tenants_update ON tenants IS 'TODO: allow platform admin bypass';");
        $this->addSql("COMMENT ON POLICY tenants_delete ON tenants IS 'TODO: allow platform admin bypass';");

        // Products table
        $this->addSql(<<<'SQL'
CREATE TABLE products (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL,
    sku VARCHAR(64) NOT NULL,
    type VARCHAR(50) NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    status VARCHAR(50) NOT NULL,
    metadata JSONB NOT NULL DEFAULT '{}',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_products_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE,
    UNIQUE(tenant_id, sku)
)
SQL);
        $this->addSql('CREATE INDEX idx_products_tenant ON products (tenant_id)');
        $this->addSql('CREATE INDEX idx_products_tenant_status ON products (tenant_id, status)');
        $this->addSql('CREATE INDEX idx_products_tenant_created_at ON products (tenant_id, created_at DESC)');
        $this->addSql('ALTER TABLE products ENABLE ROW LEVEL SECURITY');
        $this->addSql(<<<'SQL'
CREATE POLICY products_select ON products FOR SELECT USING (tenant_id = current_setting('app.tenant_id')::uuid)
SQL);
        $this->addSql(<<<'SQL'
CREATE POLICY products_insert ON products FOR INSERT WITH CHECK (tenant_id = current_setting('app.tenant_id')::uuid)
SQL);
        $this->addSql(<<<'SQL'
CREATE POLICY products_update ON products FOR UPDATE USING (tenant_id = current_setting('app.tenant_id')::uuid) WITH CHECK (tenant_id = current_setting('app.tenant_id')::uuid)
SQL);
        $this->addSql(<<<'SQL'
CREATE POLICY products_delete ON products FOR DELETE USING (tenant_id = current_setting('app.tenant_id')::uuid)
SQL);

        // Orders table
        $this->addSql(<<<'SQL'
CREATE TABLE orders (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL,
    order_number VARCHAR(64) NOT NULL,
    customer_id UUID NOT NULL,
    status VARCHAR(50) NOT NULL,
    subtotal NUMERIC(12,2) NOT NULL,
    tax NUMERIC(12,2) NOT NULL,
    shipping NUMERIC(12,2) NOT NULL,
    total NUMERIC(12,2) NOT NULL,
    currency CHAR(3) NOT NULL,
    metadata JSONB NOT NULL DEFAULT '{}',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE,
    UNIQUE(tenant_id, order_number)
)
SQL);
        $this->addSql('CREATE INDEX idx_orders_tenant_customer ON orders (tenant_id, customer_id)');
        $this->addSql('CREATE INDEX idx_orders_tenant_status ON orders (tenant_id, status)');
        $this->addSql('CREATE INDEX idx_orders_tenant_created_at ON orders (tenant_id, created_at DESC)');
        $this->addSql('ALTER TABLE orders ENABLE ROW LEVEL SECURITY');
        $this->addSql(<<<'SQL'
CREATE POLICY orders_select ON orders FOR SELECT USING (tenant_id = current_setting('app.tenant_id')::uuid)
SQL);
        $this->addSql(<<<'SQL'
CREATE POLICY orders_insert ON orders FOR INSERT WITH CHECK (tenant_id = current_setting('app.tenant_id')::uuid)
SQL);
        $this->addSql(<<<'SQL'
CREATE POLICY orders_update ON orders FOR UPDATE USING (tenant_id = current_setting('app.tenant_id')::uuid) WITH CHECK (tenant_id = current_setting('app.tenant_id')::uuid)
SQL);
        $this->addSql(<<<'SQL'
CREATE POLICY orders_delete ON orders FOR DELETE USING (tenant_id = current_setting('app.tenant_id')::uuid)
SQL);

        // Stock items table
        $this->addSql(<<<'SQL'
CREATE TABLE stock_items (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL,
    product_id UUID NOT NULL,
    warehouse_id UUID NOT NULL,
    available INT NOT NULL DEFAULT 0,
    reserved INT NOT NULL DEFAULT 0,
    in_transit INT NOT NULL DEFAULT 0,
    last_counted TIMESTAMP DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_stock_items_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE,
    CONSTRAINT fk_stock_items_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE,
    UNIQUE(product_id, warehouse_id)
)
SQL);
        $this->addSql('CREATE INDEX idx_stock_items_tenant ON stock_items (tenant_id)');
        $this->addSql('CREATE INDEX idx_stock_items_tenant_created_at ON stock_items (tenant_id, created_at DESC)');
        $this->addSql('CREATE INDEX idx_stock_items_product ON stock_items (product_id)');
        $this->addSql('ALTER TABLE stock_items ENABLE ROW LEVEL SECURITY');
        $this->addSql(<<<'SQL'
CREATE POLICY stock_items_select ON stock_items FOR SELECT USING (tenant_id = current_setting('app.tenant_id')::uuid)
SQL);
        $this->addSql(<<<'SQL'
CREATE POLICY stock_items_insert ON stock_items FOR INSERT WITH CHECK (tenant_id = current_setting('app.tenant_id')::uuid)
SQL);
        $this->addSql(<<<'SQL'
CREATE POLICY stock_items_update ON stock_items FOR UPDATE USING (tenant_id = current_setting('app.tenant_id')::uuid) WITH CHECK (tenant_id = current_setting('app.tenant_id')::uuid)
SQL);
        $this->addSql(<<<'SQL'
CREATE POLICY stock_items_delete ON stock_items FOR DELETE USING (tenant_id = current_setting('app.tenant_id')::uuid)
SQL);
    }

    public function down(Schema $schema): void
    {
        // Drop policies and tables in reverse order
        $this->addSql('DROP POLICY IF EXISTS stock_items_select ON stock_items');
        $this->addSql('DROP POLICY IF EXISTS stock_items_insert ON stock_items');
        $this->addSql('DROP POLICY IF EXISTS stock_items_update ON stock_items');
        $this->addSql('DROP POLICY IF EXISTS stock_items_delete ON stock_items');
        $this->addSql('ALTER TABLE stock_items DISABLE ROW LEVEL SECURITY');
        $this->addSql('DROP TABLE stock_items');

        $this->addSql('DROP POLICY IF EXISTS orders_select ON orders');
        $this->addSql('DROP POLICY IF EXISTS orders_insert ON orders');
        $this->addSql('DROP POLICY IF EXISTS orders_update ON orders');
        $this->addSql('DROP POLICY IF EXISTS orders_delete ON orders');
        $this->addSql('ALTER TABLE orders DISABLE ROW LEVEL SECURITY');
        $this->addSql('DROP TABLE orders');

        $this->addSql('DROP POLICY IF EXISTS products_select ON products');
        $this->addSql('DROP POLICY IF EXISTS products_insert ON products');
        $this->addSql('DROP POLICY IF EXISTS products_update ON products');
        $this->addSql('DROP POLICY IF EXISTS products_delete ON products');
        $this->addSql('ALTER TABLE products DISABLE ROW LEVEL SECURITY');
        $this->addSql('DROP TABLE products');

        $this->addSql('DROP POLICY IF EXISTS tenants_select ON tenants');
        $this->addSql('DROP POLICY IF EXISTS tenants_insert ON tenants');
        $this->addSql('DROP POLICY IF EXISTS tenants_update ON tenants');
        $this->addSql('DROP POLICY IF EXISTS tenants_delete ON tenants');
        $this->addSql('ALTER TABLE tenants DISABLE ROW LEVEL SECURITY');
        $this->addSql('DROP TABLE tenants');

        $this->addSql('DROP EXTENSION IF EXISTS pgcrypto');
    }
}
