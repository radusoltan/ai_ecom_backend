<?php

declare(strict_types=1);

namespace App\Shared\Tenant;

final class FakeTenantContext implements TenantContextInterface
{
    public function getTenantId(): string
    {
        return '00000000-0000-0000-0000-000000000000';
    }
}

