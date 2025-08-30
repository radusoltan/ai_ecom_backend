<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Shared\Http\EnvelopeResponseSubscriber;
use App\Shared\Http\RequestIdSubscriber;
use App\Shared\Http\RequestMetaFactory;
use App\Shared\Logging\RequestContextProcessor;
use App\Shared\Tenant\TenantContext;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

final class RequestIdSubscriberTest extends TestCase
{
    public function testGeneratesAndPropagatesRequestId(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        $request->setRequestFormat('json');
        $stack = new RequestStack();
        $stack->push($request);

        $subscriber = new RequestIdSubscriber();
        $subscriber->onRequest(new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST));

        $logger = new Logger('test');
        $handler = new TestHandler();
        $processor = new RequestContextProcessor($stack, new TokenStorage(), new TenantContext());
        $logger->pushHandler($handler);
        $logger->pushProcessor($processor);
        $logger->info('hello');

        $factory = new RequestMetaFactory($stack);
        $envelopeSubscriber = new EnvelopeResponseSubscriber($factory, true);
        $viewEvent = new ViewEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, ['foo' => 'bar']);
        $envelopeSubscriber->onView($viewEvent);
        $response = $viewEvent->getResponse();

        $subscriber->onResponse(new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response));
        $payload = json_decode($response->getContent(), true);
        $logRecord = $handler->getRecords()[0];

        $requestId = $payload['meta']['request_id'];
        self::assertNotEmpty($requestId);
        self::assertSame($requestId, $response->headers->get('X-Request-ID'));
        self::assertSame($requestId, $logRecord->extra['request_id']);
    }

    public function testReusesIncomingRequestId(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        $request->headers->set('X-Request-ID', 'incoming');
        $request->setRequestFormat('json');
        $stack = new RequestStack();
        $stack->push($request);

        $subscriber = new RequestIdSubscriber();
        $subscriber->onRequest(new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST));

        $logger = new Logger('test');
        $handler = new TestHandler();
        $processor = new RequestContextProcessor($stack, new TokenStorage(), new TenantContext());
        $logger->pushHandler($handler);
        $logger->pushProcessor($processor);
        $logger->info('hello');

        $factory = new RequestMetaFactory($stack);
        $envelopeSubscriber = new EnvelopeResponseSubscriber($factory, true);
        $viewEvent = new ViewEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, ['foo' => 'bar']);
        $envelopeSubscriber->onView($viewEvent);
        $response = $viewEvent->getResponse();

        $subscriber->onResponse(new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response));
        $payload = json_decode($response->getContent(), true);
        $logRecord = $handler->getRecords()[0];

        self::assertSame('incoming', $payload['meta']['request_id']);
        self::assertSame('incoming', $response->headers->get('X-Request-ID'));
        self::assertSame('incoming', $logRecord->extra['request_id']);
    }
}
