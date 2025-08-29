<?php

declare(strict_types=1);

namespace App\Shared\Context;

use App\Shared\Tenant\TenantContext;
use App\Shared\Tenant\TenantId;

final class RequestContext implements Context
{
    public function __construct(private TenantContext $tenantContext)
    {
    }

    public function tenant(): TenantId
    {
        // TODO: resolve tenant from security token or request attributes
        return $this->tenantContext->get();
    }
}
