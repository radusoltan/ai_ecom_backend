<?php

declare(strict_types=1);

namespace App\Domain\Order;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Infrastructure\API\State\OrderProvider;
use Symfony\Component\Uid\Uuid;

#[ApiResource(provider: OrderProvider::class, operations: [
    new Get(security: "is_granted('order.view', object)"),
])]
final class Order
{
    public function __construct(
        private Uuid $id,
        public string $customerId,
        public string $tenantId,
    ) {
    }

    public function getId(): Uuid
    {
        return $this->id;
    }
}

