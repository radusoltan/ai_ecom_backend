<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Shared\Tenant\ApiKeyRepository;
use App\Shared\Tenant\JwtDecoder;
use App\Shared\Tenant\TenantId;
use App\Shared\Tenant\TenantNotFoundException;
use App\Shared\Tenant\TenantRepository;
use Symfony\Component\HttpFoundation\Request;

final class TenantResolver
{
    public function __construct(
        private TenantRepository $tenants,
        private ApiKeyRepository $apiKeys,
        private JwtDecoder $jwt,
    ) {
    }

    public function resolve(Request $req): TenantId
    {
        if ($jwt = $req->headers->get('Authorization')) {
            $claims = $this->jwt->decodeFromHeader($jwt);
            if (!empty($claims['tenant_id'])) {
                return new TenantId((string) $claims['tenant_id']);
            }
        }

        $host = $req->getHost();
        if ($tenant = $this->tenants->findByCustomDomain($host)) {
            return new TenantId((string) $tenant->getId());
        }

        if (preg_match('/^(?<slug>[^.]+)\./', $host, $m)) {
            if ($tenant = $this->tenants->findBySlug($m['slug'])) {
                return new TenantId((string) $tenant->getId());
            }
        }

        if ($key = $req->headers->get('X-API-Key')) {
            if ($tenantId = $this->apiKeys->tenantIdForKey($key)) {
                return new TenantId($tenantId);
            }
        }

        throw new TenantNotFoundException('tenant_not_found');
    }
}
