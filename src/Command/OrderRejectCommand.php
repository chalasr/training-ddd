<?php

namespace App\Command;

use App\Service\OrderService;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:order:reject', description: 'Le restaurant refuse une commande')]
class OrderRejectCommand
{
    public function __construct(private OrderService $orderService)
    {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument('Identifiant de la commande')] string $order,
    ): int {
        try {
            $order = $this->orderService->rejectOrder((int) $order);
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $io->success(sprintf('Commande %s refusée.', $order->getId()));

        return Command::SUCCESS;
    }
}
