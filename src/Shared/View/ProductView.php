<?php

declare(strict_types=1);

namespace App\Shared\View;

/**
 * Immutable projection of Product for API output.
 */
final readonly class ProductView
{
    /**
     * @param list<string>                       $images
     * @param array<string,string>               $attributes
     * @param list<array{id:string,name:string}> $variants
     */
    public function __construct(
        public string $id,
        public string $slug,
        public string $name,
        public int $priceCents,
        public string $currency,
        public array $images = [],
        public array $attributes = [],
        public array $variants = [],
    ) {
    }
}
