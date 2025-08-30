<?php

declare(strict_types=1);

namespace App\Shared\Logging;

use App\Shared\Tenant\TenantContext;
use Monolog\LogRecord;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class RequestContextProcessor
{
    public function __construct(
        private RequestStack $requests,
        private TokenStorageInterface $tokenStorage,
        private TenantContext $tenantContext,
    ) {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $request = $this->requests->getCurrentRequest();
        $extra = [];

        if ($request instanceof Request) {
            $extra['request_id'] = $request->attributes->get('request_id');
            $extra['tenant_id'] = $request->attributes->get('tenant_id');
            $extra['http'] = [
                'method' => $request->getMethod(),
                'path' => $request->getPathInfo(),
                'ip' => $request->getClientIp(),
            ];
        } elseif ($this->tenantContext->has()) {
            $extra['tenant_id'] = $this->tenantContext->get()->toString();
        }

        $token = $this->tokenStorage->getToken();
        if ($token && $user = $token->getUser()) {
            if (\is_object($user)) {
                if (method_exists($user, 'getId')) {
                    $extra['user_id'] = (string) $user->getId();
                } elseif (method_exists($user, 'getUserIdentifier')) {
                    $extra['user_id'] = (string) $user->getUserIdentifier();
                }
            } elseif (\is_string($user)) {
                $extra['user_id'] = $user;
            }
        }

        return $record->with(extra: array_merge($extra, $record->extra));
    }
}
