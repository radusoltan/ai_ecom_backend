<?php

declare(strict_types=1);

namespace App\Infrastructure\Messenger;

use App\Infrastructure\Tenant\TenantContext;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\Transport\AmqpStamp;

final class TenantStampingMiddleware implements MiddlewareInterface
{
    public function __construct(private TenantContext $context) {}

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        if (!$envelope->last(AmqpStamp::class)) {
            $tenantId = $this->context->getTenantId();
            $stamp = new AmqpStamp(attributes: ['headers' => ['tenant_id' => $tenantId]]);
            $envelope = $envelope->with($stamp);
        }

        return $stack->next()->handle($envelope, $stack);
    }
}
