<?php

declare(strict_types=1);

namespace App\Infrastructure\Bus\Stamp;

use Symfony\Component\Messenger\Stamp\StampInterface;

final class CorrelationIdStamp implements StampInterface
{
    public function __construct(private string $correlationId)
    {
    }

    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }
}

