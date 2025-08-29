<?php

declare(strict_types=1);

namespace App\Infrastructure\API;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ViewEvent;

#[AsEventListener(event: 'kernel.view', priority: -255)]
final class EnvelopeResponseSubscriber
{
    public function __construct(private RequestMetaFactory $metaFactory) {}

    public function __invoke(ViewEvent $event): void
    {
        $controllerResult = $event->getControllerResult();
        $request = $event->getRequest();
        if ('json' !== $request->getRequestFormat()) {
            return;
        }

        $payload = [
            'status' => 'success',
            'data'   => $controllerResult,
            'meta'   => $this->metaFactory->fromRequest($request),
            'errors' => [],
        ];

        $event->setResponse(new JsonResponse($payload));
    }
}
