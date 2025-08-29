<?php

namespace App\Infrastructure\Http;

use App\Shared\Tenant\TenantContext;
use App\Shared\Tenant\TenantResolver;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 256)]
final class TenantContextListener
{
    public function __construct(
        private TenantResolver $resolver,
        private TenantContext $context,
        private EntityManagerInterface $em,
        private Connection $db,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if ($this->context->has()) {
            $tenantId = $this->context->get();
        } else {
            $tenantId = $this->resolver->resolve($request);
            if (!$tenantId) {
                throw new \DomainException('TenantNotFound');
            }
            $this->context->set($tenantId);
            $request->attributes->set('tenant_id', $tenantId);
        }

        $this->db->executeStatement('SET app.tenant_id = :tenant', ['tenant' => $tenantId]);
        $this->em->getFilters()->enable('tenant')->setParameter('tenant_id', $tenantId);

        $this->logger->info('Resolved tenant', ['tenant_id' => $tenantId]);
    }
}
