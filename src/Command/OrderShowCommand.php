<?php

namespace App\Command;

use App\Service\OrderService;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:order:show', description: 'Affiche une commande')]
class OrderShowCommand
{
    public function __construct(private OrderService $orderService)
    {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument('Identifiant de la commande')] string $order,
    ): int {
        try {
            $order = $this->orderService->getOrder((int) $order);
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $io->title('Commande '.$order->getId());
        $io->writeln([
            'Restaurant : '.$order->getRestaurant()?->getId(),
            'Client     : '.$order->getCustomerId(),
            'Statut     : '.$order->getStatus(),
            'Créneau    : '.$order->getDeliverySlotStart()?->format('Y-m-d H:i').' - '.$order->getDeliverySlotEnd()?->format('H:i'),
            '',
        ]);
        foreach ($order->getItems() as $item) {
            $io->writeln(sprintf('  %d x %s  %s €', $item->getQuantity(), $item->getDishName(), number_format($item->getUnitPrice() / 100, 2, ',', ' ')));
        }
        $io->writeln([
            '',
            'Sous-total : '.number_format($order->getSubtotal() / 100, 2, ',', ' ').' €',
            'Livraison  : '.number_format($order->getDeliveryFee() / 100, 2, ',', ' ').' €',
            'Total      : '.number_format($order->getTotal() / 100, 2, ',', ' ').' €',
        ]);

        return Command::SUCCESS;
    }
}
