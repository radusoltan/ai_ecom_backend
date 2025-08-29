<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\API\GraphQL;

use App\Domain\Catalog\Product;
use App\Infrastructure\API\GraphQL\Resolver\ProductResolver;
use App\Shared\Context\Context;
use App\Shared\Tenant\TenantId;
use App\Shared\View\ProductViewFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;

final class ProductResolverTest extends KernelTestCase
{
    public function testReturnsProductView(): void
    {
        self::bootKernel();
        $resolver = new ProductResolver(new ProductViewFactory());

        $product = new Product(
            Uuid::v4(),
            'resolved-slug',
            'Resolved name',
            2500,
            'USD',
        );

        $context = new class(new TenantId(Uuid::v4()->toRfc4122())) implements Context {
            public function __construct(private TenantId $tenantId)
            {
            }

            public function tenant(): TenantId
            {
                return $this->tenantId;
            }
        };

        $view = $resolver($product, $context);

        self::assertSame($product->getId()->toRfc4122(), $view->id);
        self::assertSame('resolved-slug', $view->slug);
    }
}
