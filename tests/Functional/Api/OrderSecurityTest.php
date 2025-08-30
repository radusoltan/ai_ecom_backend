<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Infrastructure\API\State\OrderProvider;
use App\Tests\Functional\ApiTestCase;
use Symfony\Component\Uid\Uuid;

final class OrderSecurityTest extends ApiTestCase
{
    public function testManagerCanViewOrder(): void
    {
        $client = $this->createClientWithManualToken([
            'id' => 'manager',
            'username' => 'manager',
            'roles' => ['ROLE_MANAGER'],
            'tenant_id' => OrderProvider::TENANT_ID,
        ]);

        $dispatcher = $client->getContainer()->get('event_dispatcher');
        $listener = $client->getContainer()->get(\App\Infrastructure\Persistence\Doctrine\SetTenantRlsSessionListener::class);
        $dispatcher->removeListener('kernel.request', [$listener, '__invoke']);

        $client->request('GET', '/api/orders/' . Uuid::v4()->toRfc4122());
        self::assertResponseStatusCodeSame(200);
    }

    public function testOtherCustomerDenied(): void
    {
        $client = $this->createClientWithManualToken([
            'id' => 'customer-2',
            'username' => 'customer-2',
            'roles' => ['ROLE_CUSTOMER'],
            'tenant_id' => OrderProvider::TENANT_ID,
        ]);

        $dispatcher = $client->getContainer()->get('event_dispatcher');
        $listener = $client->getContainer()->get(\App\Infrastructure\Persistence\Doctrine\SetTenantRlsSessionListener::class);
        $dispatcher->removeListener('kernel.request', [$listener, '__invoke']);

        $client->request('GET', '/api/orders/' . Uuid::v4()->toRfc4122());
        self::assertResponseStatusCodeSame(403);
    }
}

