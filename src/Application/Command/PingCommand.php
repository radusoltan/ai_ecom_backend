<?php

declare(strict_types=1);

namespace App\Application\Command;

final class PingCommand
{
    public function __construct(
        public readonly string $messageId,
        public readonly \DateTimeImmutable $occurredAt,
        public readonly array $payload = []
    ) {
    }
}
