<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Shared\Tenant\ApiKeyRepository;
use App\Shared\Tenant\JwtDecoder;
use App\Shared\Tenant\TenantContext;
use App\Shared\Tenant\TenantId;
use App\Shared\Tenant\TenantNotFoundException;
use App\Shared\Tenant\TenantRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class TenantContextResolver
{
    public function __construct(
        private TenantRepository $tenants,
        private ApiKeyRepository $apiKeys,
        private JwtDecoder $jwt,
        private RequestStack $requests,
        private TenantContext $context,
    ) {
    }

    /**
     * Resolve the current tenant or throw if none could be determined.
     */
    public function resolveOrFail(): TenantId
    {
        if ($this->context->has()) {
            return $this->context->get();
        }

        $req = $this->requests->getCurrentRequest();
        if (!$req instanceof Request) {
            throw new TenantNotFoundException('tenant_not_found');
        }

        if ($jwt = $req->headers->get('Authorization')) {
            $claims = $this->jwt->decodeFromHeader($jwt);
            if (!empty($claims['tenant_id'])) {
                $tenantId = new TenantId((string) $claims['tenant_id']);
                $this->context->set($tenantId);
                return $tenantId;
            }
        }

        $host = $req->getHost();
        if ($tenant = $this->tenants->findByCustomDomain($host)) {
            $tenantId = new TenantId((string) $tenant->getId());
            $this->context->set($tenantId);
            return $tenantId;
        }

        if (preg_match('/^(?<slug>[^.]+)\./', $host, $m)) {
            if ($tenant = $this->tenants->findBySlug($m['slug'])) {
                $tenantId = new TenantId((string) $tenant->getId());
                $this->context->set($tenantId);
                return $tenantId;
            }
        }

        if ($key = $req->headers->get('X-API-Key')) {
            if ($tenantId = $this->apiKeys->tenantIdForKey($key)) {
                $id = new TenantId($tenantId);
                $this->context->set($id);
                return $id;
            }
        }

        throw new TenantNotFoundException('tenant_not_found');
    }
}
