<?php

declare(strict_types=1);

namespace App\Infrastructure\Monitoring;

use Monolog\LogRecord;

/**
 * Adds a request identifier to log records.
 */
class RequestIdProcessor
{
    public function __invoke(LogRecord $record): LogRecord
    {
        $record->extra['request_id'] = $record->extra['request_id'] ?? '';

        return $record;
    }
}
