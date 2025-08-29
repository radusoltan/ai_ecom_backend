<?php

declare(strict_types=1);

namespace App\Infrastructure\API;

use App\Shared\Tenant\TenantContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\Uuid;

final class RequestMetaFactory
{
    public function __construct(
        private RequestStack $requests,
        private TenantContext $tenantContext
    ) {}

    public function fromRequest(Request $request): array
    {
        return $this->build($request);
    }

    public function fromCurrentRequest(): array
    {
        $request = $this->requests->getCurrentRequest();
        if (!$request instanceof Request) {
            $request = new Request();
        }
        return $this->build($request);
    }

    private function build(Request $request): array
    {
        $meta = [
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'request_id' => $request->headers->get('X-Request-Id') ?? Uuid::v4()->toRfc4122(),
            'tenant_id' => $this->tenantContext->has() ? $this->tenantContext->get()->toString() : null,
        ];

        if ($pagination = $request->attributes->get('pagination')) {
            $meta['pagination'] = $pagination;
        }

        return $meta;
    }
}
