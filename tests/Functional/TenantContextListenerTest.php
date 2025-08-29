<?php

namespace App\Tests\Functional;

use App\Infrastructure\Http\TenantContextListener;
use App\Infrastructure\API\ResponseEnvelopeSubscriber;
use App\Infrastructure\Persistence\Doctrine\Filter\TenantFilter;
use App\Shared\Tenant\TenantContext;
use App\Shared\Tenant\TenantResolver;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\FilterCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Psr\Log\NullLogger;

class TenantContextListenerTest extends TestCase
{
    private function handleRequest(string $tenantId, Request $request): array
    {
        $context = new TenantContext();
        $tenantRepo = $this->createMock(\App\Shared\Tenant\TenantRepository::class);
        $apiRepo = $this->createMock(\App\Shared\Tenant\ApiKeyRepository::class);
        $jwt = $this->createMock(\App\Shared\Tenant\JwtDecoder::class);
        $jwt->method('decodeFromHeader')->willReturn(['tenant_id' => $tenantId]);
        $resolver = new TenantResolver($tenantRepo, $apiRepo, $jwt);

        $conn = $this->createMock(Connection::class);
        $conn->expects($this->once())
            ->method('executeStatement')
            ->with('SET app.tenant_id = :tenant', ['tenant' => $tenantId]);

        $em = $this->createMock(EntityManagerInterface::class);
        $filters = $this->createMock(FilterCollection::class);
        $tenantFilter = new TenantFilter($em);
        $filters->expects($this->once())->method('enable')->with('tenant')->willReturn($tenantFilter);
        $em->method('getFilters')->willReturn($filters);
        $em->method('getConnection')->willReturn($conn);

        $listener = new TenantContextListener($resolver, $context, $em, $conn, new NullLogger());

        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
        $listener($event);

        return [$context, $tenantFilter];
    }

    public function test_tenant_context_and_response_envelope(): void
    {
        $request = new Request([], [], [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer token']);
        [$context, $filter] = $this->handleRequest('tenantA', $request);

        self::assertTrue($context->has());
        self::assertSame('tenantA', $context->get());

        $response = new JsonResponse(['status' => 'ok', 'data' => null, 'meta' => [], 'errors' => []]);
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);
        $subscriber = new ResponseEnvelopeSubscriber($context);
        $subscriber($event);

        $data = json_decode($response->getContent(), true);
        self::assertSame('tenantA', $data['meta']['tenant_id']);
    }

    public function test_missing_tenant_throws_exception(): void
    {
        $request = new Request();
        $context = new TenantContext();
        $tenantRepo = $this->createMock(\App\Shared\Tenant\TenantRepository::class);
        $apiRepo = $this->createMock(\App\Shared\Tenant\ApiKeyRepository::class);
        $jwt = $this->createMock(\App\Shared\Tenant\JwtDecoder::class);
        $jwt->method('decodeFromHeader')->willReturn([]);
        $resolver = new TenantResolver($tenantRepo, $apiRepo, $jwt);
        $conn = $this->createMock(Connection::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $listener = new TenantContextListener($resolver, $context, $em, $conn, new NullLogger());
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $this->expectException(\DomainException::class);
        $listener($event);
    }
}
