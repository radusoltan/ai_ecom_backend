<?php

declare(strict_types=1);

namespace App\Application\CommandHandler;

use App\Application\Command\PingCommand;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class PingCommandHandler
{
    public function __construct(
        #[Autowire(service: 'monolog.logger.messenger')]
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(PingCommand $command): void
    {
        $this->logger->info('ping received', [
            'message_id' => $command->messageId,
            'payload' => $command->payload,
        ]);
    }
}
