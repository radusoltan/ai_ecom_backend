<?php

declare(strict_types=1);

namespace App\Infrastructure\API;

use App\Domain\Order\Order;
use App\Infrastructure\API\State\OrderProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

final class OrderController extends AbstractController
{
    #[Route('/api/orders/{id}', methods: ['GET'])]
    public function __invoke(string $id): JsonResponse
    {
        $order = new Order(Uuid::fromString($id), OrderProvider::CUSTOMER_ID, OrderProvider::TENANT_ID);
        $this->denyAccessUnlessGranted('order.view', $order);

        return new JsonResponse(['id' => $order->getId()->toRfc4122()]);
    }
}

