<?php

declare(strict_types=1);

namespace App\Infrastructure\API\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Domain\Order\Order;
use Symfony\Component\Uid\Uuid;

final class OrderProvider implements ProviderInterface
{
    public const CUSTOMER_ID = '00000000-0000-0000-8000-000000000001';
    public const TENANT_ID = '11111111-1111-1111-8111-111111111111';

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): Order
    {
        $id = Uuid::fromString((string) ($uriVariables['id'] ?? Uuid::v4()->toRfc4122()));

        return new Order($id, self::CUSTOMER_ID, self::TENANT_ID);
    }
}

