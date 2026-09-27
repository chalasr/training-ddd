<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Exception;

use App\Ordering\Domain\ValueObject\Money;

/**
 * R2 : chaque restaurant fixe un montant minimum de commande, hors frais de livraison.
 */
final class OrderIsBelowMinimumAmount extends \DomainException
{
    public static function of(Money $subtotal, Money $minimum): self
    {
        return new self(sprintf(
            'Le montant minimum de commande pour ce restaurant est de %s € (panier : %s €).',
            number_format($minimum->cents / 100, 2, ',', ' '),
            number_format($subtotal->cents / 100, 2, ',', ' '),
        ));
    }
}
