<?php

declare(strict_types=1);

namespace App\Infrastructure\Security\Voter;

use App\Domain\Catalog\Product;
use App\Infrastructure\Security\PolicyEvaluator;
use App\Shared\Tenant\TenantContext;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class ProductVoter extends Voter
{
    public const WRITE = 'product.write';

    public function __construct(
        private PolicyEvaluator $evaluator,
        private TenantContext $tenantContext,
    ) {
    }

    protected function supports(string $attribute, $subject): bool
    {
        return self::WRITE === $attribute && (null === $subject || $subject instanceof Product);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        if ($subject instanceof Product && $this->tenantContext->has() && $subject->tenantId !== $this->tenantContext->get()->toString()) {
            return false;
        }

        $user = $token->getUser();
        if (!is_object($user)) {
            return false;
        }

        return $this->evaluator->decide(self::WRITE, $user, $subject);
    }
}

