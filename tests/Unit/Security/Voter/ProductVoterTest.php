<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Voter;

use App\Domain\Catalog\Product;
use App\Infrastructure\Security\Expression\ExpressionLanguageFactory;
use App\Infrastructure\Security\PolicyEvaluator;
use App\Infrastructure\Security\Voter\ProductVoter;
use App\Shared\Feature\FeatureFlagService;
use App\Shared\Tenant\TenantContext;
use App\Shared\Tenant\TenantId;
use App\Infrastructure\Security\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Uid\Uuid;

final class ProductVoterTest extends TestCase
{
    private function evaluator(array $roles, TenantContext $tenantContext, bool $feature): PolicyEvaluator
    {
        $auth = new class($roles) implements AuthorizationCheckerInterface {
            public function __construct(private array $roles) {}
            public function isGranted($attribute, $subject = null): bool
            {
                return in_array($attribute, $this->roles, true);
            }
        };
        $flags = new FeatureFlagService();
        $flags->set(Uuid::fromString($tenantContext->get()->toString()), 'catalog_write', $feature);

        return new PolicyEvaluator([
            'order.view' => "is_granted('ROLE_MANAGER') or (user.id == subject.customerId)",
            'product.write' => "is_granted('ROLE_MANAGER') and tenant(feature('catalog_write'))",
        ], $auth, $tenantContext, $flags, new ExpressionLanguageFactory());
    }

    public function testRequiresManagerAndFeature(): void
    {
        $tenantId = new TenantId(Uuid::v4()->toRfc4122());
        $tenantContext = new TenantContext($tenantId);
        $evaluator = $this->evaluator(['ROLE_MANAGER'], $tenantContext, true);
        $voter = new ProductVoter($evaluator, $tenantContext);
        $product = new Product(Uuid::v4(), 's', 'n', 10, 'USD', $tenantId->toString());
        $user = new User('manager');
        $token = $this->createConfiguredMock(TokenInterface::class, ['getUser' => $user]);
        self::assertSame(ProductVoter::ACCESS_GRANTED, $voter->vote($token, $product, ['product.write']));

        $evaluator2 = $this->evaluator(['ROLE_MANAGER'], $tenantContext, false);
        $voter2 = new ProductVoter($evaluator2, $tenantContext);
        self::assertSame(ProductVoter::ACCESS_DENIED, $voter2->vote($token, $product, ['product.write']));
    }

    public function testCrossTenantDenied(): void
    {
        $tenantId = new TenantId(Uuid::v4()->toRfc4122());
        $tenantContext = new TenantContext($tenantId);
        $evaluator = $this->evaluator(['ROLE_MANAGER'], $tenantContext, true);
        $voter = new ProductVoter($evaluator, $tenantContext);
        $product = new Product(Uuid::v4(), 's', 'n', 10, 'USD', Uuid::v4()->toRfc4122());
        $user = new User('manager');
        $token = $this->createConfiguredMock(TokenInterface::class, ['getUser' => $user]);
        self::assertSame(ProductVoter::ACCESS_DENIED, $voter->vote($token, $product, ['product.write']));
    }
}

