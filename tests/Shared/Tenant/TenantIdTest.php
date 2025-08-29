<?php

declare(strict_types=1);

namespace App\Tests\Shared\Tenant;

use App\Shared\Tenant\TenantId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class TenantIdTest extends TestCase
{
    public function testAcceptsValidUuid(): void
    {
        $uuid = Uuid::v4()->toRfc4122();
        $id = new TenantId($uuid);
        self::assertSame($uuid, $id->toString());
    }

    public function testRejectsInvalidUuid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new TenantId('invalid');
    }
}
