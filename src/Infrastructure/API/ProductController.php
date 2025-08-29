<?php

declare(strict_types=1);

namespace App\Infrastructure\API;

use Symfony\Component\Routing\Attribute\Route;

final class ProductController
{
    #[Route('/products', methods: ['GET'])]
    public function __invoke(): array
    {
        return [
            ['id' => 1, 'name' => 'Sample']
        ];
    }
}
