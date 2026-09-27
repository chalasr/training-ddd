<?php

declare(strict_types=1);

namespace App\Ordering\Infrastructure\Catalog;

use App\Catalog\Repository\DishRepository;
use App\Catalog\Repository\RestaurantRepository;
use App\Ordering\Application\Exception\UnknownDish;
use App\Ordering\Application\Exception\UnknownRestaurant;
use App\Ordering\Application\Port\RestaurantCatalog;
use App\Ordering\Domain\ValueObject\Dish;
use App\Ordering\Domain\ValueObject\Money;
use App\Ordering\Domain\ValueObject\OpeningHours;
use App\Ordering\Domain\ValueObject\RestaurantId;
use App\Ordering\Domain\ValueObject\RestaurantTerms;

/**
 * Anti-corruption layer : traduit le modèle CRUD du Catalogue (entités Doctrine, centimes en int,
 * horaires en chaînes, flags active/available) dans le langage de la Prise de commande.
 * C'est le seul endroit d'Ordering qui connaît une classe du Catalogue.
 */
final readonly class RestaurantCatalogAntiCorruptionLayer implements RestaurantCatalog
{
    public function __construct(
        private RestaurantRepository $restaurants,
        private DishRepository $dishes,
    ) {
    }

    public function termsOf(RestaurantId $restaurantId): RestaurantTerms
    {
        $restaurant = $this->restaurants->find($restaurantId->value);

        if (null === $restaurant || !$restaurant->isActive()) {
            throw UnknownRestaurant::withId($restaurantId);
        }

        return RestaurantTerms::of(
            Money::ofCents($restaurant->getMinimumOrderAmount()),
            OpeningHours::between((string) $restaurant->getOpeningTime(), (string) $restaurant->getClosingTime()),
        );
    }

    public function dish(string $dishId): Dish
    {
        $dish = $this->dishes->find($dishId);

        if (null === $dish || !$dish->isAvailable() || null === $dish->getRestaurant()) {
            throw UnknownDish::withId($dishId);
        }

        return Dish::of(
            (string) $dish->getId(),
            (string) $dish->getName(),
            RestaurantId::fromString((string) $dish->getRestaurant()->getId()),
            Money::ofCents($dish->getPrice()),
        );
    }
}
