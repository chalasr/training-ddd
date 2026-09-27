<?php

declare(strict_types=1);

namespace App\Ordering\Application\Port;

use App\Ordering\Application\Exception\UnknownDish;
use App\Ordering\Application\Exception\UnknownRestaurant;
use App\Ordering\Domain\ValueObject\Dish;
use App\Ordering\Domain\ValueObject\RestaurantId;
use App\Ordering\Domain\ValueObject\RestaurantTerms;

/**
 * Port : ce dont la Prise de commande a besoin du Catalogue, exprimé dans SON langage.
 */
interface RestaurantCatalog
{
    /**
     * @throws UnknownRestaurant
     */
    public function termsOf(RestaurantId $restaurantId): RestaurantTerms;

    /**
     * @throws UnknownDish
     */
    public function dish(string $dishId): Dish;
}
