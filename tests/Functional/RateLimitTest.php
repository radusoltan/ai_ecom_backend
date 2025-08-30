<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Component\Uid\Uuid;

final class RateLimitTest extends ApiTestCase
{
    public function test_ip_rate_limit(): void
    {
        static::ensureKernelShutdown();
        putenv('API_IP_LIMIT=3');
        $client = $this->createClientWithManualToken([
            'username' => 'user@example.com',
            'tenant_id' => Uuid::v4()->toRfc4122(),
        ]);

        for ($i = 0; $i < 3; ++$i) {
            $client->request('GET', '/api/ping');
            $this->assertResponseIsSuccessful();
        }

        $client->request('GET', '/api/ping');
        $this->assertResponseStatusCodeSame(429);
        $this->assertTrue($client->getResponse()->headers->has('Retry-After'));
    }

    public function test_api_key_rate_limit(): void
    {
        static::ensureKernelShutdown();
        putenv('API_IP_LIMIT=1000');
        putenv('API_KEY_LIMIT=5');
        putenv('API_KEY_RATE_AMOUNT=5');

        $client = $this->createClientWithManualToken([
            'username' => 'user@example.com',
            'tenant_id' => Uuid::v4()->toRfc4122(),
        ]);
        $client->setServerParameter('HTTP_X_API_KEY', 'test-key');

        for ($i = 0; $i < 5; ++$i) {
            $client->request('GET', '/api/ping');
            $this->assertResponseIsSuccessful();
        }

        $client->request('GET', '/api/ping');
        $this->assertResponseStatusCodeSame(429);
        $this->assertTrue($client->getResponse()->headers->has('Retry-After'));
    }
}
