<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Infrastructure\API\EnvelopeExceptionSubscriber;
use App\Infrastructure\API\EnvelopeResponseSubscriber;
use App\Infrastructure\API\GraphQl\ExtensionsMetaBuilder;
use App\Infrastructure\API\RequestMetaFactory;
use App\Shared\Tenant\TenantContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class EnvelopeResponseTest extends TestCase
{
    public function testRestEnvelope(): void
    {
        $request = new Request();
        $request->setRequestFormat('json');
        $stack = new RequestStack();
        $stack->push($request);
        $factory = new RequestMetaFactory($stack, new TenantContext());
        $subscriber = new EnvelopeResponseSubscriber($factory);

        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new ViewEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, ['foo' => 'bar']);
        $subscriber($event);

        $data = json_decode($event->getResponse()->getContent(), true);
        self::assertSame('success', $data['status']);
        self::assertSame('bar', $data['data']['foo']);
        self::assertArrayHasKey('request_id', $data['meta']);
        self::assertSame([], $data['errors']);
    }

    public function testGraphqlExtensionsMeta(): void
    {
        if (!class_exists(\ApiPlatform\GraphQl\GraphQlOperation::class)) {
            self::markTestSkipped('GraphQL component not installed');
        }

        $request = new Request();
        $request->setRequestFormat('json');
        $stack = new RequestStack();
        $stack->push($request);
        $factory = new RequestMetaFactory($stack, new TenantContext());

        $decorated = new class implements \ApiPlatform\GraphQl\Serializer\SerializerContextBuilderInterface {
            public function create(array $attributes = [], bool $normalization = true, ?string $operationName = null): array
            {
                return [];
            }
        };

        $builder = new ExtensionsMetaBuilder($decorated, $factory);
        $context = $builder->create();
        self::assertArrayHasKey('meta', $context['extensions']);
        self::assertArrayHasKey('request_id', $context['extensions']['meta']);
    }

    public function testErrorEnvelope(): void
    {
        $request = new Request();
        $request->setRequestFormat('json');
        $stack = new RequestStack();
        $stack->push($request);
        $factory = new RequestMetaFactory($stack, new TenantContext());
        $subscriber = new EnvelopeExceptionSubscriber($factory);

        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, new \RuntimeException('boom'));
        $subscriber($event);

        $data = json_decode($event->getResponse()->getContent(), true);
        self::assertSame('error', $data['status']);
        self::assertNull($data['data']);
        self::assertNotEmpty($data['errors']);
    }
}
