<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Infrastructure\Security\TenantContextResolver;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;

#[AsEventListener(event: 'kernel.request', priority: 32)]
final class SetTenantRlsSessionListener
{
    public function __construct(
        private Connection $db,
        private EntityManagerInterface $em,
        private TenantContextResolver $resolver,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $tenantId = $this->resolver->resolveOrFail();
        $tenant = $tenantId->toString();

        $this->db->executeStatement('SET app.tenant_id = :tenant', ['tenant' => $tenant]);
        $this->em->getFilters()->enable('tenant_filter')->setParameter('tenant_id', $tenant);

        $this->logger->info('Resolved tenant', ['tenant_id' => $tenant]);
    }
}
