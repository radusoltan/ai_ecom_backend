<?php

namespace App\Shared\Tenant;

use App\Domain\Tenant\Entity\Tenant;

interface TenantRepository
{
    public function findByCustomDomain(string $domain): ?Tenant;

    public function findBySlug(string $slug): ?Tenant;
}
