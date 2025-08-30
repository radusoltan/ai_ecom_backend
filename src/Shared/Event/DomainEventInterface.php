<?php

declare(strict_types=1);

namespace App\Shared\Event;
if (interface_exists(DomainEventInterface::class, false)) {
    return;
}

interface DomainEventInterface
{
    public function getAggregateId(): string;

    public function getTenantId(): string;

    public function getEventName(): string;

    public function getPayload(): array;

    public function getOccurredAt(): \DateTimeImmutable;

    public function getMetadata(): array;
}

