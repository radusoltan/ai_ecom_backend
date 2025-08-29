<?php

declare(strict_types=1);

namespace App\Infrastructure\API\EventListener;

use App\Infrastructure\API\Pagination\InvalidCursorException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

final class InvalidCursorExceptionListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => 'onKernelException'];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();
        if (!$throwable instanceof InvalidCursorException) {
            return;
        }

        $request = $event->getRequest();
        $request->attributes->set('error_code', 'INVALID_CURSOR');
        $event->setThrowable(new BadRequestHttpException($throwable->getMessage(), $throwable));
    }
}
