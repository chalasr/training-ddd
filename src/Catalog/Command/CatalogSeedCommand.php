<?php

namespace App\Catalog\Command;

use App\Catalog\Entity\Dish;
use App\Catalog\Entity\Restaurant;
use App\Catalog\Entity\Review;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:catalog:seed', description: 'Charge les restaurants et plats de démonstration')]
class CatalogSeedCommand
{
    private const RESTAURANTS = [
        'chez-ginette' => ['Chez Ginette', 1500, '11:30', '22:30', [
            'blanquette' => ['Blanquette de veau', 1650],
            'salade-lyonnaise' => ['Salade lyonnaise', 950],
            'tarte-tatin' => ['Tarte Tatin', 600],
        ]],
        'sushi-kaze' => ['Sushi Kaze', 2000, '12:00', '22:00', [
            'plateau-sushi' => ['Plateau 18 pièces', 2290],
            'maki-saumon' => ['Maki saumon (6)', 650],
            'mochi' => ['Mochi glacé', 400],
        ]],
        'pizzeria-nonna' => ['Pizzeria Nonna', 1200, '11:00', '23:00', [
            'margherita' => ['Pizza Margherita', 1100],
            'tiramisu' => ['Tiramisu', 550],
        ]],
    ];

    public function __construct(private EntityManagerInterface $em, private ClockInterface $clock)
    {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        foreach (self::RESTAURANTS as $id => [$name, $minimum, $opening, $closing, $dishes]) {
            if ($this->em->find(Restaurant::class, $id)) {
                continue;
            }

            $restaurant = new Restaurant();
            $restaurant->setId($id);
            $restaurant->setName($name);
            $restaurant->setMinimumOrderAmount($minimum);
            $restaurant->setOpeningTime($opening);
            $restaurant->setClosingTime($closing);

            foreach ($dishes as $dishId => [$dishName, $price]) {
                $dish = new Dish();
                $dish->setId($dishId);
                $dish->setName($dishName);
                $dish->setPrice($price);
                $restaurant->addDish($dish);
            }

            $review = new Review();
            $review->setRating(4);
            $review->setComment('Livré à l\'heure, rien à redire.');
            $review->setCustomerId('alice');
            $review->setCreatedAt(\DateTime::createFromImmutable($this->clock->now()->modify('-3 days')));
            $restaurant->addReview($review);

            $this->em->persist($restaurant);
        }

        $this->em->flush();
        $io->success('Catalogue chargé.');

        return Command::SUCCESS;
    }
}
