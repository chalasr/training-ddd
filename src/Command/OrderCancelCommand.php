<?php

namespace App\Command;

use App\Service\OrderService;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:order:cancel', description: 'Le client annule sa commande')]
class OrderCancelCommand
{
    public function __construct(private OrderService $orderService)
    {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument('Identifiant de la commande')] string $order,
    ): int {
        try {
            $this->orderService->cancelOrder($order);
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $io->success(sprintf('Commande %s annulée.', $order));

        return Command::SUCCESS;
    }
}
