<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250829094413 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create tenants table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE tenants (id UUID NOT NULL, PRIMARY KEY(id))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE tenants');
    }
}
