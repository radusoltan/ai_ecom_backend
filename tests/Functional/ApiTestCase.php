<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class ApiTestCase extends WebTestCase
{
    protected function createAuthenticatedClient(string $username = 'user@example.com', string $password = 'password')
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/api/login_check', [
            'email' => $username,
            'password' => $password,
        ]);

        $data = json_decode($client->getResponse()->getContent(), true);
        $client->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', $data['token']));

        return $client;
    }

    protected function createClientWithManualToken(array $claims): object
    {
        $client = static::createClient();
        /** @var JWTEncoderInterface $encoder */
        $encoder = $client->getContainer()->get(JWTEncoderInterface::class);
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $encoder->encode($claims));

        return $client;
    }
}

