<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Voter;

use App\Domain\Order\Order;
use App\Infrastructure\Security\Expression\ExpressionLanguageFactory;
use App\Infrastructure\Security\PolicyEvaluator;
use App\Infrastructure\Security\Voter\OrderVoter;
use App\Shared\Feature\FeatureFlagService;
use App\Shared\Tenant\TenantContext;
use App\Shared\Tenant\TenantId;
use App\Infrastructure\Security\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Uid\Uuid;

final class OrderVoterTest extends TestCase
{
    private function createEvaluator(array $roles, TenantContext $tenantContext): PolicyEvaluator
    {
        $auth = new class($roles) implements AuthorizationCheckerInterface {
            public function __construct(private array $roles) {}
            public function isGranted($attribute, $subject = null): bool
            {
                return in_array($attribute, $this->roles, true);
            }
        };
        $flags = new FeatureFlagService();
        $flags->set(Uuid::fromString($tenantContext->get()->toString()), 'catalog_write', true);

        return new PolicyEvaluator([
            'order.view' => "is_granted('ROLE_MANAGER') or (user.id == subject.customerId)",
            'product.write' => "is_granted('ROLE_MANAGER') and tenant(feature('catalog_write'))",
        ], $auth, $tenantContext, $flags, new ExpressionLanguageFactory());
    }

    public function testManagerCanViewAnyOrder(): void
    {
        $tenantId = new TenantId(Uuid::v4()->toRfc4122());
        $tenantContext = new TenantContext($tenantId);
        $evaluator = $this->createEvaluator(['ROLE_MANAGER'], $tenantContext);
        $voter = new OrderVoter($evaluator, $tenantContext);

        $order = new Order(Uuid::v4(), 'cust', $tenantId->toString());
        $user = new User('manager');
        $token = $this->createConfiguredMock(TokenInterface::class, ['getUser' => $user]);
        $result = $voter->vote($token, $order, ['order.view']);
        self::assertSame(OrderVoter::ACCESS_GRANTED, $result);
    }

    public function testCustomerCanViewOwnOrderOnly(): void
    {
        $tenantId = new TenantId(Uuid::v4()->toRfc4122());
        $tenantContext = new TenantContext($tenantId);
        $evaluator = $this->createEvaluator([], $tenantContext);
        $voter = new OrderVoter($evaluator, $tenantContext);

        $order = new Order(Uuid::v4(), 'customer1', $tenantId->toString());
        $user = new User('customer1');
        $token = $this->createConfiguredMock(TokenInterface::class, ['getUser' => $user]);
        self::assertSame(OrderVoter::ACCESS_GRANTED, $voter->vote($token, $order, ['order.view']));

        $otherUser = new User('customer2');
        $token2 = $this->createConfiguredMock(TokenInterface::class, ['getUser' => $otherUser]);
        self::assertSame(OrderVoter::ACCESS_DENIED, $voter->vote($token2, $order, ['order.view']));
    }

    public function testCrossTenantDenied(): void
    {
        $tenantId = new TenantId(Uuid::v4()->toRfc4122());
        $tenantContext = new TenantContext($tenantId);
        $evaluator = $this->createEvaluator(['ROLE_MANAGER'], $tenantContext);
        $voter = new OrderVoter($evaluator, $tenantContext);

        $order = new Order(Uuid::v4(), 'cust', Uuid::v4()->toRfc4122());
        $user = new User('manager');
        $token = $this->createConfiguredMock(TokenInterface::class, ['getUser' => $user]);
        self::assertSame(OrderVoter::ACCESS_DENIED, $voter->vote($token, $order, ['order.view']));
    }
}

