<?php

namespace App\Catalog\Command;

use App\Catalog\Repository\RestaurantRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:catalog:list', description: 'Liste les restaurants et leurs plats')]
class CatalogListCommand
{
    public function __construct(private RestaurantRepository $restaurantRepository)
    {
    }

    public function __invoke(SymfonyStyle $io, OutputInterface $output): int
    {
        foreach ($this->restaurantRepository->findAll() as $restaurant) {
            $io->section(sprintf(
                '%s (%s) — %s à %s, minimum %s €',
                $restaurant->getName(),
                $restaurant->getId(),
                $restaurant->getOpeningTime(),
                $restaurant->getClosingTime(),
                number_format($restaurant->getMinimumOrderAmount() / 100, 2, ',', ' '),
            ));

            $rows = [];
            foreach ($restaurant->getDishes() as $dish) {
                $rows[] = [$dish->getId(), $dish->getName(), number_format($dish->getPrice() / 100, 2, ',', ' ').' €'];
            }
            (new Table($output))->setHeaders(['Id', 'Plat', 'Prix'])->setRows($rows)->render();
        }

        return Command::SUCCESS;
    }
}
