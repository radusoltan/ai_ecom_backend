<?php

declare(strict_types=1);

namespace App\Shared\Http;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\Uuid;

final class RequestMetaFactory
{
    public function __construct(private RequestStack $requests)
    {
    }

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
        $requestId = $request->attributes->get('request_id');
        if (!$requestId) {
            $requestId = Uuid::v4()->toRfc4122();
            $request->attributes->set('request_id', $requestId);
        }

        $meta = [
            'timestamp' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(\DateTimeInterface::ATOM),
            'request_id' => $requestId,
            'tenant_id' => $request->attributes->get('tenant_id'),
        ];

        if ($pagination = $request->attributes->get('pagination')) {
            $meta['pagination'] = $pagination;
        }

        return $meta;
    }
}
