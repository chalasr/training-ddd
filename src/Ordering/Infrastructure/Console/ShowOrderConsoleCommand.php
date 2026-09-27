<?php

declare(strict_types=1);

namespace App\Ordering\Infrastructure\Console;

use App\Ordering\Application\Query\FindOrderQuery;
use App\Ordering\Domain\Model\Order;
use App\Shared\Application\Query\QueryBusInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:order:show', description: 'Affiche une commande')]
final class ShowOrderConsoleCommand
{
    public function __construct(private readonly QueryBusInterface $queryBus)
    {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument('Identifiant de la commande')] string $order,
    ): int {
        try {
            $order = $this->queryBus->ask(new FindOrderQuery($order));
        } catch (\InvalidArgumentException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        if (!$order instanceof Order) {
            $io->error('Commande introuvable : '.$order);

            return Command::FAILURE;
        }

        $io->title('Commande '.$order->id());
        $io->writeln([
            'Restaurant : '.$order->restaurantId(),
            'Client     : '.$order->customerId(),
            'Statut     : '.$order->status()->value,
            'Créneau    : '.$order->deliverySlot()?->start->format('Y-m-d H:i').' - '.$order->deliverySlot()?->end()->format('H:i'),
            '',
        ]);
        foreach ($order->lines() as $line) {
            $io->writeln(sprintf('  %d x %s  %s €', $line->quantity()->value, $line->dishName(), Euros::format($line->unitPrice())));
        }
        $io->writeln([
            '',
            'Sous-total : '.Euros::format($order->subtotal()).' €',
            'Livraison  : '.Euros::format($order->deliveryFee()).' €',
            'Total      : '.Euros::format($order->total()).' €',
        ]);

        return Command::SUCCESS;
    }
}
