<?php

namespace App\Tests\Shared\Tenant;

use App\Domain\Tenant\Entity\Tenant;
use App\Shared\Tenant\ApiKeyRepository;
use App\Shared\Tenant\JwtDecoder;
use App\Shared\Tenant\TenantRepository;
use App\Shared\Tenant\TenantResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Uid\Uuid;

class TenantResolverTest extends TestCase
{
    public function test_resolves_from_jwt(): void
    {
        $tenantRepo = $this->createMock(TenantRepository::class);
        $apiKeyRepo = $this->createMock(ApiKeyRepository::class);
        $jwt = $this->createMock(JwtDecoder::class);
        $jwt->method('decodeFromHeader')->with('Bearer token')->willReturn(['tenant_id' => 't1']);

        $resolver = new TenantResolver($tenantRepo, $apiKeyRepo, $jwt);
        $req = Request::create('/', server: ['HTTP_AUTHORIZATION' => 'Bearer token']);

        self::assertSame('t1', $resolver->resolve($req));
    }

    public function test_resolves_from_custom_domain(): void
    {
        $tenant = $this->createConfiguredMock(Tenant::class, ['getId' => Uuid::fromString('00000000-0000-0000-0000-000000000002')]);
        $tenantRepo = $this->createMock(TenantRepository::class);
        $tenantRepo->method('findByCustomDomain')->with('foo.com')->willReturn($tenant);
        $apiKeyRepo = $this->createMock(ApiKeyRepository::class);
        $jwt = $this->createMock(JwtDecoder::class);

        $resolver = new TenantResolver($tenantRepo, $apiKeyRepo, $jwt);
        $req = Request::create('https://foo.com');

        self::assertSame('00000000-0000-0000-0000-000000000002', $resolver->resolve($req));
    }

    public function test_resolves_from_subdomain(): void
    {
        $tenant = $this->createConfiguredMock(Tenant::class, ['getId' => Uuid::fromString('00000000-0000-0000-0000-000000000003')]);
        $tenantRepo = $this->createMock(TenantRepository::class);
        $tenantRepo->method('findBySlug')->with('bar')->willReturn($tenant);
        $apiKeyRepo = $this->createMock(ApiKeyRepository::class);
        $jwt = $this->createMock(JwtDecoder::class);

        $resolver = new TenantResolver($tenantRepo, $apiKeyRepo, $jwt);
        $req = Request::create('https://bar.example.com');

        self::assertSame('00000000-0000-0000-0000-000000000003', $resolver->resolve($req));
    }

    public function test_resolves_from_api_key(): void
    {
        $tenantRepo = $this->createMock(TenantRepository::class);
        $apiKeyRepo = $this->createMock(ApiKeyRepository::class);
        $apiKeyRepo->method('tenantIdForKey')->with('abc')->willReturn('t4');
        $jwt = $this->createMock(JwtDecoder::class);

        $resolver = new TenantResolver($tenantRepo, $apiKeyRepo, $jwt);
        $req = Request::create('/', server: ['HTTP_X_API_KEY' => 'abc']);

        self::assertSame('t4', $resolver->resolve($req));
    }

    public function test_returns_null_when_no_source_matches(): void
    {
        $tenantRepo = $this->createMock(TenantRepository::class);
        $apiKeyRepo = $this->createMock(ApiKeyRepository::class);
        $jwt = $this->createMock(JwtDecoder::class);

        $resolver = new TenantResolver($tenantRepo, $apiKeyRepo, $jwt);
        $req = Request::create('/');

        self::assertNull($resolver->resolve($req));
    }
}
