<?php

declare(strict_types=1);

namespace App\Infrastructure\API\GraphQl;

use ApiPlatform\GraphQl\Serializer\SerializerContextBuilderInterface;
use App\Infrastructure\API\RequestMetaFactory;

final class ExtensionsMetaBuilder implements SerializerContextBuilderInterface
{
    public function __construct(
        private SerializerContextBuilderInterface $decorated,
        private RequestMetaFactory $metaFactory
    ) {
    }

    public function create(array $attributes = [], bool $normalization = true, ?string $operationName = null): array
    {
        $context = $this->decorated->create($attributes, $normalization, $operationName);
        $context['extensions']['meta'] = $this->metaFactory->fromCurrentRequest();
        return $context;
    }
}
