<?php

declare(strict_types=1);

namespace App\Shared\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\OpenApi;
use ApiPlatform\OpenApi\Model;
use ArrayObject;

final class EnvelopedOpenApiFactory implements OpenApiFactoryInterface
{
    public function __construct(private OpenApiFactoryInterface $decorated)
    {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);
        $components = $openApi->getComponents();
        $schemas = $components->getSchemas() ?: new ArrayObject();

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
                            'code' => ['type' => 'integer'],
                            'message' => ['type' => 'string'],
                            'field' => ['type' => 'string', 'nullable' => true],
                            'details' => ['type' => 'string', 'nullable' => true],
                        ],
                    ],
                ],
            ],
        ];

        $components = $components->withSchemas($schemas);
        $openApi = $openApi->withComponents($components);

        foreach ($openApi->getPaths()->getPaths() as $pathItem) {
            foreach (Model\PathItem::$methods as $method) {
                $operation = $pathItem->{'get'.ucfirst(strtolower($method))}();
                if (!$operation) {
                    continue;
                }
                $responses = $operation->getResponses() ?? [];
                foreach ($responses as $status => $response) {
                    $content = $response->getContent();
                    if (!$content || !isset($content['application/json'])) {
                        continue;
                    }
                    $schema = $content['application/json']->getSchema();
                    $wrapped = new ArrayObject([
                        'allOf' => [
                            ['$ref' => '#/components/schemas/Envelope'],
                            ['properties' => ['data' => $schema]],
                        ],
                    ]);
                    $content['application/json'] = $content['application/json']->withSchema($wrapped);
                }
            }
        }

        return $openApi;
    }
}
