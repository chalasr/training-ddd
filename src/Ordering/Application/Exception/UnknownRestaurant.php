<?php

declare(strict_types=1);

namespace App\Ordering\Application\Exception;

use App\Ordering\Domain\ValueObject\RestaurantId;

final class UnknownRestaurant extends \DomainException
{
    public static function withId(RestaurantId $restaurantId): self
    {
        return new self(sprintf('Le restaurant %s n\'existe pas ou ne prend pas de commandes.', $restaurantId));
    }
}
