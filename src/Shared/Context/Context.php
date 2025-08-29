<?php

declare(strict_types=1);

namespace App\Shared\Context;

use App\Shared\Tenant\TenantId;

interface Context
{
    public function tenant(): TenantId;
}
