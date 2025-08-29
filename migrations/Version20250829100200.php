<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Additional multi-tenant tables with RLS policies using app.tenant_id.
 */
final class Version20250829100200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Customers, addresses, order_items, product_images, product_reviews, categories and wishlists with tenant RLS';
    }

    public function up(Schema $schema): void
    {
        // Customers
        $this->addSql(<<<'SQL'
CREATE TABLE customers (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL,
    email VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_customers_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE,
    UNIQUE(tenant_id, email)
)
SQL);
        $this->addSql('CREATE INDEX idx_customers_tenant ON customers (tenant_id)');
        $this->addSql('ALTER TABLE customers ENABLE ROW LEVEL SECURITY');
        $this->addSql("CREATE POLICY customers_select ON customers FOR SELECT USING (tenant_id = current_setting('app.tenant_id')::uuid)");
        $this->addSql("CREATE POLICY customers_insert ON customers FOR INSERT WITH CHECK (tenant_id = current_setting('app.tenant_id')::uuid)");
        $this->addSql("CREATE POLICY customers_update ON customers FOR UPDATE USING (tenant_id = current_setting('app.tenant_id')::uuid) WITH CHECK (tenant_id = current_setting('app.tenant_id')::uuid)");
        $this->addSql("CREATE POLICY customers_delete ON customers FOR DELETE USING (tenant_id = current_setting('app.tenant_id')::uuid)");

        // Addresses
        $this->addSql(<<<'SQL'
CREATE TABLE addresses (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL,
    customer_id UUID NOT NULL,
    line1 VARCHAR(255) NOT NULL,
    city VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_addresses_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE,
    CONSTRAINT fk_addresses_customer FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE CASCADE
)
SQL);
        $this->addSql('CREATE INDEX idx_addresses_tenant ON addresses (tenant_id)');
        $this->addSql('ALTER TABLE addresses ENABLE ROW LEVEL SECURITY');
        $this->addSql("CREATE POLICY addresses_select ON addresses FOR SELECT USING (tenant_id = current_setting('app.tenant_id')::uuid)");
        $this->addSql("CREATE POLICY addresses_insert ON addresses FOR INSERT WITH CHECK (tenant_id = current_setting('app.tenant_id')::uuid)");
        $this->addSql("CREATE POLICY addresses_update ON addresses FOR UPDATE USING (tenant_id = current_setting('app.tenant_id')::uuid) WITH CHECK (tenant_id = current_setting('app.tenant_id')::uuid)");
        $this->addSql("CREATE POLICY addresses_delete ON addresses FOR DELETE USING (tenant_id = current_setting('app.tenant_id')::uuid)");

        // Order items
        $this->addSql(<<<'SQL'
CREATE TABLE order_items (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL,
    order_id UUID NOT NULL,
    product_id UUID NOT NULL,
    quantity INT NOT NULL,
    price NUMERIC(12,2) NOT NULL,
    CONSTRAINT fk_order_items_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE,
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
)
SQL);
        $this->addSql('CREATE INDEX idx_order_items_tenant ON order_items (tenant_id)');
        $this->addSql('ALTER TABLE order_items ENABLE ROW LEVEL SECURITY');
        $this->addSql("CREATE POLICY order_items_select ON order_items FOR SELECT USING (tenant_id = current_setting('app.tenant_id')::uuid)");
        $this->addSql("CREATE POLICY order_items_insert ON order_items FOR INSERT WITH CHECK (tenant_id = current_setting('app.tenant_id')::uuid)");
        $this->addSql("CREATE POLICY order_items_update ON order_items FOR UPDATE USING (tenant_id = current_setting('app.tenant_id')::uuid) WITH CHECK (tenant_id = current_setting('app.tenant_id')::uuid)");
        $this->addSql("CREATE POLICY order_items_delete ON order_items FOR DELETE USING (tenant_id = current_setting('app.tenant_id')::uuid)");

        // Product images
        $this->addSql(<<<'SQL'
CREATE TABLE product_images (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL,
    product_id UUID NOT NULL,
    url TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_product_images_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE,
    CONSTRAINT fk_product_images_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
)
SQL);
        $this->addSql('CREATE INDEX idx_product_images_tenant ON product_images (tenant_id)');
        $this->addSql('ALTER TABLE product_images ENABLE ROW LEVEL SECURITY');
        $this->addSql("CREATE POLICY product_images_select ON product_images FOR SELECT USING (tenant_id = current_setting('app.tenant_id')::uuid)");
        $this->addSql("CREATE POLICY product_images_insert ON product_images FOR INSERT WITH CHECK (tenant_id = current_setting('app.tenant_id')::uuid)");
        $this->addSql("CREATE POLICY product_images_update ON product_images FOR UPDATE USING (tenant_id = current_setting('app.tenant_id')::uuid) WITH CHECK (tenant_id = current_setting('app.tenant_id')::uuid)");
        $this->addSql("CREATE POLICY product_images_delete ON product_images FOR DELETE USING (tenant_id = current_setting('app.tenant_id')::uuid)");

        // Product reviews
        $this->addSql(<<<'SQL'
CREATE TABLE product_reviews (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL,
    product_id UUID NOT NULL,
    customer_id UUID NOT NULL,
    rating INT NOT NULL,
    comment TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_product_reviews_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE,
    CONSTRAINT fk_product_reviews_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE,
    CONSTRAINT fk_product_reviews_customer FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE CASCADE
)
SQL);
        $this->addSql('CREATE INDEX idx_product_reviews_tenant ON product_reviews (tenant_id)');
        $this->addSql('ALTER TABLE product_reviews ENABLE ROW LEVEL SECURITY');
        $this->addSql("CREATE POLICY product_reviews_select ON product_reviews FOR SELECT USING (tenant_id = current_setting('app.tenant_id')::uuid)");
        $this->addSql("CREATE POLICY product_reviews_insert ON product_reviews FOR INSERT WITH CHECK (tenant_id = current_setting('app.tenant_id')::uuid)");
        $this->addSql("CREATE POLICY product_reviews_update ON product_reviews FOR UPDATE USING (tenant_id = current_setting('app.tenant_id')::uuid) WITH CHECK (tenant_id = current_setting('app.tenant_id')::uuid)");
        $this->addSql("CREATE POLICY product_reviews_delete ON product_reviews FOR DELETE USING (tenant_id = current_setting('app.tenant_id')::uuid)");

        // Categories
        $this->addSql(<<<'SQL'
CREATE TABLE categories (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    CONSTRAINT fk_categories_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE,
    UNIQUE(tenant_id, slug)
)
SQL);
        $this->addSql('CREATE INDEX idx_categories_tenant ON categories (tenant_id)');
        $this->addSql('ALTER TABLE categories ENABLE ROW LEVEL SECURITY');
        $this->addSql("CREATE POLICY categories_select ON categories FOR SELECT USING (tenant_id = current_setting('app.tenant_id')::uuid)");
        $this->addSql("CREATE POLICY categories_insert ON categories FOR INSERT WITH CHECK (tenant_id = current_setting('app.tenant_id')::uuid)");
        $this->addSql("CREATE POLICY categories_update ON categories FOR UPDATE USING (tenant_id = current_setting('app.tenant_id')::uuid) WITH CHECK (tenant_id = current_setting('app.tenant_id')::uuid)");
        $this->addSql("CREATE POLICY categories_delete ON categories FOR DELETE USING (tenant_id = current_setting('app.tenant_id')::uuid)");

        // Wishlists
        $this->addSql(<<<'SQL'
CREATE TABLE wishlists (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL,
    customer_id UUID NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_wishlists_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE,
    CONSTRAINT fk_wishlists_customer FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE CASCADE
)
SQL);
        $this->addSql('CREATE INDEX idx_wishlists_tenant ON wishlists (tenant_id)');
        $this->addSql('ALTER TABLE wishlists ENABLE ROW LEVEL SECURITY');
        $this->addSql("CREATE POLICY wishlists_select ON wishlists FOR SELECT USING (tenant_id = current_setting('app.tenant_id')::uuid)");
        $this->addSql("CREATE POLICY wishlists_insert ON wishlists FOR INSERT WITH CHECK (tenant_id = current_setting('app.tenant_id')::uuid)");
        $this->addSql("CREATE POLICY wishlists_update ON wishlists FOR UPDATE USING (tenant_id = current_setting('app.tenant_id')::uuid) WITH CHECK (tenant_id = current_setting('app.tenant_id')::uuid)");
        $this->addSql("CREATE POLICY wishlists_delete ON wishlists FOR DELETE USING (tenant_id = current_setting('app.tenant_id')::uuid)");
    }

    public function down(Schema $schema): void
    {
        $tables = ['wishlists','categories','product_reviews','product_images','order_items','addresses','customers'];
        foreach ($tables as $table) {
            $this->addSql("DROP POLICY IF EXISTS {$table}_select ON {$table}");
            $this->addSql("DROP POLICY IF EXISTS {$table}_insert ON {$table}");
            $this->addSql("DROP POLICY IF EXISTS {$table}_update ON {$table}");
            $this->addSql("DROP POLICY IF EXISTS {$table}_delete ON {$table}");
            $this->addSql("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
            $this->addSql("DROP TABLE {$table}");
        }
    }
}
