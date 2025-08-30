<?php

declare(strict_types=1);

namespace App\Domain\Product\Event;

use App\Shared\Event\AbstractDomainEvent;

if (class_exists(ProductPriceChangedEvent::class, false)) {
    return;
}

final class ProductPriceChangedEvent extends AbstractDomainEvent
{
    public const EVENT_NAME = 'product.price_changed';

    public function __construct(
        string $aggregateId,
        string $tenantId,
        public readonly int $oldPrice,
        public readonly int $newPrice,
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
            'old_price' => $this->oldPrice,
            'new_price' => $this->newPrice,
        ];
    }
}

