<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use App\Infrastructure\EventStore\Projection\ProjectorInterface;

#[AsCommand(name: 'events:replay')]
final class ReplayEventsCommand extends Command
{
    /** @param iterable<string,ProjectorInterface> $projectors */
    public function __construct(private Connection $connection, private iterable $projectors)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('projector', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Projector key')
            ->addOption('from', null, InputOption::VALUE_REQUIRED, 'Replay events from date', '1970-01-01T00:00:00Z');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $projectorKeys = $input->getOption('projector');
        $from = new \DateTimeImmutable($input->getOption('from'));

        $projectors = [];
        foreach ($this->projectors as $key => $projector) {
            if (!$projectorKeys || in_array($key, $projectorKeys, true)) {
                $projectors[$key] = $projector;
            }
        }

        $stmt = $this->connection->executeQuery(
            'SELECT * FROM event_store WHERE occurred_at >= :from ORDER BY occurred_at ASC',
            ['from' => $from->format(DATE_ATOM)]
        );

        while ($row = $stmt->fetchAssociative()) {
            $row['payload'] = json_decode($row['payload'], true, 512, JSON_THROW_ON_ERROR);
            $row['metadata'] = json_decode($row['metadata'], true, 512, JSON_THROW_ON_ERROR);
            foreach ($projectors as $projector) {
                if ($projector->supports($row['event_name'])) {
                    $projector->project($row);
                }
            }
        }

        return Command::SUCCESS;
    }
}

