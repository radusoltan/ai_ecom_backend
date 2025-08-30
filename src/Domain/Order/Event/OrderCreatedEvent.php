<?php

declare(strict_types=1);

namespace App\Domain\Order\Event;

use App\Shared\Event\AbstractDomainEvent;

if (class_exists(OrderCreatedEvent::class, false)) {
    return;
}

final class OrderCreatedEvent extends AbstractDomainEvent
{
    public const EVENT_NAME = 'order.created';

    public function __construct(
        string $aggregateId,
        string $tenantId,
        public readonly int $itemsCount,
        public readonly string $currency,
        public readonly int $totalMinor,
        array $metadata = [],
        \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
        parent::__construct($aggregateId, $tenantId, $occurredAt, $metadata);
    }

    public static function eventName(): string
    {
        return self::EVENT_NAME;
    }

    public function getPayload(): array
    {
        return [
            'items_count' => $this->itemsCount,
            'currency' => $this->currency,
            'total_minor' => $this->totalMinor,
        ];
    }
}

