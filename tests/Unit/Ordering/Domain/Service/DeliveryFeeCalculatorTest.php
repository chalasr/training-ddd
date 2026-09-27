<?php

declare(strict_types=1);

namespace App\Tests\Unit\Ordering\Domain\Service;

use App\Ordering\Domain\Service\DeliveryFeeCalculator;
use App\Ordering\Domain\ValueObject\DeliverySlot;
use App\Ordering\Domain\ValueObject\Money;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class DeliveryFeeCalculatorTest extends TestCase
{
    public function test_delivery_costs_two_euros_ninety_outside_rush_hours(): void
    {
        self::assertEquals(Money::ofCents(290), (new DeliveryFeeCalculator())->feeFor(Money::ofCents(2000), $this->slotAt('15:00')));
    }

    #[TestWith(['12:00'])]
    #[TestWith(['13:15'])]
    #[TestWith(['19:30'])]
    public function test_delivery_costs_one_euro_more_during_rush_hours(string $time): void
    {
        self::assertEquals(Money::ofCents(390), (new DeliveryFeeCalculator())->feeFor(Money::ofCents(2000), $this->slotAt($time)));
    }

    public function test_delivery_is_free_from_thirty_euros(): void
    {
        self::assertEquals(Money::zero(), (new DeliveryFeeCalculator())->feeFor(Money::ofCents(3000), $this->slotAt('12:30')));
    }

    private function slotAt(string $time): DeliverySlot
    {
        return DeliverySlot::startingAt(new \DateTimeImmutable('2026-10-01 '.$time), new \DateTimeImmutable('2026-10-01 08:00'));
    }
}
