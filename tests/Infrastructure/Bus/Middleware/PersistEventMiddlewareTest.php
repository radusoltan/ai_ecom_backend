<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Bus\Middleware;

use App\Domain\Order\Event\OrderCreatedEvent;
use App\Infrastructure\Bus\Middleware\PersistEventMiddleware;
use App\Infrastructure\EventStore\EventStoreRepository;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

final class PersistEventMiddlewareTest extends TestCase
{
    public function testEventIsPersistedAndMessagePassedAlong(): void
    {
        $connection = DriverManager::getConnection(['url' => 'sqlite:///:memory:']);
        $connection->executeStatement('CREATE TABLE event_store (id VARCHAR(36) PRIMARY KEY, tenant_id VARCHAR(36) NOT NULL, aggregate_type VARCHAR(100) NOT NULL, aggregate_id VARCHAR(36) NOT NULL, version INT NOT NULL, event_name VARCHAR(100) NOT NULL, payload TEXT NOT NULL, metadata TEXT NOT NULL, occurred_at TEXT NOT NULL, recorded_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)');
        $connection->executeStatement('CREATE UNIQUE INDEX ux_event_store_aggregate_version ON event_store(aggregate_id, version)');
        $repo = new EventStoreRepository($connection);

        $middleware = new PersistEventMiddleware($repo);

        $event = new OrderCreatedEvent('a1', 't1', 1, 'USD', 100);
        $envelope = new Envelope($event);

        $stack = new class implements StackInterface {
            public bool $called = false;
            public function next(): MiddlewareInterface
            {
                return new class($this) implements MiddlewareInterface {
                    public function __construct(private $outer) {}
                    public function handle(Envelope $envelope, StackInterface $stack): Envelope
                    {
                        $this->outer->called = true;
                        return $envelope;
                    }
                };
            }
        };

        $middleware->handle($envelope, $stack);

        $count = (int) $connection->fetchOne('SELECT COUNT(*) FROM event_store');
        self::assertSame(1, $count);
        self::assertTrue($stack->called);
    }
}

