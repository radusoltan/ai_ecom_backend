<?php

declare(strict_types=1);

namespace App\Infrastructure\Bus;

use App\Infrastructure\Security\TenantContextResolver;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

final class AddTenantStampMiddleware implements MiddlewareInterface
{
    public function __construct(private TenantContextResolver $resolver) {}

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        if (!$envelope->last(TenantIdStamp::class)) {
            $tenantId = $this->resolver->resolveOrFail()->toString();
            $envelope = $envelope->with(new TenantIdStamp($tenantId));
        }

        return $stack->next()->handle($envelope, $stack);
    }
}
