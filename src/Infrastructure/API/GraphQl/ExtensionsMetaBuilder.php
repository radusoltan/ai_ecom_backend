<?php

declare(strict_types=1);

namespace App\Infrastructure\API\GraphQl;

use App\Shared\Http\RequestMetaFactory;

if (interface_exists(\ApiPlatform\GraphQl\Serializer\SerializerContextBuilderInterface::class)) {
    final class ExtensionsMetaBuilder implements \ApiPlatform\GraphQl\Serializer\SerializerContextBuilderInterface
    {
        public function __construct(
            private \ApiPlatform\GraphQl\Serializer\SerializerContextBuilderInterface $decorated,
            private RequestMetaFactory $metaFactory,
        ) {
        }

        public function create(array $attributes = [], bool $normalization = true, ?string $operationName = null): array
        {
            $context = $this->decorated->create($attributes, $normalization, $operationName);
            $context['extensions']['meta'] = $this->metaFactory->fromCurrentRequest();

            return $context;
        }
    }
}

