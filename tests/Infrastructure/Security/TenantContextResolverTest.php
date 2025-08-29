<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Security;

use App\Domain\Tenant\Entity\Tenant;
use App\Infrastructure\Security\TenantContextResolver;
use App\Shared\Tenant\ApiKeyRepository;
use App\Shared\Tenant\JwtDecoder;
use App\Shared\Tenant\TenantNotFoundException;
use App\Shared\Tenant\TenantRepository;
use App\Shared\Tenant\TenantContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\Uuid;

class TenantContextResolverTest extends TestCase
{
    public function testResolvesFromJwt(): void
    {
        $tenantRepo = $this->createMock(TenantRepository::class);
        $apiKeyRepo = $this->createMock(ApiKeyRepository::class);
        $jwt = $this->createMock(JwtDecoder::class);
        $jwt->method('decodeFromHeader')->with('Bearer token')->willReturn(['tenant_id' => Uuid::v4()->toRfc4122()]);
        $stack = new RequestStack();
        $stack->push(Request::create('/', server: ['HTTP_AUTHORIZATION' => 'Bearer token']));
        $resolver = new TenantContextResolver($tenantRepo, $apiKeyRepo, $jwt, $stack, new TenantContext());

        $tenantId = $resolver->resolveOrFail();
        self::assertInstanceOf(\App\Shared\Tenant\TenantId::class, $tenantId);
    }

    public function testResolvesFromCustomDomain(): void
    {
        $uuid = Uuid::v4();
        $tenant = $this->createConfiguredMock(Tenant::class, ['getId' => $uuid]);
        $tenantRepo = $this->createMock(TenantRepository::class);
        $tenantRepo->method('findByCustomDomain')->with('foo.com')->willReturn($tenant);
        $apiKeyRepo = $this->createMock(ApiKeyRepository::class);
        $jwt = $this->createMock(JwtDecoder::class);
        $stack = new RequestStack();
        $stack->push(Request::create('https://foo.com'));
        $resolver = new TenantContextResolver($tenantRepo, $apiKeyRepo, $jwt, $stack, new TenantContext());

        $tenantId = $resolver->resolveOrFail();
        self::assertSame($uuid->toRfc4122(), $tenantId->toString());
    }

    public function testResolvesFromSubdomain(): void
    {
        $uuid = Uuid::v4();
        $tenant = $this->createConfiguredMock(Tenant::class, ['getId' => $uuid]);
        $tenantRepo = $this->createMock(TenantRepository::class);
        $tenantRepo->method('findBySlug')->with('bar')->willReturn($tenant);
        $apiKeyRepo = $this->createMock(ApiKeyRepository::class);
        $jwt = $this->createMock(JwtDecoder::class);
        $stack = new RequestStack();
        $stack->push(Request::create('https://bar.example.com'));
        $resolver = new TenantContextResolver($tenantRepo, $apiKeyRepo, $jwt, $stack, new TenantContext());

        $tenantId = $resolver->resolveOrFail();
        self::assertSame($uuid->toRfc4122(), $tenantId->toString());
    }

    public function testResolvesFromApiKey(): void
    {
        $uuid = Uuid::v4()->toRfc4122();
        $tenantRepo = $this->createMock(TenantRepository::class);
        $apiKeyRepo = $this->createMock(ApiKeyRepository::class);
        $apiKeyRepo->method('tenantIdForKey')->with('abc')->willReturn($uuid);
        $jwt = $this->createMock(JwtDecoder::class);
        $stack = new RequestStack();
        $stack->push(Request::create('/', server: ['HTTP_X_API_KEY' => 'abc']));
        $resolver = new TenantContextResolver($tenantRepo, $apiKeyRepo, $jwt, $stack, new TenantContext());

        $tenantId = $resolver->resolveOrFail();
        self::assertSame($uuid, $tenantId->toString());
    }

    public function testThrowsWhenNoSourceMatches(): void
    {
        $tenantRepo = $this->createMock(TenantRepository::class);
        $apiKeyRepo = $this->createMock(ApiKeyRepository::class);
        $jwt = $this->createMock(JwtDecoder::class);
        $stack = new RequestStack();
        $stack->push(Request::create('/'));
        $resolver = new TenantContextResolver($tenantRepo, $apiKeyRepo, $jwt, $stack, new TenantContext());

        $this->expectException(TenantNotFoundException::class);
        $resolver->resolveOrFail();
    }
}
