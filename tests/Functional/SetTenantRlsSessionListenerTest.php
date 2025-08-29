<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Infrastructure\Http\ResponseEnvelope\ResponseEnvelopeListener;
use App\Infrastructure\Persistence\Doctrine\Filter\TenantFilter;
use App\Infrastructure\Persistence\Doctrine\SetTenantRlsSessionListener;
use App\Infrastructure\Security\TenantContextResolver;
use App\Shared\Tenant\TenantContext;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\FilterCollection;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Uid\Uuid;

class SetTenantRlsSessionListenerTest extends TestCase
{
    private function handleRequest(string $tenantId, Request $request): array
    {
        $context = new TenantContext();
        $tenantRepo = $this->createMock(\App\Shared\Tenant\TenantRepository::class);
        $apiRepo = $this->createMock(\App\Shared\Tenant\ApiKeyRepository::class);
        $jwt = $this->createMock(\App\Shared\Tenant\JwtDecoder::class);
        $jwt->method('decodeFromHeader')->willReturn(['tenant_id' => $tenantId]);
        $stack = new \Symfony\Component\HttpFoundation\RequestStack();
        $stack->push($request);
        $resolver = new TenantContextResolver($tenantRepo, $apiRepo, $jwt, $stack, $context);

        $conn = $this->createMock(Connection::class);
        $conn->expects($this->once())
            ->method('executeStatement')
            ->with('SET app.tenant_id = :tenant', ['tenant' => $tenantId]);

        $em = $this->createMock(EntityManagerInterface::class);
        $filters = $this->createMock(FilterCollection::class);
        $tenantFilter = new TenantFilter($em);
        $filters->expects($this->once())->method('enable')->with('tenant_filter')->willReturn($tenantFilter);
        $em->method('getFilters')->willReturn($filters);
        $em->method('getConnection')->willReturn($conn);

        $listener = new SetTenantRlsSessionListener($conn, $em, $resolver, new NullLogger());

        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
        $listener($event);

        return [$context, $tenantFilter];
    }

    public function testTenantContextAndResponseEnvelope(): void
    {
        $request = new Request([], [], [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer token']);
        $uuid = Uuid::v4()->toRfc4122();
        [$context, $filter] = $this->handleRequest($uuid, $request);

        self::assertTrue($context->has());
        self::assertSame($uuid, $context->get()->toString());

        $response = new JsonResponse(['status' => 'ok', 'data' => null, 'meta' => [], 'errors' => []]);
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);
        $subscriber = new ResponseEnvelopeListener($context);
        $subscriber($event);

        $data = json_decode($response->getContent(), true);
        self::assertSame($uuid, $data['meta']['tenant_id']);
    }

    public function testMissingTenantThrowsException(): void
    {
        $request = new Request();
        $context = new TenantContext();
        $tenantRepo = $this->createMock(\App\Shared\Tenant\TenantRepository::class);
        $apiRepo = $this->createMock(\App\Shared\Tenant\ApiKeyRepository::class);
        $jwt = $this->createMock(\App\Shared\Tenant\JwtDecoder::class);
        $jwt->method('decodeFromHeader')->willReturn([]);
        $stack = new \Symfony\Component\HttpFoundation\RequestStack();
        $stack->push($request);
        $resolver = new TenantContextResolver($tenantRepo, $apiRepo, $jwt, $stack, $context);
        $conn = $this->createMock(Connection::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $listener = new SetTenantRlsSessionListener($conn, $em, $resolver, new NullLogger());
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $this->expectException(\App\Shared\Tenant\TenantNotFoundException::class);
        $listener($event);
    }
}
