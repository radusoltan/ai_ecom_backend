<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Persistence\Doctrine\Filter;

use App\Infrastructure\Persistence\Doctrine\Filter\TenantFilter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use PHPUnit\Framework\TestCase;

class TenantFilterTest extends TestCase
{
    public function testAddsConstraintWhenTenantFieldPresent(): void
    {
        $conn = $this->createMock(\Doctrine\DBAL\Connection::class);
        $conn->method('quote')->willReturnCallback(fn ($v) => "'$v'");
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($conn);
        $filter = new TenantFilter($em);
        $filter->setParameter('tenant_id', 'abc');
        $meta = new ClassMetadata('Foo');
        $meta->mapField(['fieldName' => 'tenantId', 'columnName' => 'tenant_id']);

        $sql = $filter->addFilterConstraint($meta, 't');
        self::assertSame("t.tenant_id = 'abc'", $sql);
    }

    public function testReturnsEmptyStringForGlobalEntities(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $filter = new TenantFilter($em);
        $meta = new ClassMetadata('Bar');

        $sql = $filter->addFilterConstraint($meta, 't');
        self::assertSame('', $sql);
    }
}
