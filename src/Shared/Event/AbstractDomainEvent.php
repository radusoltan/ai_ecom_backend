<?php

declare(strict_types=1);

namespace App\Shared\Event;

use Symfony\Component\Uid\Ulid;

abstract class AbstractDomainEvent implements DomainEventInterface
{
    public readonly string $eventId;

    public function __construct(
        protected readonly string $aggregateId,
        protected readonly string $tenantId,
        protected readonly \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
        protected readonly array $metadata = [],
    ) {
        $this->eventId = (string) new Ulid();
    }

    abstract public static function eventName(): string;

    public function getAggregateId(): string
    {
        return $this->aggregateId;
    }

    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getEventName(): string
    {
        return static::eventName();
    }
}

