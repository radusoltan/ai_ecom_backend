<?php

declare(strict_types=1);

namespace App\Tests\Contract;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\OpenApi\EnvelopedOpenApiFactory;
use PHPUnit\Framework\TestCase;
use ArrayObject;

final class OpenApiEnvelopeTest extends TestCase
{
    public function testEnvelopeSchemaWrapping(): void
    {
        $decorated = new class implements OpenApiFactoryInterface {
            public function __invoke(array $context = []): OpenApi
            {
                $schema = new ArrayObject(['type' => 'object']);
                $mediaType = new Model\MediaType($schema);
                $response = new Model\Response(description: 'OK', content: new ArrayObject(['application/json' => $mediaType]));
                $responses = ['200' => $response];
                $operation = new Model\Operation(responses: $responses);
                $pathItem = new Model\PathItem(get: $operation);
                $paths = new Model\Paths();
                $paths->addPath('/foo', $pathItem);
                return new OpenApi(new Model\Info('t', '1.0.0'), [], $paths);
            }
        };

        $factory = new EnvelopedOpenApiFactory($decorated);
        $openApi = ($factory)();
        $schemas = $openApi->getComponents()->getSchemas();
        self::assertArrayHasKey('Envelope', $schemas);
        $schema = $openApi->getPaths()->getPath('/foo')->getGet()->getResponses()['200']->getContent()['application/json']->getSchema();
        self::assertSame('#/components/schemas/Envelope', $schema['allOf'][0]['$ref']);
    }
}
