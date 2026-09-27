<?php

declare(strict_types=1);

namespace App\Tests\Unit\Ordering\Application\Command;

use App\Ordering\Application\Command\PlaceOrderCommand;
use App\Ordering\Application\Command\PlaceOrderCommandHandler;
use App\Ordering\Application\Exception\UnknownDish;
use App\Ordering\Domain\Event\OrderPlaced;
use App\Ordering\Domain\Model\OrderStatus;
use App\Ordering\Domain\Service\DeliveryFeeCalculator;
use App\Ordering\Domain\ValueObject\Dish;
use App\Ordering\Domain\ValueObject\Money;
use App\Ordering\Domain\ValueObject\OpeningHours;
use App\Ordering\Domain\ValueObject\RestaurantId;
use App\Ordering\Domain\ValueObject\RestaurantTerms;
use App\Ordering\Infrastructure\InMemory\InMemoryOrderRepository;
use App\Ordering\Infrastructure\InMemory\InMemoryRestaurantCatalog;
use App\Shared\Infrastructure\InMemory\InMemoryEventBus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

/**
 * Le cas d'usage testé sans base de données : les adaptateurs en mémoire remplacent Doctrine.
 */
final class PlaceOrderCommandHandlerTest extends TestCase
{
    private InMemoryOrderRepository $orders;
    private InMemoryEventBus $eventBus;
    private PlaceOrderCommandHandler $handler;

    protected function setUp(): void
    {
        $ginette = RestaurantId::fromString('chez-ginette');
        $catalog = new InMemoryRestaurantCatalog();
        $catalog->addRestaurant($ginette, RestaurantTerms::of(Money::ofCents(1500), OpeningHours::between('11:30', '22:30')));
        $catalog->addDish(Dish::of('blanquette', 'Blanquette de veau', $ginette, Money::ofCents(1650)));

        $this->orders = new InMemoryOrderRepository();
        $this->eventBus = new InMemoryEventBus();
        $this->handler = new PlaceOrderCommandHandler(
            $this->orders,
            $catalog,
            new DeliveryFeeCalculator(),
            $this->eventBus,
            new MockClock(new \DateTimeImmutable('2026-10-01 11:00')),
        );
    }

    public function test_placing_an_order_saves_a_placed_order(): void
    {
        $orderId = ($this->handler)(new PlaceOrderCommand('alice', 'chez-ginette', new \DateTimeImmutable('2026-10-01 12:30'), ['blanquette' => 1]));

        $order = $this->orders->ofId($orderId);
        self::assertNotNull($order);
        self::assertSame(OrderStatus::Placed, $order->status());
        self::assertEquals(Money::ofCents(2040), $order->total());
    }

    public function test_placing_an_order_publishes_order_placed(): void
    {
        ($this->handler)(new PlaceOrderCommand('alice', 'chez-ginette', new \DateTimeImmutable('2026-10-01 12:30'), ['blanquette' => 1]));

        self::assertCount(1, $this->eventBus->published);
        self::assertInstanceOf(OrderPlaced::class, $this->eventBus->published[0]);
    }

    public function test_an_unknown_dish_cannot_be_ordered(): void
    {
        $this->expectException(UnknownDish::class);

        ($this->handler)(new PlaceOrderCommand('alice', 'chez-ginette', new \DateTimeImmutable('2026-10-01 12:30'), ['cassoulet' => 1]));
    }
}
