<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Subscriber;

use App\Infrastructure\Security\TenantResolver;
use App\Shared\Tenant\TenantContext;
use App\Shared\Tenant\TenantNotFoundException;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 256)]
final class TenantRequestSubscriber
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

        try {
            $tenantId = $this->context->has() ? $this->context->get() : $this->resolver->resolve($request);
        } catch (TenantNotFoundException $e) {
            throw new AccessDeniedHttpException($e->getMessage(), $e);
        }

        if (!$this->context->has()) {
            $this->context->set($tenantId);
            $request->attributes->set('tenant_id', $tenantId->toString());
        }

        $this->db->executeStatement('SET app.tenant_id = :tenant', ['tenant' => $tenantId->toString()]);
        $this->em->getFilters()->enable('tenant')->setParameter('tenant_id', $tenantId->toString());

        $this->logger->info('Resolved tenant', ['tenant_id' => $tenantId->toString()]);
    }
}
