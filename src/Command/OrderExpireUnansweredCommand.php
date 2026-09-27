<?php

namespace App\Command;

use App\Service\OrderService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * À lancer toutes les minutes via cron : * * * * * bin/console app:order:expire-unanswered
 */
#[AsCommand(name: 'app:order:expire-unanswered', description: 'Annule les commandes sans réponse du restaurant depuis 5 minutes')]
class OrderExpireUnansweredCommand
{
    public function __construct(private OrderService $orderService)
    {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $count = $this->orderService->expireUnansweredOrders();
        $io->success(sprintf('%d commande(s) annulée(s) faute de réponse.', $count));

        return Command::SUCCESS;
    }
}
