<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Shared\Http\EnvelopeResponseSubscriber;
use App\Shared\Http\RequestMetaFactory;
use ApiPlatform\State\Pagination\PaginatorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final class EnvelopeResponseTest extends TestCase
{
    private function createFactory(Request $request): EnvelopeResponseSubscriber
    {
        $stack = new RequestStack();
        $stack->push($request);
        $factory = new RequestMetaFactory($stack);
        return new EnvelopeResponseSubscriber($factory, true);
    }

    public function testSuccessEnvelope(): void
    {
        $request = new Request();
        $request->setRequestFormat('json');
        $request->attributes->set('tenant_id', 't1');
        $subscriber = $this->createFactory($request);

        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new ViewEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, ['foo' => 'bar']);
        $subscriber->onView($event);
        $payload = json_decode($event->getResponse()->getContent(), true);
        self::assertSame('success', $payload['status']);
        self::assertSame('bar', $payload['data']['foo']);
        self::assertArrayHasKey('timestamp', $payload['meta']);
        self::assertArrayHasKey('request_id', $payload['meta']);
        self::assertSame('t1', $payload['meta']['tenant_id']);
        self::assertSame([], $payload['errors']);
    }

    public function testPaginationEnvelope(): void
    {
        if (!interface_exists(PaginatorInterface::class)) {
            self::markTestSkipped();
        }

        $request = new Request();
        $request->setRequestFormat('json');
        $subscriber = $this->createFactory($request);

        $paginator = new class([1,2,3]) extends \ArrayIterator implements PaginatorInterface {
            public function getLastPage(): float { return 2; }
            public function getTotalItems(): float { return 30; }
            public function getCurrentPage(): float { return 1; }
            public function getItemsPerPage(): float { return 15; }
        };

        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new ViewEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $paginator);
        $subscriber->onView($event);
        $payload = json_decode($event->getResponse()->getContent(), true);
        self::assertTrue($payload['meta']['pagination']['has_more']);
        self::assertSame(30, $payload['meta']['pagination']['total']);
        self::assertSame(15, $payload['meta']['pagination']['limit']);
    }

    public function testValidationErrorEnvelope(): void
    {
        $request = new Request();
        $request->setRequestFormat('json');
        $subscriber = $this->createFactory($request);

        $violation = new ConstraintViolation('msg', null, [], '', 'field', null, null, Assert\NotBlank::IS_BLANK_ERROR, null);
        $list = new ConstraintViolationList([$violation]);
        $exception = new ValidationFailedException('data', $list);

        $kernel = $this->createMock(KernelInterface::class);
        $event = new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);
        $subscriber->onException($event);
        $payload = json_decode($event->getResponse()->getContent(), true);
        self::assertSame('error', $payload['status']);
        self::assertNull($payload['data']);
        self::assertSame('field', $payload['errors'][0]['field']);
    }

    public function testExceptionEnvelope(): void
    {
        $request = new Request();
        $request->setRequestFormat('json');
        $subscriber = $this->createFactory($request);

        $kernel = $this->createMock(KernelInterface::class);
        $event = new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, new NotFoundHttpException('missing'));
        $subscriber->onException($event);
        $payload = json_decode($event->getResponse()->getContent(), true);
        self::assertSame('error', $payload['status']);
        self::assertSame(404, $event->getResponse()->getStatusCode());
        self::assertNotEmpty($payload['errors']);
    }
}
