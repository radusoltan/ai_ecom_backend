<?php

namespace App\Shared\Tenant;

final class NullJwtDecoder implements JwtDecoder
{
    public function decodeFromHeader(string $header): array
    {
        return [];
    }
}
