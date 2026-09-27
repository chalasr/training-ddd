<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Event;

use App\Ordering\Domain\Model\CancellationReason;
use App\Ordering\Domain\ValueObject\OrderId;
use App\Shared\Domain\Event\DomainEvent;

/**
 * Le client est remboursé dans tous les cas d'annulation (R5, R6).
 */
final readonly class OrderCancelled implements DomainEvent
{
    public function __construct(
        public OrderId $orderId,
        public CancellationReason $reason,
        public \DateTimeImmutable $cancelledAt,
    ) {
    }
}
