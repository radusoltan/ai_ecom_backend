<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Shared\Tenant\TenantContextInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent;

final class SetTenantRlsSubscriber
{
    public function __construct(private EntityManagerInterface $em, private TenantContextInterface $tenantContext)
    {
    }

    #[AsEventListener(event: RequestEvent::class)]
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $tenantId = $this->tenantContext->getTenantId();
        $this->em->getConnection()->executeStatement('SET app.tenant_id = :tenant', ['tenant' => $tenantId]);
    }

    #[AsEventListener(event: WorkerMessageReceivedEvent::class)]
    public function onWorkerMessage(WorkerMessageReceivedEvent $event): void
    {
        $tenantId = $this->tenantContext->getTenantId();
        $this->em->getConnection()->executeStatement('SET app.tenant_id = :tenant', ['tenant' => $tenantId]);
    }
}

