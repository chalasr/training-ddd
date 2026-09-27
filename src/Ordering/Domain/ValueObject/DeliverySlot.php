<?php

declare(strict_types=1);

namespace App\Ordering\Domain\ValueObject;

use App\Ordering\Domain\Exception\DeliverySlotIsTooSoon;
use App\Ordering\Domain\Exception\DeliverySlotMustStartOnAQuarterHour;
use Doctrine\ORM\Mapping as ORM;

/**
 * R3 : un créneau de livraison dure 15 minutes et commence au plus tôt 30 minutes après la validation.
 * (Les horaires d'ouverture dépendent du restaurant : ils sont vérifiés au moment de valider la commande.)
 */
#[ORM\Embeddable]
final readonly class DeliverySlot
{
    public const int DURATION_IN_MINUTES = 15;
    public const int MINIMUM_LEAD_TIME_IN_MINUTES = 30;

    private function __construct(
        #[ORM\Column]
        public \DateTimeImmutable $start,
    )
    {
    }

    public static function startingAt(\DateTimeImmutable $start, \DateTimeImmutable $now): self
    {
        if (0 !== (int) $start->format('i') % self::DURATION_IN_MINUTES || '00' !== $start->format('s')) {
            throw DeliverySlotMustStartOnAQuarterHour::at($start);
        }

        if ($start < $now->modify(sprintf('+%d minutes', self::MINIMUM_LEAD_TIME_IN_MINUTES))) {
            throw DeliverySlotIsTooSoon::at($start, $now);
        }

        return new self($start);
    }

    public function end(): \DateTimeImmutable
    {
        return $this->start->modify(sprintf('+%d minutes', self::DURATION_IN_MINUTES));
    }

    public function equals(self $other): bool
    {
        return $this->start == $other->start;
    }
}
