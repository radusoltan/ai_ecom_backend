<?php

declare(strict_types=1);

namespace App\Shared\Tenant;

final class TenantContext
{
    private ?TenantId $tenantId;

    public function __construct(?TenantId $tenantId = null)
    {
        $this->tenantId = $tenantId;
    }

    public function set(TenantId $tenantId): void
    {
        $this->tenantId = $tenantId;
    }

    public function get(): TenantId
    {
        if (!$this->tenantId) {
            throw new \LogicException('Tenant not resolved');
        }

        return $this->tenantId;
    }

    public function has(): bool
    {
        return null !== $this->tenantId;
    }
}
