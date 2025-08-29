<?php

namespace App\Shared\Tenant;

final class NullApiKeyRepository implements ApiKeyRepository
{
    public function tenantIdForKey(string $key): ?string
    {
        return null;
    }
}
