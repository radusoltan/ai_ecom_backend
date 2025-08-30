<?php

declare(strict_types=1);

namespace App\Shared\Tenant;

interface TenantContextInterface
{
    public function getTenantId(): string;
}

