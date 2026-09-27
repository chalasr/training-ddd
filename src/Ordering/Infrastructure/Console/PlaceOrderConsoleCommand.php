<?php

declare(strict_types=1);

namespace App\Ordering\Infrastructure\Console;

use App\Ordering\Application\Command\PlaceOrderCommand;
use App\Ordering\Application\Query\FindOrderQuery;
use App\Ordering\Domain\Model\Order;
use App\Ordering\Domain\ValueObject\OrderId;
use App\Shared\Application\Command\CommandBusInterface;
use App\Shared\Application\Query\QueryBusInterface;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Adaptateur d'entrée : traduit la ligne de commande en PlaceOrderCommand. Aucune règle métier ici.
 */
#[AsCommand(name: 'app:order:place', description: 'Passe une commande (panier + créneau) et la valide')]
final class PlaceOrderConsoleCommand
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
    ) {
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
        try {
            $slotStart = DatePoint::createFromFormat('!Y-m-d H:i', $slot);
        } catch (\DateMalformedStringException) {
            $slotStart = null;
        }
        // Refuse aussi les dates qui "débordent" (2026-13-45 deviendrait une date de 2027)
        if (null === $slotStart || $slotStart->format('Y-m-d H:i') !== $slot) {
            $io->error('Créneau invalide, format attendu : AAAA-MM-JJ HH:MM');

            return Command::FAILURE;
        }

        $quantities = [];
        foreach ($dishes as $dish) {
            [$dishId, $quantity] = array_pad(explode(':', (string) $dish, 2), 2, '1');
            $quantities[$dishId] = ($quantities[$dishId] ?? 0) + (int) $quantity;
        }

        try {
            $orderId = $this->commandBus->dispatch(new PlaceOrderCommand(
                $customer,
                $restaurant,
                $slotStart,
                $quantities,
            ));
            \assert($orderId instanceof OrderId);
        } catch (\DomainException|\InvalidArgumentException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $order = $this->queryBus->ask(new FindOrderQuery($orderId->value));
        \assert($order instanceof Order);

        $io->success(sprintf(
            'Commande %s validée. Total : %s € (dont %s € de livraison), livraison entre %s et %s.',
            $order->id(),
            Euros::format($order->total()),
            Euros::format($order->deliveryFee()),
            $order->deliverySlot()?->start->format('H:i'),
            $order->deliverySlot()?->end()->format('H:i'),
        ));

        return Command::SUCCESS;
    }
}
