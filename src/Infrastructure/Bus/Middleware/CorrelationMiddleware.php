<?php

declare(strict_types=1);

namespace App\Infrastructure\Bus\Middleware;

use App\Infrastructure\Bus\Stamp\CausationIdStamp;
use App\Infrastructure\Bus\Stamp\CorrelationIdStamp;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Uid\Ulid;

final class CorrelationMiddleware implements MiddlewareInterface
{
    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $correlationStamp = $envelope->last(CorrelationIdStamp::class);
        if (!$correlationStamp) {
            $correlationStamp = new CorrelationIdStamp((string) new Ulid());
            $envelope = $envelope->with($correlationStamp);
        }

        $envelope = $envelope->with(new CausationIdStamp((string) new Ulid()));

        return $stack->next()->handle($envelope, $stack);
    }
}

