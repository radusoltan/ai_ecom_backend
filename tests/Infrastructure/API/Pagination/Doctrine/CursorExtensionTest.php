<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\API\Pagination\Doctrine;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Metadata\GetCollection;
use App\Infrastructure\API\Pagination\Cursor;
use App\Infrastructure\API\Pagination\CursorEncoder;
use App\Infrastructure\API\Pagination\Doctrine\CursorExtension;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use PHPUnit\Framework\TestCase;
use App\Tests\Infrastructure\API\Pagination\Doctrine\Fixtures\Foo;

final class CursorExtensionTest extends TestCase
{
    private EntityManager $em;

    protected function setUp(): void
    {
        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/Fixtures'], true);
        $connection = DriverManager::getConnection(['url' => 'sqlite:///:memory:']);
        $this->em = new EntityManager($connection, $config);
    }

    public function testApplyCursor(): void
    {
        $qb = $this->em->createQueryBuilder()->select('f')->from(Foo::class, 'f');
        $encoder = new CursorEncoder();
        $extension = new CursorExtension($encoder);
        $cursor = new Cursor(new \DateTimeImmutable('2024-01-01T00:00:00Z'), '00000000-0000-0000-0000-000000000001');
        $context = ['filters' => ['cursor' => $encoder->encode($cursor), 'limit' => 2]];
        $extension->applyToCollection($qb, new QueryNameGenerator(), Foo::class, new GetCollection(), $context);

        self::assertStringContainsString('f.createdAt <', $qb->getDQL());
        self::assertSame(3, $qb->getMaxResults());
    }
}
