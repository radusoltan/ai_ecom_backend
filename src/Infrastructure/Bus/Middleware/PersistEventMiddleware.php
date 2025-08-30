<?php

declare(strict_types=1);

namespace App\Infrastructure\Bus\Middleware;

use App\Infrastructure\Bus\Stamp\CausationIdStamp;
use App\Infrastructure\Bus\Stamp\CorrelationIdStamp;
use App\Infrastructure\EventStore\EventStoreRepository;
use App\Shared\Event\DomainEventInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

final class PersistEventMiddleware implements MiddlewareInterface
{
    public function __construct(private EventStoreRepository $eventStore)
    {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $message = $envelope->getMessage();

        if ($message instanceof DomainEventInterface) {
            $metadata = $message->getMetadata();
            $metadata['tenant_id'] = $message->getTenantId();
            if ($stamp = $envelope->last(CorrelationIdStamp::class)) {
                $metadata['correlation_id'] = $stamp->getCorrelationId();
            }
            if ($stamp = $envelope->last(CausationIdStamp::class)) {
                $metadata['causation_id'] = $stamp->getCausationId();
            }

            $this->eventStore->append($message, $message::class, null, $metadata);
        }

        return $stack->next()->handle($envelope, $stack);
    }
}

