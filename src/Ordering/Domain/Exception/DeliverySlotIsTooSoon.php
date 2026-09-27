<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Exception;

use App\Ordering\Domain\ValueObject\DeliverySlot;

final class DeliverySlotIsTooSoon extends \DomainException
{
    public static function at(\DateTimeImmutable $start, \DateTimeImmutable $now): self
    {
        return new self(sprintf(
            'Le créneau doit commencer au moins %d minutes après la commande (créneau : %s, maintenant : %s).',
            DeliverySlot::MINIMUM_LEAD_TIME_IN_MINUTES,
            $start->format('H:i'),
            $now->format('H:i'),
        ));
    }
}
