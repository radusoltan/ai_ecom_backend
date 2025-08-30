<?php

declare(strict_types=1);

namespace App\Infrastructure\EventStore;

use App\Shared\Event\DomainEventInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\Uid\Uuid;

final class EventStoreRepository
{
    public function __construct(private Connection $connection)
    {
    }

    public function append(DomainEventInterface $event, string $aggregateType, ?int $expectedVersion, array $metadata = []): void
    {
        $this->connection->beginTransaction();
        try {
            $currentVersion = (int) $this->connection->fetchOne(
                'SELECT MAX(version) FROM event_store WHERE aggregate_id = :id',
                ['id' => $event->getAggregateId()]
            );

            if ($expectedVersion !== null && $currentVersion !== $expectedVersion) {
                throw new EventStoreConcurrencyException('Concurrency conflict for aggregate '.$event->getAggregateId());
            }

            $version = $currentVersion + 1;
            $payload = json_encode($event->getPayload(), JSON_THROW_ON_ERROR);
            $meta = json_encode(array_merge($event->getMetadata(), $metadata), JSON_THROW_ON_ERROR);

            try {
                $this->connection->insert('event_store', [
                    'id' => (string) Uuid::v4(),
                    'tenant_id' => $event->getTenantId(),
                    'aggregate_type' => $aggregateType,
                    'aggregate_id' => $event->getAggregateId(),
                    'version' => $version,
                    'event_name' => $event->getEventName(),
                    'payload' => $payload,
                    'metadata' => $meta,
                    'occurred_at' => $event->getOccurredAt()->format(DATE_ATOM),
                ]);
            } catch (UniqueConstraintViolationException $e) {
                throw new EventStoreConcurrencyException('Concurrency conflict for aggregate '.$event->getAggregateId(), 0, $e);
            }

            $this->connection->commit();
        } catch (\Throwable $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    /**
     * @return iterable<int, array<string,mixed>>
     */
    public function loadStream(string $aggregateId): iterable
    {
        $stmt = $this->connection->executeQuery(
            'SELECT * FROM event_store WHERE aggregate_id = :id ORDER BY version ASC',
            ['id' => $aggregateId]
        );

        while ($row = $stmt->fetchAssociative()) {
            $row['payload'] = json_decode($row['payload'], true, 512, JSON_THROW_ON_ERROR);
            $row['metadata'] = json_decode($row['metadata'], true, 512, JSON_THROW_ON_ERROR);
            yield $row;
        }
    }
}

