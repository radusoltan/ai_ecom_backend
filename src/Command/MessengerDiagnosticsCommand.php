<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Transport\AmqpExt\Connection;

#[AsCommand(name: 'app:messenger:diagnostics', description: 'Check AMQP connectivity')]
final class MessengerDiagnosticsCommand extends Command
{
    public function __construct(
        #[Autowire('%env(MESSENGER_TRANSPORT_DSN)%')]
        private string $dsn
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $connection = Connection::fromDsn($this->dsn, true);
            $connection->connect();
            $connection->disconnect();
            $output->writeln('<info>AMQP connection OK</info>');
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');
            return Command::FAILURE;
        }
    }
}
