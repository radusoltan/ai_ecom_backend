<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Infrastructure\Bus\TenantIdStamp;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent;

#[AsEventListener(event: WorkerMessageReceivedEvent::class)]
final class SetTenantRlsWorkerSubscriber
{
    public function __construct(private Connection $db, private EntityManagerInterface $em) {}

    public function __invoke(WorkerMessageReceivedEvent $event): void
    {
        $stamp = $event->getEnvelope()->last(TenantIdStamp::class);
        if (!$stamp) {
            return;
        }

        $tenant = $stamp->getTenantId();
        $this->db->executeStatement('SET app.tenant_id = :tenant', ['tenant' => $tenant]);
        $this->em->getFilters()->enable('tenant_filter')->setParameter('tenant_id', $tenant);
    }
}
