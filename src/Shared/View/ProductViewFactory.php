<?php

declare(strict_types=1);

namespace App\Shared\View;

use App\Domain\Catalog\Product;
use App\Shared\Tenant\TenantId;

final class ProductViewFactory
{
    public function fromEntity(Product $product, TenantId $tenantId): ProductView
    {
        // TODO: use batching/data loaders for nested collections
        return new ProductView(
            id: $product->getId()->toRfc4122(),
            slug: $product->getSlug(),
            name: $product->getName(),
            priceCents: $product->getPriceCents(),
            currency: $product->getCurrency(),
            images: [],      // TODO: load images via DataLoader
            attributes: [],  // TODO: resolve attributes lazily
            variants: [],    // TODO: hydrate variants
        );
    }
}
