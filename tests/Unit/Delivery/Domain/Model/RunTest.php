<?php

declare(strict_types=1);

namespace App\Tests\Unit\Delivery\Domain\Model;

use App\Delivery\Domain\Exception\RunCannotCarryMoreThanTwoOrders;
use App\Delivery\Domain\Exception\RunIsAlreadyAssigned;
use App\Delivery\Domain\Model\Run;
use App\Delivery\Domain\ValueObject\CourierId;
use App\Delivery\Domain\ValueObject\OrderReference;
use App\Delivery\Domain\ValueObject\RunId;
use PHPUnit\Framework\TestCase;

final class RunTest extends TestCase
{
    public function test_a_run_is_proposed_for_an_accepted_order(): void
    {
        $run = $this->runFor('order-1');

        self::assertEquals([OrderReference::fromString('order-1')], $run->orders());
        self::assertNull($run->courierId());
    }

    public function test_a_run_carries_up_to_two_orders(): void
    {
        $run = $this->runFor('order-1');

        $run->addOrder(OrderReference::fromString('order-2'));

        self::assertCount(2, $run->orders());
    }

    public function test_a_run_cannot_carry_a_third_order(): void
    {
        $run = $this->runFor('order-1');
        $run->addOrder(OrderReference::fromString('order-2'));

        $this->expectException(RunCannotCarryMoreThanTwoOrders::class);

        $run->addOrder(OrderReference::fromString('order-3'));
    }

    public function test_a_run_is_assigned_to_one_courier_only(): void
    {
        $run = $this->runFor('order-1');
        $run->assignTo(CourierId::fromString('karim'));

        $this->expectException(RunIsAlreadyAssigned::class);

        $run->assignTo(CourierId::fromString('lea'));
    }

    private function runFor(string $orderId): Run
    {
        return Run::propose(RunId::generate(), OrderReference::fromString($orderId), new \DateTimeImmutable('2026-10-01 12:45'));
    }
}
