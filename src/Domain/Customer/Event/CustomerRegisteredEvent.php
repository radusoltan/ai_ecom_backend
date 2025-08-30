<?php

declare(strict_types=1);

namespace App\Domain\Customer\Event;

use App\Shared\Event\AbstractDomainEvent;

if (class_exists(CustomerRegisteredEvent::class, false)) {
    return;
}

final class CustomerRegisteredEvent extends AbstractDomainEvent
{
    public const EVENT_NAME = 'customer.registered';

    public function __construct(
        string $aggregateId,
        string $tenantId,
        public readonly string $email,
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
            'email' => $this->email,
        ];
    }
}

