<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250829100300_create_event_store extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create event_store table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE event_store (id UUID PRIMARY KEY, tenant_id UUID NOT NULL, aggregate_type VARCHAR(100) NOT NULL, aggregate_id UUID NOT NULL, version INT NOT NULL, event_name VARCHAR(100) NOT NULL, payload JSONB NOT NULL, metadata JSONB NOT NULL, occurred_at TIMESTAMPTZ NOT NULL, recorded_at TIMESTAMPTZ NOT NULL DEFAULT NOW())');
        $this->addSql('CREATE UNIQUE INDEX ux_event_store_aggregate_version ON event_store(aggregate_id, version)');
        $this->addSql('CREATE INDEX ix_event_store_tenant ON event_store(tenant_id)');
        $this->addSql('CREATE INDEX ix_event_store_aggregate ON event_store(aggregate_type, aggregate_id)');
        $this->addSql('CREATE INDEX ix_event_store_event_name ON event_store(event_name)');
        $this->addSql('CREATE INDEX ix_event_store_occurred_at ON event_store(occurred_at)');
        $this->addSql('ALTER TABLE event_store ENABLE ROW LEVEL SECURITY');
        $this->addSql("CREATE POLICY tenant_isolation_all ON event_store FOR ALL USING (tenant_id = current_setting('app.tenant_id')::uuid) WITH CHECK (tenant_id = current_setting('app.tenant_id')::uuid)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE event_store');
    }
}

