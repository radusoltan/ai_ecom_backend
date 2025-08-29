<?php

declare(strict_types=1);

namespace App\Infrastructure\API\GraphQl\Resolver;

final class OrderResolver
{
    public function __invoke(mixed $item, array $context): array
    {
        $id = $context['args']['id'] ?? '1';
        return ['id' => $id, 'status' => 'NEW'];
    }
}
