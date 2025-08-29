<?php

namespace App\Shared\Tenant;

final class TenantContext
{
    public function __construct(private ?string $tenantId = null)
    {
    }

    public function set(string $tenantId): void
    {
        $this->tenantId = $tenantId;
    }

    public function get(): string
    {
        if (!$this->tenantId) {
            throw new \LogicException('Tenant not resolved');
        }

        return $this->tenantId;
    }

    public function has(): bool
    {
        return (bool) $this->tenantId;
    }
}
