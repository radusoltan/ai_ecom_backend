<?php

declare(strict_types=1);

namespace App\Shared\Event;

final class EventMetadata
{
    public function __construct(
        public readonly ?string $correlationId = null,
        public readonly ?string $causationId = null,
        public readonly ?string $userId = null,
        public readonly ?string $ip = null,
        public readonly ?string $ua = null,
        public readonly array $context = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            'correlation_id' => $this->correlationId,
            'causation_id' => $this->causationId,
            'user_id' => $this->userId,
            'ip' => $this->ip,
            'ua' => $this->ua,
            'context' => $this->context,
        ];
    }
}

