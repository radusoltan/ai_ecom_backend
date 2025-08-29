<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\EventSubscriber;

use App\Shared\Tenant\TenantContext;
use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Event\ConnectionEventArgs;
use Doctrine\DBAL\Events;

final class TenantBootstrapSubscriber implements EventSubscriber
{
    public function __construct(private TenantContext $context)
    {
    }

    public function postConnect(ConnectionEventArgs $args): void
    {
        if (!$this->context->has()) {
            return;
        }

        $args->getConnection()->executeStatement(
            'SET app.tenant_id = :tenant',
            ['tenant' => $this->context->get()->toString()]
        );
    }

    public function getSubscribedEvents(): array
    {
        return [Events::postConnect];
    }
}
