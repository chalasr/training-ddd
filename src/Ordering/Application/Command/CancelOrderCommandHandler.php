<?php

declare(strict_types=1);

namespace App\Ordering\Application\Command;

use App\Ordering\Application\Exception\OrderNotFound;
use App\Ordering\Domain\Repository\OrderRepository;
use App\Ordering\Domain\ValueObject\OrderId;
use App\Shared\Application\Command\CommandHandlerInterface;
use App\Shared\Application\Event\EventBusInterface;
use Psr\Clock\ClockInterface;

final readonly class CancelOrderCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private OrderRepository $orders,
        private EventBusInterface $eventBus,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(CancelOrderCommand $command): void
    {
        $order = $this->orders->ofId(OrderId::fromString($command->orderId))
            ?? throw OrderNotFound::withId($command->orderId);

        $order->cancel($this->clock->now());

        $this->orders->save($order);
        $this->eventBus->publish(...$order->releaseEvents());
    }
}
