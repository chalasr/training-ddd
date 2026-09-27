<?php

declare(strict_types=1);

namespace App\Ordering\Domain\ValueObject;

use Webmozart\Assert\Assert;

/**
 * Un plat tel que la Prise de commande le voit : ce qu'on commande, chez qui, à quel prix.
 * Le Catalogue a son propre modèle de plat (disponibilité, description...) : même mot, autre contexte.
 */
final readonly class Dish
{
    private function __construct(
        public string $id,
        public string $name,
        public RestaurantId $restaurantId,
        public Money $price,
    ) {
        Assert::stringNotEmpty($id);
        Assert::stringNotEmpty($name);
    }

    public static function of(string $id, string $name, RestaurantId $restaurantId, Money $price): self
    {
        return new self($id, $name, $restaurantId, $price);
    }
}
