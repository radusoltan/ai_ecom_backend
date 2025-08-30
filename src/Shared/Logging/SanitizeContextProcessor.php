<?php

declare(strict_types=1);

namespace App\Shared\Logging;

use Monolog\LogRecord;

final class SanitizeContextProcessor
{
    private const SENSITIVE_KEYS = [
        'password',
        'token',
        'authorization',
        'cookie',
        'card',
        'cvv',
        'secret',
    ];

    public function __invoke(LogRecord $record): LogRecord
    {
        return $record->with(
            context: $this->sanitize($record->context),
            extra: $this->sanitize($record->extra),
        );
    }

    private function sanitize(array $data): array
    {
        foreach ($data as $key => $value) {
            if (\in_array(strtolower((string) $key), self::SENSITIVE_KEYS, true)) {
                $data[$key] = '[REDACTED]';
                continue;
            }

            if (\is_array($value)) {
                $data[$key] = $this->sanitize($value);
            }
        }

        return $data;
    }
}
