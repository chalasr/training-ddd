<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Service;

use App\Ordering\Domain\ValueObject\DeliverySlot;
use App\Ordering\Domain\ValueObject\Money;

/**
 * Service du domaine : la politique tarifaire de livraison de Popote.
 * Elle combine le montant du panier et le créneau, et n'appartient naturellement à aucun des deux.
 */
final class DeliveryFeeCalculator
{
    private const int BASE_FEE_IN_CENTS = 290;
    private const int FREE_DELIVERY_THRESHOLD_IN_CENTS = 3000;
    private const int RUSH_HOUR_SURCHARGE_IN_CENTS = 100;
    private const array RUSH_HOURS = [['12:00', '13:30'], ['19:00', '20:30']];

    public function feeFor(Money $subtotal, DeliverySlot $slot): Money
    {
        if ($subtotal->isGreaterThanOrEqual(Money::ofCents(self::FREE_DELIVERY_THRESHOLD_IN_CENTS, $subtotal->currency))) {
            return Money::zero($subtotal->currency);
        }

        $fee = Money::ofCents(self::BASE_FEE_IN_CENTS, $subtotal->currency);

        if ($this->isDuringRushHour($slot)) {
            $fee = $fee->add(Money::ofCents(self::RUSH_HOUR_SURCHARGE_IN_CENTS, $subtotal->currency));
        }

        return $fee;
    }

    private function isDuringRushHour(DeliverySlot $slot): bool
    {
        $time = $slot->start->format('H:i');

        foreach (self::RUSH_HOURS as [$from, $to]) {
            if ($time >= $from && $time < $to) {
                return true;
            }
        }

        return false;
    }
}
