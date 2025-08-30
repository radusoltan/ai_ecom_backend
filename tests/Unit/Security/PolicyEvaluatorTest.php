<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Infrastructure\Security\Expression\ExpressionLanguageFactory;
use App\Infrastructure\Security\PolicyEvaluator;
use App\Shared\Feature\FeatureFlagService;
use App\Shared\Tenant\TenantContext;
use App\Shared\Tenant\TenantId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Uid\Uuid;

final class PolicyEvaluatorTest extends TestCase
{
    private function createEvaluator(array $roles, bool $featureEnabled): PolicyEvaluator
    {
        $auth = new class($roles) implements AuthorizationCheckerInterface {
            public function __construct(private array $roles) {}
            public function isGranted($attribute, $subject = null): bool
            {
                return in_array($attribute, $this->roles, true);
            }
        };

        $tenantId = new TenantId(Uuid::v4()->toRfc4122());
        $tenantContext = new TenantContext($tenantId);
        $flags = new FeatureFlagService();
        $flags->set(Uuid::fromString($tenantId->toString()), 'catalog_write', $featureEnabled);

        return new PolicyEvaluator(
            [
                'order.view' => "is_granted('ROLE_MANAGER') or (user.id == subject.customerId)",
                'product.write' => "is_granted('ROLE_MANAGER') and tenant(feature('catalog_write'))",
            ],
            $auth,
            $tenantContext,
            $flags,
            new ExpressionLanguageFactory(),
        );
    }

    public function testOrderViewPolicy(): void
    {
        $evaluator = $this->createEvaluator(['ROLE_MANAGER'], true);
        $user = (object) ['id' => 'u1'];
        $order = (object) ['customerId' => 'u2', 'tenantId' => 't'];
        self::assertTrue($evaluator->decide('order.view', $user, $order));

        $evaluator = $this->createEvaluator([], true);
        $user2 = (object) ['id' => 'cust'];
        $order2 = (object) ['customerId' => 'cust', 'tenantId' => 't'];
        self::assertTrue($evaluator->decide('order.view', $user2, $order2));
    }

    public function testProductWritePolicy(): void
    {
        $evaluator = $this->createEvaluator(['ROLE_MANAGER'], true);
        $user = (object) ['id' => 'manager'];
        self::assertTrue($evaluator->decide('product.write', $user, null));

        $evaluator = $this->createEvaluator(['ROLE_MANAGER'], false);
        self::assertFalse($evaluator->decide('product.write', $user, null));
    }
}

