<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Infrastructure\Security\Expression\ExpressionLanguageFactory;
use App\Shared\Feature\FeatureFlagService;
use App\Shared\Tenant\TenantContext;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\ParsedExpression;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class PolicyEvaluator
{
    private array $compiled = [];

    public function __construct(
        array $policies,
        private AuthorizationCheckerInterface $authChecker,
        private TenantContext $tenantContext,
        private FeatureFlagService $featureFlags,
        ExpressionLanguageFactory $factory,
    ) {
        $language = $factory->create();
        foreach ($policies as $key => $expression) {
            $this->compiled[$key] = $language->parse($expression, ['user', 'subject', 'auth_checker', 'tenant_context', 'feature_flags']);
        }
        $this->language = $language;
    }

    public function decide(string $policy, ?object $user = null, ?object $subject = null): bool
    {
        if (!isset($this->compiled[$policy])) {
            throw new \InvalidArgumentException(sprintf('Unknown policy "%s"', $policy));
        }

        /** @var ParsedExpression $expr */
        $expr = $this->compiled[$policy];

        return (bool) $this->language->evaluate($expr, [
            'user' => $user,
            'subject' => $subject,
            'auth_checker' => $this->authChecker,
            'tenant_context' => $this->tenantContext,
            'feature_flags' => $this->featureFlags,
        ]);
    }

    private ExpressionLanguage $language;
}

