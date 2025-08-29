<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250101000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add composite indexes for cursor pagination';
    }

    public function up(Schema $schema): void
    {
        // Example index for products table
        // $this->addSql('CREATE INDEX idx_products_tenant_createdat_id ON products (tenant_id, created_at DESC, id DESC)');
    }

    public function down(Schema $schema): void
    {
        // $this->addSql('DROP INDEX idx_products_tenant_createdat_id');
    }
}
