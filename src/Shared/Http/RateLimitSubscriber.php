<?php

declare(strict_types=1);

namespace App\Shared\Http;

use App\Shared\Tenant\ApiKeyRepository;
use App\Shared\Tenant\JwtDecoder;
use App\Shared\Tenant\TenantRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\RateLimit;

final class RateLimitSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private RateLimiterFactory $ipLimiter,
        private RateLimiterFactory $apiKeyLimiter,
        private JwtDecoder $jwt,
        private TenantRepository $tenants,
        private ApiKeyRepository $apiKeys,
        private RequestMetaFactory $metaFactory,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 200],
            KernelEvents::RESPONSE => ['onResponse', 0],
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!$this->isApiPath($request)) {
            return;
        }

        $tenant = $this->resolveTenant($request);
        $apiKey = $request->headers->get('X-API-Key');
        if ($apiKey) {
            $limiter = $this->apiKeyLimiter;
            $key = sprintf('%s:%s', $tenant ?? $request->getClientIp(), $apiKey);
        } else {
            $limiter = $this->ipLimiter;
            $ip = $request->getClientIp() ?? 'unknown';
            $key = sprintf('%s:%s', $tenant ?? $ip, $ip);
        }

        $rateLimit = $limiter->create($key)->consume(1);
        $request->attributes->set('_rate_limit', $rateLimit);

        if (!$rateLimit->isAccepted()) {
            $event->setResponse($this->limitExceededResponse($request, $rateLimit));
        }
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!$this->isApiPath($request)) {
            return;
        }

        /** @var RateLimit|null $rateLimit */
        $rateLimit = $request->attributes->get('_rate_limit');
        if (!$rateLimit) {
            return;
        }

        $headers = [
            'X-RateLimit-Limit' => $rateLimit->getLimit(),
            'X-RateLimit-Remaining' => $rateLimit->getRemainingTokens(),
        ];
        if ($rateLimit->getRetryAfter()) {
            $headers['Retry-After'] = max($rateLimit->getRetryAfter()->getTimestamp() - time(), 0);
        }
        $event->getResponse()->headers->add($headers);
    }

    private function limitExceededResponse(Request $request, RateLimit $rateLimit): JsonResponse
    {
        $headers = [
            'X-RateLimit-Limit' => $rateLimit->getLimit(),
            'X-RateLimit-Remaining' => $rateLimit->getRemainingTokens(),
        ];
        if ($rateLimit->getRetryAfter()) {
            $headers['Retry-After'] = max($rateLimit->getRetryAfter()->getTimestamp() - time(), 0);
        }

        $envelope = new ResponseEnvelope('error', null, $this->metaFactory->fromRequest($request), [
            ['code' => 429, 'message' => 'Too Many Requests'],
        ]);

        return new JsonResponse($envelope, 429, $headers);
    }

    private function resolveTenant(Request $request): ?string
    {
        if ($jwt = $request->headers->get('Authorization')) {
            $claims = $this->jwt->decodeFromHeader($jwt);
            if (!empty($claims['tenant_id'])) {
                return (string) $claims['tenant_id'];
            }
        }

        $host = $request->getHost();
        if ($tenant = $this->tenants->findByCustomDomain($host)) {
            return (string) $tenant->getId();
        }
        if (preg_match('/^(?<slug>[^.]+)\./', $host, $m)) {
            if ($tenant = $this->tenants->findBySlug($m['slug'])) {
                return (string) $tenant->getId();
            }
        }
        if ($key = $request->headers->get('X-API-Key')) {
            if ($tenantId = $this->apiKeys->tenantIdForKey($key)) {
                return $tenantId;
            }
        }

        return null;
    }

    private function isApiPath(Request $request): bool
    {
        $path = $request->getPathInfo();
        return str_starts_with($path, '/api') || str_starts_with($path, '/graphql');
    }
}
