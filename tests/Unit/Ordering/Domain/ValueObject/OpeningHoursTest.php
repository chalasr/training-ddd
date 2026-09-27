<?php

declare(strict_types=1);

namespace App\Tests\Unit\Ordering\Domain\ValueObject;

use App\Ordering\Domain\ValueObject\DeliverySlot;
use App\Ordering\Domain\ValueObject\OpeningHours;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class OpeningHoursTest extends TestCase
{
    #[TestWith(['11:30', true])]
    #[TestWith(['22:15', true])]
    #[TestWith(['11:15', false])]
    #[TestWith(['22:30', false])]
    public function test_a_slot_is_covered_when_it_fits_entirely_within_opening_hours(string $start, bool $covered): void
    {
        $slot = DeliverySlot::startingAt(new \DateTimeImmutable('2026-10-01 '.$start), new \DateTimeImmutable('2026-10-01 08:00'));

        self::assertSame($covered, OpeningHours::between('11:30', '22:30')->covers($slot));
    }

    public function test_a_restaurant_closes_after_it_opens(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        OpeningHours::between('22:30', '11:30');
    }
}
