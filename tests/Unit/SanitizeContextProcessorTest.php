<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Shared\Logging\SanitizeContextProcessor;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;

final class SanitizeContextProcessorTest extends TestCase
{
    public function testRedactsSensitiveKeys(): void
    {
        $record = new LogRecord(new \DateTimeImmutable(), 'test', Level::Info, 'msg', [
            'password' => 'secret',
            'nested' => ['token' => 'abc'],
        ], [
            'authorization' => 'Bearer',
            'keep' => 'ok',
        ]);

        $processor = new SanitizeContextProcessor();
        $processed = $processor($record);

        self::assertSame('[REDACTED]', $processed->context['password']);
        self::assertSame('[REDACTED]', $processed->context['nested']['token']);
        self::assertSame('[REDACTED]', $processed->extra['authorization']);
        self::assertSame('ok', $processed->extra['keep']);
    }
}
