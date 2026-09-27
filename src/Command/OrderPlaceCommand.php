<?php

namespace App\Command;

use App\Service\OrderService;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:order:place', description: 'Passe une commande (panier + créneau) et la valide')]
class OrderPlaceCommand
{
    public function __construct(private OrderService $orderService)
    {
    }

    /**
     * @param list<string> $dishes au format plat:quantité
     */
    public function __invoke(
        SymfonyStyle $io,
        #[Argument('Identifiant du client')] string $customer,
        #[Argument('Identifiant du restaurant')] string $restaurant,
        #[Argument('Début du créneau de livraison, "AAAA-MM-JJ HH:MM"')] string $slot,
        #[Argument('Plats, au format plat:quantité')] array $dishes,
    ): int {
        // Vérifications de saisie avant d'appeler le service
        $start = \DateTime::createFromFormat('Y-m-d H:i', $slot);
        if (!$start) {
            $io->error('Créneau invalide, format attendu : AAAA-MM-JJ HH:MM');

            return Command::FAILURE;
        }
        if (!in_array($start->format('i'), ['00', '15', '30', '45'])) {
            $io->error('Les créneaux commencent à l\'heure, et quart, et demie ou moins le quart.');

            return Command::FAILURE;
        }
        if ($start->getTimestamp() <= time() + 30 * 60) {
            $io->error('Le créneau doit commencer au moins 30 minutes après la commande.');

            return Command::FAILURE;
        }

        $items = [];
        foreach ($dishes as $dish) {
            [$dishId, $quantity] = array_pad(explode(':', (string) $dish, 2), 2, '1');
            if ((int) $quantity < 1) {
                $io->error('Quantité invalide pour '.$dishId);

                return Command::FAILURE;
            }
            $items[$dishId] = ($items[$dishId] ?? 0) + (int) $quantity;
        }

        try {
            $order = $this->orderService->placeOrder(
                $customer,
                $restaurant,
                $items,
                $slot,
            );
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $io->success(sprintf(
            'Commande %s validée. Total : %s € (dont %s € de livraison), livraison entre %s et %s.',
            $order->getId(),
            number_format($order->getTotal() / 100, 2, ',', ' '),
            number_format($order->getDeliveryFee() / 100, 2, ',', ' '),
            $order->getDeliverySlotStart()?->format('H:i'),
            $order->getDeliverySlotEnd()?->format('H:i'),
        ));

        return Command::SUCCESS;
    }
}
