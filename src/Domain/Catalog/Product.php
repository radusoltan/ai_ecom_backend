<?php

declare(strict_types=1);

namespace App\Domain\Catalog;

use Symfony\Component\Uid\Uuid;

/**
 * Minimal Product entity stub.
 * TODO: Expand with persistence mapping and additional fields.
 */
final class Product
{
    public function __construct(
        private Uuid $id,
        private string $slug,
        private string $name,
        private int $priceCents,
        private string $currency,
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
