<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Event;

use App\Ordering\Domain\ValueObject\OrderId;
use App\Shared\Domain\Event\DomainEvent;

final readonly class OrderRejected implements DomainEvent
{
    public function __construct(
        public OrderId $orderId,
        public \DateTimeImmutable $rejectedAt,
    ) {
    }
}
