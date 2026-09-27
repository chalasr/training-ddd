<?php

declare(strict_types=1);

namespace App\Tests\Integration\Ordering\Infrastructure\Doctrine;

use App\Ordering\Domain\Model\Order;
use App\Ordering\Domain\Model\OrderStatus;
use App\Ordering\Domain\Service\DeliveryFeeCalculator;
use App\Ordering\Domain\ValueObject\CustomerId;
use App\Ordering\Domain\ValueObject\DeliverySlot;
use App\Ordering\Domain\ValueObject\Dish;
use App\Ordering\Domain\ValueObject\Money;
use App\Ordering\Domain\ValueObject\OpeningHours;
use App\Ordering\Domain\ValueObject\OrderId;
use App\Ordering\Domain\ValueObject\Quantity;
use App\Ordering\Domain\ValueObject\RestaurantId;
use App\Ordering\Domain\ValueObject\RestaurantTerms;
use App\Ordering\Infrastructure\Doctrine\DoctrineOrderRepository;
use App\Tests\Support\DatabaseTestCase;

final class DoctrineOrderRepositoryTest extends DatabaseTestCase
{
    private DoctrineOrderRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new DoctrineOrderRepository($this->entityManager());
    }

    public function test_a_placed_order_is_saved_and_found_again_by_its_id(): void
    {
        $order = $this->placedOrder();

        $this->repository->save($order);
        $this->entityManager()->clear();
        $found = $this->repository->ofId($order->id());

        self::assertNotNull($found);
        self::assertNotSame($order, $found, 'L\'agrégat est bien relu depuis la base.');
        self::assertTrue($found->id()->equals($order->id()));
        self::assertSame('alice', $found->customerId()->value);
        self::assertSame('chez-ginette', $found->restaurantId()->value);
        self::assertSame(OrderStatus::Placed, $found->status());
        self::assertEquals($order->deliverySlot(), $found->deliverySlot());
        self::assertEquals(Money::ofCents(390), $found->deliveryFee());
        self::assertCount(2, $found->lines());
        self::assertEquals(Money::ofCents(3240), $found->total());
    }

    public function test_changes_made_through_the_aggregate_are_saved(): void
    {
        $order = $this->placedOrder();
        $this->repository->save($order);
        $this->entityManager()->clear();

        $found = $this->repository->ofId($order->id());
        self::assertNotNull($found);
        $found->accept(new \DateTimeImmutable('2026-10-01 11:02'));
        $this->repository->save($found);
        $this->entityManager()->clear();

        self::assertSame(OrderStatus::Accepted, $this->repository->ofId($order->id())?->status());
    }

    public function test_an_unknown_order_is_not_found(): void
    {
        self::assertNull($this->repository->ofId(OrderId::generate()));
    }

    private function placedOrder(): Order
    {
        $now = new \DateTimeImmutable('2026-10-01 11:00');
        $ginette = RestaurantId::fromString('chez-ginette');

        $order = Order::draft($this->repository->nextIdentity(), CustomerId::fromString('alice'), $ginette);
        $order->addLine(Dish::of('blanquette', 'Blanquette de veau', $ginette, Money::ofCents(1650)), Quantity::of(1));
        $order->addLine(Dish::of('tarte-tatin', 'Tarte Tatin', $ginette, Money::ofCents(600)), Quantity::of(2));
        $order->place(
            DeliverySlot::startingAt(new \DateTimeImmutable('2026-10-01 12:30'), $now),
            RestaurantTerms::of(Money::ofCents(1500), OpeningHours::between('11:30', '22:30')),
            new DeliveryFeeCalculator(),
            $now,
        );

        return $order;
    }
}
