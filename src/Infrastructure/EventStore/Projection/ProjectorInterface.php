<?php

declare(strict_types=1);

namespace App\Infrastructure\EventStore\Projection;

interface ProjectorInterface
{
    public function project(array $eventRow): void;

    public function supports(string $eventName): bool;
}

