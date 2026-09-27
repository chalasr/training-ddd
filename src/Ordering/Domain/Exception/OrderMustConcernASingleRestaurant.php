<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Exception;

use App\Ordering\Domain\ValueObject\Dish;
use App\Ordering\Domain\ValueObject\RestaurantId;

/**
 * R1 : une commande ne concerne qu'un seul restaurant.
 */
final class OrderMustConcernASingleRestaurant extends \DomainException
{
    public static function cannotAdd(Dish $dish, RestaurantId $orderRestaurant): self
    {
        return new self(sprintf(
            'Le plat "%s" vient du restaurant %s, or la commande concerne le restaurant %s.',
            $dish->name,
            $dish->restaurantId,
            $orderRestaurant,
        ));
    }
}
