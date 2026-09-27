<?php

declare(strict_types=1);

namespace App\Tests\Unit\Ordering\Domain\Model;

use App\Ordering\Domain\Event\OrderAccepted;
use App\Ordering\Domain\Event\OrderCancelled;
use App\Ordering\Domain\Event\OrderPlaced;
use App\Ordering\Domain\Event\OrderRejected;
use App\Ordering\Domain\Exception\AnswerDeadlineHasPassed;
use App\Ordering\Domain\Exception\DeliverySlotIsOutsideOpeningHours;
use App\Ordering\Domain\Exception\EmptyOrderCannotBePlaced;
use App\Ordering\Domain\Exception\OrderCannotBeModified;
use App\Ordering\Domain\Exception\OrderIsBelowMinimumAmount;
use App\Ordering\Domain\Exception\OrderIsNotAwaitingAnswer;
use App\Ordering\Domain\Exception\OrderMustConcernASingleRestaurant;
use App\Ordering\Domain\Model\CancellationReason;
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
use PHPUnit\Framework\TestCase;

final class OrderTest extends TestCase
{
    private const string NOW = '2026-10-01 11:00';

    public function test_a_new_order_is_a_draft(): void
    {
        $order = $this->draftAtGinette();

        self::assertSame(OrderStatus::Draft, $order->status());
        self::assertSame([], $order->lines());
    }

    public function test_adding_the_same_dish_twice_adds_up_the_quantities(): void
    {
        $order = $this->draftAtGinette();

        $order->addLine($this->blanquette(), Quantity::of(1));
        $order->addLine($this->blanquette(), Quantity::of(2));

        self::assertCount(1, $order->lines());
        self::assertEquals(Quantity::of(3), $order->lines()[0]->quantity());
        self::assertEquals(Money::ofCents(4950), $order->subtotal());
    }

    public function test_a_draft_order_line_can_be_removed(): void
    {
        $order = $this->draftAtGinette();
        $order->addLine($this->blanquette(), Quantity::of(1));
        $order->addLine($this->tarteTatin(), Quantity::of(1));

        $order->removeLine('blanquette');

        self::assertCount(1, $order->lines());
        self::assertSame('tarte-tatin', $order->lines()[0]->dishId());
    }

    public function test_an_order_only_concerns_one_restaurant(): void
    {
        $order = $this->draftAtGinette();

        $this->expectException(OrderMustConcernASingleRestaurant::class);

        $order->addLine(Dish::of('mochi', 'Mochi glacé', RestaurantId::fromString('sushi-kaze'), Money::ofCents(400)), Quantity::of(1));
    }

    public function test_placing_an_order_fixes_its_slot_fee_and_total(): void
    {
        $order = $this->draftAtGinette();
        $order->addLine($this->blanquette(), Quantity::of(1));

        $order->place($this->slotAt('12:30'), $this->ginetteTerms(), new DeliveryFeeCalculator(), $this->now());

        self::assertSame(OrderStatus::Placed, $order->status());
        self::assertEquals($this->slotAt('12:30'), $order->deliverySlot());
        self::assertEquals(Money::ofCents(390), $order->deliveryFee());
        self::assertEquals(Money::ofCents(2040), $order->total());
    }

    public function test_placing_an_order_records_that_it_was_placed(): void
    {
        $order = $this->placedOrder();

        $events = $order->releaseEvents();

        self::assertCount(1, $events);
        self::assertInstanceOf(OrderPlaced::class, $events[0]);
        self::assertEquals(new \DateTimeImmutable('2026-10-01 11:05'), $events[0]->answerDeadline);
        self::assertSame([], $order->releaseEvents(), 'Les événements ne sont publiés qu\'une fois.');
    }

    public function test_an_empty_order_cannot_be_placed(): void
    {
        $this->expectException(EmptyOrderCannotBePlaced::class);

        $this->draftAtGinette()->place($this->slotAt('12:30'), $this->ginetteTerms(), new DeliveryFeeCalculator(), $this->now());
    }

    public function test_an_order_below_the_restaurant_minimum_cannot_be_placed(): void
    {
        $order = $this->draftAtGinette();
        $order->addLine($this->tarteTatin(), Quantity::of(2));

        $this->expectException(OrderIsBelowMinimumAmount::class);

        $order->place($this->slotAt('12:30'), $this->ginetteTerms(), new DeliveryFeeCalculator(), $this->now());
    }

    public function test_the_minimum_amount_does_not_include_the_delivery_fee(): void
    {
        $order = $this->draftAtGinette();
        $order->addLine($this->tarteTatin(), Quantity::of(2));
        $terms = RestaurantTerms::of(Money::ofCents(1200), OpeningHours::between('11:30', '22:30'));

        $order->place($this->slotAt('12:30'), $terms, new DeliveryFeeCalculator(), $this->now());

        self::assertSame(OrderStatus::Placed, $order->status());
    }

    public function test_the_delivery_slot_must_fall_within_opening_hours(): void
    {
        $order = $this->draftAtGinette();
        $order->addLine($this->blanquette(), Quantity::of(1));

        $this->expectException(DeliverySlotIsOutsideOpeningHours::class);

        $order->place($this->slotAt('22:30'), $this->ginetteTerms(), new DeliveryFeeCalculator(), $this->now());
    }

    public function test_a_placed_order_cannot_be_modified(): void
    {
        $order = $this->placedOrder();

        $this->expectException(OrderCannotBeModified::class);

        $order->addLine($this->tarteTatin(), Quantity::of(1));
    }

    public function test_a_placed_order_line_cannot_be_removed(): void
    {
        $order = $this->placedOrder();

        $this->expectException(OrderCannotBeModified::class);

        $order->removeLine('blanquette');
    }

    public function test_the_restaurant_accepts_a_placed_order(): void
    {
        $order = $this->placedOrder();
        $order->releaseEvents();

        $order->accept($this->now()->modify('+4 minutes'));

        self::assertSame(OrderStatus::Accepted, $order->status());
        $events = $order->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(OrderAccepted::class, $events[0]);
        self::assertTrue($events[0]->orderId->equals($order->id()));
    }

    public function test_the_restaurant_cannot_accept_once_the_five_minutes_have_passed(): void
    {
        $order = $this->placedOrder();

        $this->expectException(AnswerDeadlineHasPassed::class);

        $order->accept($this->now()->modify('+5 minutes +1 second'));
    }

    public function test_the_restaurant_rejects_a_placed_order(): void
    {
        $order = $this->placedOrder();
        $order->releaseEvents();

        $order->reject($this->now()->modify('+1 minute'));

        self::assertSame(OrderStatus::Rejected, $order->status());
        self::assertInstanceOf(OrderRejected::class, $order->releaseEvents()[0]);
    }

    public function test_a_draft_order_cannot_be_accepted(): void
    {
        $this->expectException(OrderIsNotAwaitingAnswer::class);

        $this->draftAtGinette()->accept($this->now());
    }

    public function test_an_order_cannot_be_accepted_twice(): void
    {
        $order = $this->placedOrder();
        $order->accept($this->now());

        $this->expectException(OrderIsNotAwaitingAnswer::class);

        $order->accept($this->now());
    }

    public function test_an_order_without_answer_after_five_minutes_is_cancelled(): void
    {
        $order = $this->placedOrder();
        $order->releaseEvents();

        $order->expireIfNotAnswered($this->now()->modify('+5 minutes'));

        self::assertSame(OrderStatus::Cancelled, $order->status());
        self::assertEquals(
            [new OrderCancelled($order->id(), CancellationReason::RestaurantDidNotAnswer, $this->now()->modify('+5 minutes'))],
            $order->releaseEvents(),
        );
    }

    public function test_an_answered_order_does_not_expire(): void
    {
        $order = $this->placedOrder();
        $order->accept($this->now()->modify('+2 minutes'));
        $order->releaseEvents();

        $order->expireIfNotAnswered($this->now()->modify('+5 minutes'));

        self::assertSame(OrderStatus::Accepted, $order->status());
        self::assertSame([], $order->releaseEvents());
    }

    public function test_an_order_does_not_expire_before_its_deadline(): void
    {
        $order = $this->placedOrder();

        $order->expireIfNotAnswered($this->now()->modify('+4 minutes'));

        self::assertSame(OrderStatus::Placed, $order->status());
    }

    private function draftAtGinette(): Order
    {
        return Order::draft(OrderId::generate(), CustomerId::fromString('alice'), RestaurantId::fromString('chez-ginette'));
    }

    private function placedOrder(): Order
    {
        $order = $this->draftAtGinette();
        $order->addLine($this->blanquette(), Quantity::of(1));
        $order->place($this->slotAt('12:30'), $this->ginetteTerms(), new DeliveryFeeCalculator(), $this->now());

        return $order;
    }

    private function blanquette(): Dish
    {
        return Dish::of('blanquette', 'Blanquette de veau', RestaurantId::fromString('chez-ginette'), Money::ofCents(1650));
    }

    private function tarteTatin(): Dish
    {
        return Dish::of('tarte-tatin', 'Tarte Tatin', RestaurantId::fromString('chez-ginette'), Money::ofCents(600));
    }

    private function ginetteTerms(): RestaurantTerms
    {
        return RestaurantTerms::of(Money::ofCents(1500), OpeningHours::between('11:30', '22:30'));
    }

    private function slotAt(string $time): DeliverySlot
    {
        return DeliverySlot::startingAt(new \DateTimeImmutable('2026-10-01 '.$time), $this->now());
    }

    private function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable(self::NOW);
    }
}
