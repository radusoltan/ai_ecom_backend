<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CorsTest extends WebTestCase
{
    public function test_preflight_allows_known_origin(): void
    {
        static::ensureKernelShutdown();
        putenv('FRONTEND_ORIGINS=http://allowed.example');
        $client = static::createClient([], ['REMOTE_ADDR' => '10.0.0.1']);

        $client->request('OPTIONS', '/api/ping', [], [], [
            'HTTP_ORIGIN' => 'http://allowed.example',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'X-API-Key',
        ]);

        $response = $client->getResponse();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Origin', $response->headers->get('Vary'));
    }

    public function test_disallowed_origin_has_no_cors_headers(): void
    {
        static::ensureKernelShutdown();
        putenv('FRONTEND_ORIGINS=http://allowed.example');
        $client = static::createClient([], ['REMOTE_ADDR' => '10.0.0.2']);

        $client->request('OPTIONS', '/api/ping', [], [], [
            'HTTP_ORIGIN' => 'http://evil.example',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'X-API-Key',
        ]);

        $response = $client->getResponse();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertNull($response->headers->get('Access-Control-Allow-Origin'));
    }
}
