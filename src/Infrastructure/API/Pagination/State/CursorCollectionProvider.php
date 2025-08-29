<?php

declare(strict_types=1);

namespace App\Infrastructure\API\Pagination\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;

final class CursorCollectionProvider implements ProviderInterface
{
    public function __construct(private ProviderInterface $decorated)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        return $this->decorated->provide($operation, $uriVariables, $context);
    }
}
