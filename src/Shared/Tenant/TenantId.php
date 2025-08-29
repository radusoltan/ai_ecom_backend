<?php

declare(strict_types=1);

namespace App\Shared\Tenant;

use Symfony\Component\Uid\Uuid;

/**
 * @psalm-immutable
 */
final class TenantId
{
    private string $value;

    public function __construct(string $value)
    {
        if (!Uuid::isValid($value)) {
            throw new \InvalidArgumentException('Invalid tenant UUID');
        }
        $this->value = $value;
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
