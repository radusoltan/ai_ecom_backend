<?php

declare(strict_types=1);

namespace App\Infrastructure\API\GraphQl\Resolver;

final class SearchProductsResolver
{
    public function __invoke(mixed $item, array $context): array
    {
        return [
            ['id' => '1', 'name' => 'Sample product']
        ];
    }
}
