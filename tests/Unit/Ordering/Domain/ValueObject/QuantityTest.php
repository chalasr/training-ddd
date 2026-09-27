<?php

declare(strict_types=1);

namespace App\Tests\Unit\Ordering\Domain\ValueObject;

use App\Ordering\Domain\ValueObject\Quantity;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class QuantityTest extends TestCase
{
    #[TestWith([0])]
    #[TestWith([-2])]
    public function test_a_quantity_is_strictly_positive(int $invalid): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Quantity::of($invalid);
    }

    public function test_quantities_add_up(): void
    {
        self::assertEquals(Quantity::of(3), Quantity::of(1)->add(Quantity::of(2)));
    }
}
