<?php

declare(strict_types=1);

namespace App\Infrastructure\Bus\Serializer;

use App\Shared\Event\DomainEventInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class DomainEventNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof DomainEventInterface;
    }

    /**
     * @param DomainEventInterface $object
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        return [
            'event_name' => $object->getEventName(),
            'aggregate_id' => $object->getAggregateId(),
            'tenant_id' => $object->getTenantId(),
            'payload' => $object->getPayload(),
            'metadata' => $object->getMetadata(),
            'occurred_at' => $object->getOccurredAt()->format(DATE_ATOM),
        ];
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return is_subclass_of($type, DomainEventInterface::class);
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): DomainEventInterface
    {
        $ref = new \ReflectionClass($type);
        $ctor = $ref->getConstructor();
        $args = [];
        foreach ($ctor->getParameters() as $param) {
            $name = $param->getName();
            if ($name === 'aggregateId') {
                $args[] = $data['aggregate_id'];
            } elseif ($name === 'tenantId') {
                $args[] = $data['tenant_id'];
            } elseif ($name === 'metadata') {
                $args[] = $data['metadata'] ?? [];
            } elseif ($name === 'occurredAt') {
                $args[] = new \DateTimeImmutable($data['occurred_at']);
            } else {
                $key = self::camelToSnake($name);
                $args[] = $data['payload'][$key] ?? null;
            }
        }

        return $ref->newInstanceArgs($args);
    }

    private static function camelToSnake(string $value): string
    {
        return strtolower(preg_replace('/[A-Z]/', '_$0', lcfirst($value)) ?? $value);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [DomainEventInterface::class => true];
    }
}

