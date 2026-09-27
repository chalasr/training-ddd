<?php

declare(strict_types=1);

namespace App\Ordering\Infrastructure\Console;

use App\Ordering\Application\Command\RejectOrderCommand;
use App\Shared\Application\Command\CommandBusInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:order:reject', description: 'Le restaurant refuse une commande')]
final class RejectOrderConsoleCommand
{
    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument('Identifiant de la commande', name: 'order')] string $orderId,
    ): int {
        try {
            $this->commandBus->dispatch(new RejectOrderCommand($orderId));
        } catch (\DomainException|\InvalidArgumentException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $io->success(sprintf('Commande %s refusée.', $orderId));

        return Command::SUCCESS;
    }
}
