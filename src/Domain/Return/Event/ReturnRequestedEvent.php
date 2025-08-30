<?php

declare(strict_types=1);

namespace App\Domain\Return\Event;

use App\Shared\Event\AbstractDomainEvent;

if (class_exists(ReturnRequestedEvent::class, false)) {
    return;
}

final class ReturnRequestedEvent extends AbstractDomainEvent
{
    public const EVENT_NAME = 'return.requested';

    public function __construct(
        string $aggregateId,
        string $tenantId,
        public readonly string $orderId,
        public readonly int $itemsCount,
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
            'order_id' => $this->orderId,
            'items_count' => $this->itemsCount,
        ];
    }
}

