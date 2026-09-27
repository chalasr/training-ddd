<?php

declare(strict_types=1);

namespace App\Tests\Unit\Ordering\Domain\ValueObject;

use App\Ordering\Domain\ValueObject\Money;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function test_two_amounts_are_equal_when_they_have_the_same_value_and_currency(): void
    {
        self::assertTrue(Money::ofCents(1650)->equals(Money::ofCents(1650)));
        self::assertFalse(Money::ofCents(1650)->equals(Money::ofCents(1600)));
        self::assertFalse(Money::ofCents(1650, 'EUR')->equals(Money::ofCents(1650, 'CHF')));
    }

    public function test_adding_amounts_returns_a_new_amount(): void
    {
        $price = Money::ofCents(1650);

        $total = $price->add(Money::ofCents(600));

        self::assertEquals(Money::ofCents(2250), $total);
        self::assertEquals(Money::ofCents(1650), $price, 'Le montant de départ est immuable.');
    }

    public function test_a_price_can_be_multiplied_by_a_quantity(): void
    {
        self::assertEquals(Money::ofCents(1200), Money::ofCents(600)->multiply(2));
    }

    public function test_amounts_are_compared(): void
    {
        self::assertTrue(Money::ofCents(1500)->isGreaterThanOrEqual(Money::ofCents(1500)));
        self::assertTrue(Money::ofCents(1600)->isGreaterThanOrEqual(Money::ofCents(1500)));
        self::assertFalse(Money::ofCents(1499)->isGreaterThanOrEqual(Money::ofCents(1500)));
    }

    public function test_an_amount_cannot_be_negative(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Money::ofCents(-1);
    }

    public function test_amounts_in_different_currencies_cannot_be_added(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Money::ofCents(100, 'EUR')->add(Money::ofCents(100, 'CHF'));
    }
}
