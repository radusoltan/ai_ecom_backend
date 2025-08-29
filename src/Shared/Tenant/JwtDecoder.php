<?php

namespace App\Shared\Tenant;

interface JwtDecoder
{
    /**
     * @param string $header Authorization header value
     *
     * @return array<string,mixed>
     */
    public function decodeFromHeader(string $header): array;
}
