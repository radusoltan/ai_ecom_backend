<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\EventStore;

use App\Domain\Order\Event\OrderCreatedEvent;
use App\Infrastructure\EventStore\EventStoreConcurrencyException;
use App\Infrastructure\EventStore\EventStoreRepository;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;

final class EventStoreRepositoryTest extends TestCase
{
    private EventStoreRepository $repository;

    protected function setUp(): void
    {
        $connection = DriverManager::getConnection(['url' => 'sqlite:///:memory:']);
        $connection->executeStatement('CREATE TABLE event_store (id VARCHAR(36) PRIMARY KEY, tenant_id VARCHAR(36) NOT NULL, aggregate_type VARCHAR(100) NOT NULL, aggregate_id VARCHAR(36) NOT NULL, version INT NOT NULL, event_name VARCHAR(100) NOT NULL, payload TEXT NOT NULL, metadata TEXT NOT NULL, occurred_at TEXT NOT NULL, recorded_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)');
        $connection->executeStatement('CREATE UNIQUE INDEX ux_event_store_aggregate_version ON event_store(aggregate_id, version)');
        $this->repository = new EventStoreRepository($connection);
    }

    public function testAppendIncrementsVersion(): void
    {
        $event1 = new OrderCreatedEvent('a1', 't1', 1, 'USD', 100);
        $this->repository->append($event1, 'order', null);

        $event2 = new OrderCreatedEvent('a1', 't1', 1, 'USD', 100);
        $this->repository->append($event2, 'order', 1);

        $rows = iterator_to_array($this->repository->loadStream('a1'));
        self::assertCount(2, $rows);
        self::assertSame(1, $rows[0]['version']);
        self::assertSame(2, $rows[1]['version']);
    }

    public function testAppendWithStaleVersionThrows(): void
    {
        $event1 = new OrderCreatedEvent('a1', 't1', 1, 'USD', 100);
        $this->repository->append($event1, 'order', null);

        $event2 = new OrderCreatedEvent('a1', 't1', 1, 'USD', 100);
        $this->repository->append($event2, 'order', 1);

        $this->expectException(EventStoreConcurrencyException::class);
        $event3 = new OrderCreatedEvent('a1', 't1', 1, 'USD', 100);
        $this->repository->append($event3, 'order', 1);
    }
}

