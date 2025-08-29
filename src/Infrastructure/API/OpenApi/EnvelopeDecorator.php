<?php

declare(strict_types=1);

namespace App\Infrastructure\API\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\OpenApi;

final class EnvelopeDecorator implements OpenApiFactoryInterface
{
    public function __construct(private OpenApiFactoryInterface $decorated)
    {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);
        $schemas = $openApi->getComponents()->getSchemas();

        $schemas['Envelope'] = [
            'type' => 'object',
            'properties' => [
                'status' => ['type' => 'string'],
                'data' => ['type' => 'object', 'nullable' => true],
                'meta' => [
                    'type' => 'object',
                    'properties' => [
                        'timestamp' => ['type' => 'string', 'format' => 'date-time'],
                        'request_id' => ['type' => 'string'],
                        'tenant_id' => ['type' => 'string', 'nullable' => true],
                        'pagination' => [
                            'type' => 'object',
                            'nullable' => true,
                            'properties' => [
                                'cursor' => ['type' => 'string', 'nullable' => true],
                                'has_more' => ['type' => 'boolean', 'nullable' => true],
                                'total' => ['type' => 'integer', 'nullable' => true],
                            ],
                        ],
                    ],
                ],
                'errors' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'message' => ['type' => 'string'],
                            'code' => ['type' => 'integer'],
                        ],
                    ],
                ],
            ],
        ];

        $openApi->getComponents()->setSchemas($schemas);

        return $openApi;
    }
}
