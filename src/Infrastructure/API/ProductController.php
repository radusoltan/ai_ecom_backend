<?php

declare(strict_types=1);

namespace App\Infrastructure\API;

use App\Domain\Catalog\Product;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

final class ProductController extends AbstractController
{
    #[Route('/api/products', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = $request->toArray();
        $product = new Product(
            Uuid::fromString($data['id']),
            $data['slug'],
            $data['name'],
            (int) $data['priceCents'],
            $data['currency'],
            $data['tenantId'] ?? null,
        );

        $this->denyAccessUnlessGranted('product.write', $product);

        return new JsonResponse(['id' => $product->getId()->toRfc4122()], 201);
    }
}

