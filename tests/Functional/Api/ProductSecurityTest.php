<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Shared\Feature\FeatureFlagService;
use App\Tests\Functional\ApiTestCase;
use Symfony\Component\Uid\Uuid;

final class ProductSecurityTest extends ApiTestCase
{
    public function testPostDeniedWhenFeatureDisabled(): void
    {
        $tenantId = Uuid::v4();
        $client = $this->createClientWithManualToken([
            'id' => 'manager',
            'username' => 'manager',
            'roles' => ['ROLE_MANAGER'],
            'tenant_id' => $tenantId->toRfc4122(),
        ]);
        $dispatcher = $client->getContainer()->get('event_dispatcher');
        $listener = $client->getContainer()->get(\App\Infrastructure\Persistence\Doctrine\SetTenantRlsSessionListener::class);
        $dispatcher->removeListener('kernel.request', [$listener, '__invoke']);

        $client->getContainer()->get(\App\Shared\Tenant\TenantContext::class)
            ->set(new \App\Shared\Tenant\TenantId($tenantId->toRfc4122()));

        $client->request('POST', '/api/products', ['json' => [
            'id' => Uuid::v4()->toRfc4122(),
            'slug' => 's',
            'name' => 'n',
            'priceCents' => 100,
            'currency' => 'USD',
            'tenantId' => $tenantId->toRfc4122(),
        ]]);
        self::assertResponseStatusCodeSame(403);
    }

    public function testPostAllowedWhenFeatureEnabled(): void
    {
        $tenantId = Uuid::v4();
        $client = $this->createClientWithManualToken([
            'id' => 'manager',
            'username' => 'manager',
            'roles' => ['ROLE_MANAGER'],
            'tenant_id' => $tenantId->toRfc4122(),
        ]);
        $dispatcher = $client->getContainer()->get('event_dispatcher');
        $listener = $client->getContainer()->get(\App\Infrastructure\Persistence\Doctrine\SetTenantRlsSessionListener::class);
        $dispatcher->removeListener('kernel.request', [$listener, '__invoke']);

        $client->getContainer()->get(\App\Shared\Tenant\TenantContext::class)
            ->set(new \App\Shared\Tenant\TenantId($tenantId->toRfc4122()));

        self::getContainer()->get(FeatureFlagService::class)->set($tenantId, 'catalog_write', true);

        $client->request('POST', '/api/products', ['json' => [
            'id' => Uuid::v4()->toRfc4122(),
            'slug' => 's',
            'name' => 'n',
            'priceCents' => 100,
            'currency' => 'USD',
            'tenantId' => $tenantId->toRfc4122(),
        ]]);
        self::assertResponseStatusCodeSame(201);
    }
}

