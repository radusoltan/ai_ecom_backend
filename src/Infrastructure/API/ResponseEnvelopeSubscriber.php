<?php

namespace App\Infrastructure\API;

use App\Shared\Tenant\TenantContext;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::RESPONSE)]
final class ResponseEnvelopeSubscriber
{
    public function __construct(private TenantContext $context)
    {
    }

    public function __invoke(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();
        $content = $response->getContent();
        if (!$content) {
            return;
        }
        $data = json_decode($content, true);
        if (!is_array($data)) {
            return;
        }
        if (!isset($data['meta']) || !is_array($data['meta'])) {
            $data['meta'] = [];
        }
        if ($this->context->has()) {
            $data['meta']['tenant_id'] = $this->context->get();
        }
        $response->setContent(json_encode($data));
    }
}
