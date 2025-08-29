<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\API\Pagination;

use App\Infrastructure\API\Pagination\Cursor;
use App\Infrastructure\API\Pagination\CursorEncoder;
use App\Infrastructure\API\Pagination\InvalidCursorException;
use PHPUnit\Framework\TestCase;

final class CursorEncoderTest extends TestCase
{
    public function testEncodeDecode(): void
    {
        $cursor = new Cursor(new \DateTimeImmutable('2025-08-25T12:00:00Z'), 'abc-123');
        $encoder = new CursorEncoder();
        $encoded = $encoder->encode($cursor);
        $decoded = $encoder->decode($encoded);
        self::assertEquals($cursor->createdAt, $decoded->createdAt);
        self::assertSame($cursor->id, $decoded->id);
    }

    public function testInvalidCursor(): void
    {
        $this->expectException(InvalidCursorException::class);
        $encoder = new CursorEncoder();
        $encoder->decode('not-base64');
    }
}
