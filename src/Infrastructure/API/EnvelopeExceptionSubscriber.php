<?php

declare(strict_types=1);

namespace App\Infrastructure\API;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

#[AsEventListener(event: 'kernel.exception')]
final class EnvelopeExceptionSubscriber
{
    public function __construct(private RequestMetaFactory $metaFactory) {}

    public function __invoke(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        if ('json' !== $request->getRequestFormat()) {
            return;
        }

        $exception = $event->getThrowable();
        $statusCode = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;

        $payload = [
            'status' => 'error',
            'data' => null,
            'meta' => $this->metaFactory->fromRequest($request),
            'errors' => [[
                'message' => $exception->getMessage(),
                'code' => $statusCode,
            ]],
        ];

        $event->setResponse(new JsonResponse($payload, $statusCode));
    }
}
