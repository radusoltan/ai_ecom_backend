<?php

declare(strict_types=1);

namespace App\Infrastructure\Bus\Stamp;

use Symfony\Component\Messenger\Stamp\StampInterface;

final class CausationIdStamp implements StampInterface
{
    public function __construct(private string $causationId)
    {
    }

    public function getCausationId(): string
    {
        return $this->causationId;
    }
}

