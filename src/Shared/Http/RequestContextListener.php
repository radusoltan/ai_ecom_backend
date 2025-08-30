<?php

declare(strict_types=1);

namespace App\Shared\Http;

use App\Shared\Http\Attribute\SkipEnvelope;
use App\Shared\Tenant\TenantContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class RequestContextListener implements EventSubscriberInterface
{
    public function __construct(private TenantContext $tenantContext)
    {
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if ($this->tenantContext->has()) {
            $request->attributes->set('tenant_id', $this->tenantContext->get()->toString());
        }
    }

    public function onController(ControllerEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $controller = $event->getController();
        $reflection = null;
        if (\is_array($controller)) {
            $reflection = new \ReflectionMethod($controller[0], $controller[1]);
        } elseif (\is_object($controller)) {
            $reflection = new \ReflectionClass($controller);
        }

        if (!$reflection) {
            return;
        }

        $attributes = [];
        if ($reflection instanceof \ReflectionMethod) {
            $attributes = $reflection->getAttributes(SkipEnvelope::class);
            $classAttrs = $reflection->getDeclaringClass()->getAttributes(SkipEnvelope::class);
            $attributes = array_merge($attributes, $classAttrs);
        } else {
            $attributes = $reflection->getAttributes(SkipEnvelope::class);
        }

        if ($attributes) {
            $event->getRequest()->attributes->set('skip_envelope', true);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 100],
            KernelEvents::CONTROLLER => ['onController', 0],
        ];
    }
}
