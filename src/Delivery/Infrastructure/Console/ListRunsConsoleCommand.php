<?php

declare(strict_types=1);

namespace App\Delivery\Infrastructure\Console;

use App\Delivery\Domain\Repository\RunRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:delivery:runs', description: 'Liste les courses proposées aux coursiers')]
final class ListRunsConsoleCommand
{
    public function __construct(private readonly RunRepository $runs)
    {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $runs = $this->runs->all();

        if ([] === $runs) {
            $io->writeln('Aucune course.');

            return Command::SUCCESS;
        }

        foreach ($runs as $run) {
            $io->writeln(sprintf(
                'Course %s : commande(s) %s, à livrer avant %s, coursier : %s',
                $run->id(),
                implode(', ', $run->orders()),
                $run->deliverBy()->format('Y-m-d H:i'),
                $run->courierId() ?? 'à attribuer',
            ));
        }

        return Command::SUCCESS;
    }
}
