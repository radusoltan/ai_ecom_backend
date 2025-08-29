<?php

declare(strict_types=1);

namespace App\Tests\Functional;

final class AuthFlowTest extends ApiTestCase
{
    public function test_can_login_and_access_protected_endpoint(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/api/login_check', [
            'email' => 'user@example.com',
            'password' => 'password',
        ]);
        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $data);

        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $data['token']);
        $client->jsonRequest('GET', '/api/ping');
        $this->assertResponseIsSuccessful();
        $this->assertJsonStringEqualsJsonString('{"ok":true}', $client->getResponse()->getContent());
    }
}

