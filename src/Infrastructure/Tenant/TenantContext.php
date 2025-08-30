<?php

declare(strict_types=1);

namespace App\Infrastructure\Tenant;

final class TenantContext
{
    public function getTenantId(): string
    {
        return '00000000-0000-0000-0000-000000000000';
    }
}
