<?php

declare(strict_types=1);

namespace App\Shared\Feature;

use Symfony\Component\Uid\Uuid;

final class FeatureFlagService
{
    /** @var array<string, array<string,bool>> */
    private array $flags = [];

    public function enabled(string $name, Uuid $tenantId): bool
    {
        return $this->flags[$tenantId->toRfc4122()][$name] ?? false;
    }

    public function set(Uuid $tenantId, string $name, bool $enabled): void
    {
        $this->flags[$tenantId->toRfc4122()][$name] = $enabled;
    }
}

