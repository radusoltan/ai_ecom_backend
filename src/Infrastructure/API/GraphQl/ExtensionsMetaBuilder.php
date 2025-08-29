<?php
declare(strict_types=1);

namespace App\Infrastructure\API\GraphQl;

use ApiPlatform\Metadata\GraphQl\Operation;
use App\Shared\Http\RequestMetaFactory;

if (interface_exists(\ApiPlatform\GraphQl\Serializer\SerializerContextBuilderInterface::class)) {
    final class ExtensionsMetaBuilder implements \ApiPlatform\GraphQl\Serializer\SerializerContextBuilderInterface
    {
        public function __construct(
            private \ApiPlatform\GraphQl\Serializer\SerializerContextBuilderInterface $decorated,
            private RequestMetaFactory $metaFactory,
        ) {
        }

        public function create(
            string $resourceClass,
            Operation $operation,
            array $resolverContext,
            bool $normalization
        ): array {
            $context = $this->decorated->create($resourceClass, $operation, $resolverContext, $normalization);

            // Adaugă meta informații din request-ul curent
            $context['extensions']['meta'] = $this->metaFactory->fromCurrentRequest();

            return $context;
        }
    }
}
