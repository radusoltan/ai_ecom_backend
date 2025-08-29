<?php

declare(strict_types=1);

namespace App\Infrastructure\Bus;

use Symfony\Component\Messenger\Stamp\StampInterface;

final class TenantIdStamp implements StampInterface
{
    public function __construct(private string $tenantId) {}

    public function getTenantId(): string
    {
        return $this->tenantId;
    }
}
