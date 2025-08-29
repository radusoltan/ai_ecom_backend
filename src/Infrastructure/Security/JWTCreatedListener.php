<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;

final class JWTCreatedListener
{
    public function onJWTCreated(JWTCreatedEvent $event): void
    {
        $payload = $event->getData();
        // TODO in D2: resolve tenant context and inject claim
        // $payload['tenant_id'] = 'TBD';
        $event->setData($payload);
    }
}

