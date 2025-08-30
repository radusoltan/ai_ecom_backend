<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Application\Command\PingCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Transport\InMemoryTransport;
use Symfony\Component\Messenger\Worker;
use Symfony\Component\Uid\Uuid;

#[CoversClass(PingCommand::class)]
final class PingCommandHandlerTest extends KernelTestCase
{
    public function testPingIsHandled(): void
    {
        putenv('MESSENGER_TRANSPORT_DSN=in-memory://');
        self::bootKernel();

        $bus = self::getContainer()->get(MessageBusInterface::class);
        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.amqp_async');

        $command = new PingCommand(Uuid::v4()->toRfc4122(), new \DateTimeImmutable(), ['foo' => 'bar']);
        $bus->dispatch($command);

        $worker = new Worker(['amqp_async' => $transport], $bus);
        $worker->run(['sleep' => 0, 'stopWhenEmpty' => true]);

        self::assertCount(1, $transport->getAcknowledged());
    }
}
