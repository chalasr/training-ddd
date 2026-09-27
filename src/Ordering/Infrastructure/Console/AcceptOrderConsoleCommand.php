<?php

declare(strict_types=1);

namespace App\Ordering\Infrastructure\Console;

use App\Ordering\Application\Command\AcceptOrderCommand;
use App\Shared\Application\Command\CommandBusInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:order:accept', description: 'Le restaurant accepte une commande')]
final class AcceptOrderConsoleCommand
{
    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument('Identifiant de la commande', name: 'order')] string $orderId,
    ): int {
        try {
            $this->commandBus->dispatch(new AcceptOrderCommand($orderId));
        } catch (\DomainException|\InvalidArgumentException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $io->success(sprintf('Commande %s acceptée.', $orderId));

        return Command::SUCCESS;
    }
}
