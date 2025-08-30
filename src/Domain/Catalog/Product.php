<?php

declare(strict_types=1);

namespace App\Domain\Catalog;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Infrastructure\API\State\ProductProcessor;
use Symfony\Component\Uid\Uuid;

/**
 * Minimal Product entity stub.
 * TODO: Expand with persistence mapping and additional fields.
 */
#[ApiResource(operations: [
    new Post(security: "is_granted('product.write', object ?? null)", processor: ProductProcessor::class),
    new Put(security: "is_granted('product.write', object ?? null)", processor: ProductProcessor::class),
    new Patch(security: "is_granted('product.write', object ?? null)", processor: ProductProcessor::class),
    new Delete(security: "is_granted('product.write', object ?? null)", processor: ProductProcessor::class),
])]
final class Product
{
    public function __construct(
        private Uuid $id,
        private string $slug,
        private string $name,
        private int $priceCents,
        private string $currency,
        public ?string $tenantId = null,
    ) {
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPriceCents(): int
    {
        return $this->priceCents;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    // TODO: add attributes, variants, images relationships
}
