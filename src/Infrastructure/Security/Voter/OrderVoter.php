<?php

declare(strict_types=1);

namespace App\Infrastructure\Security\Voter;

use App\Domain\Order\Order;
use App\Infrastructure\Security\PolicyEvaluator;
use App\Shared\Tenant\TenantContext;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class OrderVoter extends Voter
{
    public const VIEW = 'order.view';

    public function __construct(
        private PolicyEvaluator $evaluator,
        private TenantContext $tenantContext,
    ) {
    }

    protected function supports(string $attribute, $subject): bool
    {
        return self::VIEW === $attribute && $subject instanceof Order;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        if (!$subject instanceof Order) {
            return false;
        }

        if ($this->tenantContext->has() && $subject->tenantId !== $this->tenantContext->get()->toString()) {
            return false;
        }

        $user = $token->getUser();
        if (!is_object($user)) {
            return false;
        }

        return $this->evaluator->decide(self::VIEW, $user, $subject);
    }
}

