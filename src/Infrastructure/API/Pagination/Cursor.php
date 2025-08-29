<?php

declare(strict_types=1);

namespace App\Infrastructure\API\Pagination;

use DateTimeImmutable;

final class Cursor
{
    public function __construct(
        public DateTimeImmutable $createdAt,
        public string $id,
    ) {
    }
}
