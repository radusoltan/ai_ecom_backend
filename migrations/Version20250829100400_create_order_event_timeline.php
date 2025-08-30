<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250829100400_create_order_event_timeline extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create order_event_timeline table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE order_event_timeline (id UUID PRIMARY KEY, order_id UUID NOT NULL, event_name VARCHAR(100) NOT NULL, payload JSONB NOT NULL, occurred_at TIMESTAMPTZ NOT NULL)');
        $this->addSql('CREATE INDEX ix_order_event_timeline_order ON order_event_timeline(order_id)');
        $this->addSql('CREATE INDEX ix_order_event_timeline_occurred_at ON order_event_timeline(occurred_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE order_event_timeline');
    }
}

