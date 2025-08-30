<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Event;

use App\Shared\Event\AbstractDomainEvent;

if (class_exists(InventoryReservedEvent::class, false)) {
    return;
}

final class InventoryReservedEvent extends AbstractDomainEvent
{
    public const EVENT_NAME = 'inventory.reserved';

    public function __construct(
        string $aggregateId,
        string $tenantId,
        public readonly string $productId,
        public readonly int $quantity,
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
            'product_id' => $this->productId,
            'quantity' => $this->quantity,
        ];
    }
}

