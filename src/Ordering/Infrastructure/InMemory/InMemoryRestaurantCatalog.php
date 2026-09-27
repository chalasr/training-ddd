<?php

declare(strict_types=1);

namespace App\Ordering\Infrastructure\InMemory;

use App\Ordering\Application\Exception\UnknownDish;
use App\Ordering\Application\Exception\UnknownRestaurant;
use App\Ordering\Application\Port\RestaurantCatalog;
use App\Ordering\Domain\ValueObject\Dish;
use App\Ordering\Domain\ValueObject\RestaurantId;
use App\Ordering\Domain\ValueObject\RestaurantTerms;

final class InMemoryRestaurantCatalog implements RestaurantCatalog
{
    /** @var array<string, RestaurantTerms> */
    private array $terms = [];

    /** @var array<string, Dish> */
    private array $dishes = [];

    public function addRestaurant(RestaurantId $restaurantId, RestaurantTerms $terms): void
    {
        $this->terms[$restaurantId->value] = $terms;
    }

    public function addDish(Dish $dish): void
    {
        $this->dishes[$dish->id] = $dish;
    }

    public function termsOf(RestaurantId $restaurantId): RestaurantTerms
    {
        return $this->terms[$restaurantId->value] ?? throw UnknownRestaurant::withId($restaurantId);
    }

    public function dish(string $dishId): Dish
    {
        return $this->dishes[$dishId] ?? throw UnknownDish::withId($dishId);
    }
}
