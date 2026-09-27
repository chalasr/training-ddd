<?php

declare(strict_types=1);

namespace App\Ordering\Domain\ValueObject;

/**
 * Les conditions qu'un restaurant impose à une commande : montant minimum (R2) et horaires (R3).
 */
final readonly class RestaurantTerms
{
    private function __construct(
        public Money $minimumAmount,
        public OpeningHours $openingHours,
    ) {
    }

    public static function of(Money $minimumAmount, OpeningHours $openingHours): self
    {
        return new self($minimumAmount, $openingHours);
    }
}
