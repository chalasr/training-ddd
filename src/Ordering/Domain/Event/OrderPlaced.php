<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Event;

use App\Ordering\Domain\ValueObject\Money;
use App\Ordering\Domain\ValueObject\OrderId;
use App\Ordering\Domain\ValueObject\RestaurantId;
use App\Shared\Domain\Event\DomainEvent;

final readonly class OrderPlaced implements DomainEvent
{
    public function __construct(
        public OrderId $orderId,
        public RestaurantId $restaurantId,
        public Money $total,
        public \DateTimeImmutable $answerDeadline,
        public \DateTimeImmutable $placedAt,
    ) {
    }
}
