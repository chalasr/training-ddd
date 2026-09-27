<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Exception;

final class DeliverySlotMustStartOnAQuarterHour extends \DomainException
{
    public static function at(\DateTimeImmutable $start): self
    {
        return new self(sprintf(
            'Un créneau commence à l\'heure, et quart, et demie ou moins le quart (reçu : %s).',
            $start->format('H:i:s'),
        ));
    }
}
