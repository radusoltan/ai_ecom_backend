<?php

declare(strict_types=1);

namespace App\Infrastructure\API\GraphQL\Resolver;

use ApiPlatform\Metadata\GraphQl\Resolver\AsGraphQlResolver;
use App\Domain\Catalog\Product;
use App\Shared\Context\Context;
use App\Shared\View\ProductView;
use App\Shared\View\ProductViewFactory;

#[AsGraphQlResolver(entity: Product::class, operation: 'item_query')]
final class ProductResolver
{
    public function __construct(private ProductViewFactory $viewFactory)
    {
    }

    public function __invoke(Product $product, Context $context): ProductView
    {
        // TODO: DataLoader batching for attributes, variants and images
        // TODO: Add error handling and GraphQL extensions envelope
        return $this->viewFactory->fromEntity($product, $context->tenant());
    }
}
