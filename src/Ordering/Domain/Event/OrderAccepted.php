<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Event;

use App\Ordering\Domain\ValueObject\CustomerId;
use App\Ordering\Domain\ValueObject\DeliverySlot;
use App\Ordering\Domain\ValueObject\OrderId;
use App\Ordering\Domain\ValueObject\RestaurantId;
use App\Shared\Domain\Event\DomainEvent;

final readonly class OrderAccepted implements DomainEvent
{
    public function __construct(
        public OrderId $orderId,
        public RestaurantId $restaurantId,
        public CustomerId $customerId,
        public DeliverySlot $deliverySlot,
        public \DateTimeImmutable $acceptedAt,
    ) {
    }
}
