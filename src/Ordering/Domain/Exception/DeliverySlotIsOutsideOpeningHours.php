<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Exception;

use App\Ordering\Domain\ValueObject\DeliverySlot;
use App\Ordering\Domain\ValueObject\OpeningHours;

/**
 * R3 : le créneau de livraison tombe pendant les horaires d'ouverture du restaurant.
 */
final class DeliverySlotIsOutsideOpeningHours extends \DomainException
{
    public static function for(DeliverySlot $slot, OpeningHours $openingHours): self
    {
        return new self(sprintf(
            'Le restaurant est fermé sur ce créneau (%s - %s) : il est ouvert de %s à %s.',
            $slot->start->format('H:i'),
            $slot->end()->format('H:i'),
            $openingHours->opensAt,
            $openingHours->closesAt,
        ));
    }
}
