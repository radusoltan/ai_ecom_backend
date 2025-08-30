<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Shared\Logging\RequestContextProcessor;
use App\Shared\Tenant\TenantContext;
use App\Shared\Tenant\TenantId;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;

final class RequestContextProcessorTest extends TestCase
{
    public function testAddsContext(): void
    {
        $request = new Request([], [], [], [], [], ['REMOTE_ADDR' => '1.2.3.4']);
        $request->attributes->set('request_id', 'req-1');
        $request->attributes->set('tenant_id', 'tenant-1');
        $request->server->set('REQUEST_URI', '/api/test');
        $request->setMethod('POST');

        $stack = new RequestStack();
        $stack->push($request);

        $tenantContext = new TenantContext(new TenantId('11111111-1111-4111-8111-111111111111'));

        $user = new class implements UserInterface {
            public function getUserIdentifier(): string
            {
                return 'user-1';
            }

            public function getRoles(): array
            {
                return [];
            }

            public function eraseCredentials(): void
            {
            }

            public function getId(): string
            {
                return 'user-1';
            }
        };

        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken($token);

        $processor = new RequestContextProcessor($stack, $tokenStorage, $tenantContext);
        $record = new LogRecord(new \DateTimeImmutable(), 'app', Level::Info, 'msg');
        $processed = $processor($record);

        self::assertSame('req-1', $processed->extra['request_id']);
        self::assertSame('tenant-1', $processed->extra['tenant_id']);
        self::assertSame('user-1', $processed->extra['user_id']);
        self::assertSame('POST', $processed->extra['http']['method']);
        self::assertSame('/api/test', $processed->extra['http']['path']);
        self::assertSame('1.2.3.4', $processed->extra['http']['ip']);
    }
}
