<?php

namespace App\Shared\Tenant;

use App\Domain\Tenant\Entity\Tenant;

final class NullTenantRepository implements TenantRepository
{
    public function findByCustomDomain(string $domain): ?Tenant
    {
        return null;
    }

    public function findBySlug(string $slug): ?Tenant
    {
        return null;
    }
}
