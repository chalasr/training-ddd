<?php

declare(strict_types=1);

namespace App\Tests\Unit\Ordering\Domain\ValueObject;

use App\Ordering\Domain\Exception\DeliverySlotIsTooSoon;
use App\Ordering\Domain\Exception\DeliverySlotMustStartOnAQuarterHour;
use App\Ordering\Domain\ValueObject\DeliverySlot;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class DeliverySlotTest extends TestCase
{
    private \DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->now = new \DateTimeImmutable('2026-10-01 11:50');
    }

    public function test_a_delivery_slot_lasts_fifteen_minutes(): void
    {
        $slot = DeliverySlot::startingAt(new \DateTimeImmutable('2026-10-01 12:30'), $this->now);

        self::assertEquals(new \DateTimeImmutable('2026-10-01 12:45'), $slot->end());
    }

    #[TestWith(['12:30'])]
    #[TestWith(['12:45'])]
    #[TestWith(['13:00'])]
    public function test_a_delivery_slot_starts_on_a_quarter_hour(string $time): void
    {
        $slot = DeliverySlot::startingAt(new \DateTimeImmutable('2026-10-01 '.$time), $this->now);

        self::assertSame($time, $slot->start->format('H:i'));
    }

    #[TestWith(['12:40'])]
    #[TestWith(['12:31'])]
    #[TestWith(['12:30:30'])]
    public function test_a_delivery_slot_cannot_start_between_two_quarter_hours(string $time): void
    {
        $this->expectException(DeliverySlotMustStartOnAQuarterHour::class);

        DeliverySlot::startingAt(new \DateTimeImmutable('2026-10-01 '.$time), $this->now);
    }

    public function test_a_delivery_slot_starts_at_least_thirty_minutes_after_the_order(): void
    {
        $slot = DeliverySlot::startingAt(new \DateTimeImmutable('2026-10-01 12:30'), new \DateTimeImmutable('2026-10-01 12:00'));

        self::assertSame('12:30', $slot->start->format('H:i'));
    }

    public function test_a_delivery_slot_cannot_start_less_than_thirty_minutes_after_the_order(): void
    {
        $this->expectException(DeliverySlotIsTooSoon::class);

        DeliverySlot::startingAt(new \DateTimeImmutable('2026-10-01 12:15'), $this->now);
    }

    public function test_two_slots_starting_at_the_same_time_are_equal(): void
    {
        $start = new \DateTimeImmutable('2026-10-01 12:30');

        self::assertTrue(DeliverySlot::startingAt($start, $this->now)->equals(DeliverySlot::startingAt($start, $this->now)));
    }
}
