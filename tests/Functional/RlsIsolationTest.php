<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;

final class RlsIsolationTest extends KernelTestCase
{
    private Connection $conn;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->conn = self::getContainer()->get(Connection::class);
        try {
            // clear data
            $this->conn->executeStatement('TRUNCATE tenants, products RESTART IDENTITY CASCADE');
        } catch (\Throwable $e) {
            $this->markTestSkipped('Database not available: ' . $e->getMessage());
        }
    }

    public function testCrossTenantSelectIsBlocked(): void
    {
        $tenantA = Uuid::v4()->toRfc4122();
        $tenantB = Uuid::v4()->toRfc4122();
        $this->seedTenant($tenantA);
        $this->seedTenant($tenantB);
        $this->conn->executeStatement('INSERT INTO products(id, tenant_id, sku, type, name, status, metadata) VALUES (gen_random_uuid(), :t, :sku, :type, :name, :status, :meta)', [
            't' => $tenantB,
            'sku' => 'sku-b',
            'type' => 'simple',
            'name' => 'B',
            'status' => 'active',
            'meta' => '{}',
        ]);

        $this->conn->executeStatement('SET app.tenant_id = :tenant', ['tenant' => $tenantA]);
        $count = (int) $this->conn->fetchOne('SELECT COUNT(*) FROM products');
        self::assertSame(0, $count);
    }

    public function testInsertWithMismatchedTenantFails(): void
    {
        $tenantA = Uuid::v4()->toRfc4122();
        $tenantB = Uuid::v4()->toRfc4122();
        $this->seedTenant($tenantA);
        $this->seedTenant($tenantB);
        $this->conn->executeStatement('SET app.tenant_id = :tenant', ['tenant' => $tenantA]);

        $this->expectException(\Throwable::class);
        $this->conn->executeStatement('INSERT INTO products(id, tenant_id, sku, type, name, status, metadata) VALUES (gen_random_uuid(), :t, :sku, :type, :name, :status, :meta)', [
            't' => $tenantB,
            'sku' => 'sku-b',
            'type' => 'simple',
            'name' => 'B',
            'status' => 'active',
            'meta' => '{}',
        ]);
    }

    private function seedTenant(string $id): void
    {
        $this->conn->executeStatement('INSERT INTO tenants(id, slug, name, status, tier, config) VALUES (:id, :slug, :name, :status, :tier, :config)', [
            'id' => $id,
            'slug' => substr($id, 0, 8),
            'name' => 'T',
            'status' => 'active',
            'tier' => 'free',
            'config' => '{}',
        ]);
    }
}
