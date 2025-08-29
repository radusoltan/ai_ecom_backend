<?php

namespace App\Shared\Tenant;

use Symfony\Component\HttpFoundation\Request;

final class TenantResolver
{
    public function __construct(
        private TenantRepository $tenants,
        private ApiKeyRepository $apiKeys,
        private JwtDecoder $jwt
    ) {
    }

    public function resolve(Request $req): ?string
    {
        // 1) JWT claim
        if ($jwt = $req->headers->get('Authorization')) {
            $claims = $this->jwt->decodeFromHeader($jwt);
            if (!empty($claims['tenant_id'])) {
                return (string) $claims['tenant_id'];
            }
        }

        // 2) custom domain
        $host = $req->getHost();
        if ($tenant = $this->tenants->findByCustomDomain($host)) {
            return (string) $tenant->getId();
        }

        // 3) subdomain
        if (preg_match('/^(?<slug>[^.]+)\./', $host, $m)) {
            if ($tenant = $this->tenants->findBySlug($m['slug'])) {
                return (string) $tenant->getId();
            }
        }

        // 4) X-API-Key
        if ($key = $req->headers->get('X-API-Key')) {
            if ($tenantId = $this->apiKeys->tenantIdForKey($key)) {
                return $tenantId;
            }
        }

        return null;
    }
}
