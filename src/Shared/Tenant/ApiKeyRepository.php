<?php

namespace App\Shared\Tenant;

interface ApiKeyRepository
{
    public function tenantIdForKey(string $key): ?string;
}
