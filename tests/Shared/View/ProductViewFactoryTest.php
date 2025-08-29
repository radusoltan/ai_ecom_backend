<?php

declare(strict_types=1);

namespace App\Tests\Shared\View;

use App\Domain\Catalog\Product;
use App\Shared\Tenant\TenantId;
use App\Shared\View\ProductViewFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class ProductViewFactoryTest extends TestCase
{
    public function testMapsCoreFields(): void
    {
        $product = new Product(
            Uuid::v4(),
            'sample-slug',
            'Sample name',
            1999,
            'USD',
        );

        $factory = new ProductViewFactory();
        $tenantId = new TenantId(Uuid::v4()->toRfc4122());
        $view = $factory->fromEntity($product, $tenantId);

        self::assertSame($product->getId()->toRfc4122(), $view->id);
        self::assertSame('sample-slug', $view->slug);
        self::assertSame('Sample name', $view->name);
        self::assertSame(1999, $view->priceCents);
        self::assertSame('USD', $view->currency);
    }
}
