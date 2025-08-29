<?php

declare(strict_types=1);

namespace App\Infrastructure\API\Pagination;

use function base64_decode;
use function base64_encode;
use function is_array;
use function json_decode;
use function json_encode;
use function rtrim;
use function strtr;
use function trim;

final class CursorEncoder
{
    public function encode(Cursor $cursor): string
    {
        $payload = [
            'createdAt' => $cursor->createdAt->format(DATE_ATOM),
            'id' => $cursor->id,
        ];
        $json = json_encode($payload);
        $b64 = base64_encode($json);
        return rtrim(strtr($b64, '+/', '-_'), '=');
    }

    public function decode(string $encoded): Cursor
    {
        $b64 = strtr($encoded, '-_', '+/');
        $decoded = base64_decode($b64, true);
        if (false === $decoded) {
            throw new InvalidCursorException('Malformed base64 cursor.');
        }
        $data = json_decode($decoded, true);
        if (!is_array($data) || !isset($data['createdAt'], $data['id'])) {
            throw new InvalidCursorException('Invalid cursor payload.');
        }
        try {
            $createdAt = new \DateTimeImmutable($data['createdAt']);
        } catch (\Exception $e) {
            throw new InvalidCursorException('Invalid cursor timestamp.', 0, $e);
        }
        if (!is_string($data['id']) || '' === trim($data['id'])) {
            throw new InvalidCursorException('Invalid cursor id.');
        }

        return new Cursor($createdAt, $data['id']);
    }
}
