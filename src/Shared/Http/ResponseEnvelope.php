<?php

declare(strict_types=1);

namespace App\Shared\Http;

final class ResponseEnvelope implements \JsonSerializable
{
    public function __construct(
        public string $status,
        public mixed $data,
        public array $meta,
        public array $errors = []
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'status' => $this->status,
            'data' => $this->data,
            'meta' => $this->meta,
            'errors' => $this->errors,
        ];
    }
}
