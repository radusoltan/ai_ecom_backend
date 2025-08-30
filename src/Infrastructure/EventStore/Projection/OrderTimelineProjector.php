<?php

declare(strict_types=1);

namespace App\Infrastructure\EventStore\Projection;

use App\Domain\Order\Event\OrderCreatedEvent;
use Doctrine\DBAL\Connection;
use Symfony\Component\Uid\Uuid;

final class OrderTimelineProjector implements ProjectorInterface
{
    public function __construct(private Connection $connection)
    {
    }

    public function supports(string $eventName): bool
    {
        return $eventName === OrderCreatedEvent::EVENT_NAME;
    }

    public function project(array $eventRow): void
    {
        $payload = $eventRow['payload'];
        if (is_string($payload)) {
            $payload = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        }

        $this->connection->insert('order_event_timeline', [
            'id' => (string) Uuid::v4(),
            'order_id' => $eventRow['aggregate_id'],
            'event_name' => $eventRow['event_name'],
            'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
            'occurred_at' => $eventRow['occurred_at'],
        ]);
    }
}

