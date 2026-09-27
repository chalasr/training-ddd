<?php

declare(strict_types=1);

namespace App\Ordering\Application\Command;

use App\Ordering\Application\Port\RestaurantCatalog;
use App\Ordering\Domain\Model\Order;
use App\Ordering\Domain\Repository\OrderRepository;
use App\Ordering\Domain\Service\DeliveryFeeCalculator;
use App\Ordering\Domain\ValueObject\CustomerId;
use App\Ordering\Domain\ValueObject\DeliverySlot;
use App\Ordering\Domain\ValueObject\OrderId;
use App\Ordering\Domain\ValueObject\Quantity;
use App\Ordering\Domain\ValueObject\RestaurantId;
use App\Shared\Application\Command\CommandHandlerInterface;
use App\Shared\Application\Event\EventBusInterface;
use Psr\Clock\ClockInterface;

/**
 * Cas d'usage : orchestre, ne décide rien. Les règles sont dans l'agrégat.
 */
final readonly class PlaceOrderCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private OrderRepository $orders,
        private RestaurantCatalog $catalog,
        private DeliveryFeeCalculator $deliveryFees,
        private EventBusInterface $eventBus,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(PlaceOrderCommand $command): OrderId
    {
        $now = $this->clock->now();
        $restaurantId = RestaurantId::fromString($command->restaurantId);

        $order = Order::draft($this->orders->nextIdentity(), CustomerId::fromString($command->customerId), $restaurantId);

        foreach ($command->dishes as $dishId => $quantity) {
            $order->addLine($this->catalog->dish($dishId), Quantity::of($quantity));
        }

        $order->place(
            DeliverySlot::startingAt($command->deliverySlotStart, $now),
            $this->catalog->termsOf($restaurantId),
            $this->deliveryFees,
            $now,
        );

        $this->orders->save($order);
        $this->eventBus->publish(...$order->releaseEvents());

        return $order->id();
    }
}
