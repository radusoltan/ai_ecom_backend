<?php

namespace App\Infrastructure\API;

use App\Shared\Tenant\TenantContext;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class ProbeController
{
    public function __construct(private TenantContext $context)
    {
    }

    #[Route('/probe', name: 'tenant_probe')]
    public function __invoke(): JsonResponse
    {
        $this->context->get();

        return new JsonResponse([
            'status' => 'ok',
            'data' => null,
            'meta' => [],
            'errors' => [],
        ]);
    }
}
