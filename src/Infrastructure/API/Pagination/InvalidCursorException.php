<?php

declare(strict_types=1);

namespace App\Infrastructure\API\Pagination;

use RuntimeException;

final class InvalidCursorException extends RuntimeException
{
}
