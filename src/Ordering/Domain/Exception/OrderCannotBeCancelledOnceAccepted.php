<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Exception;

use App\Ordering\Domain\ValueObject\OrderId;

/**
 * R6 : une fois acceptée par le restaurant, la commande ne peut plus être annulée.
 */
final class OrderCannotBeCancelledOnceAccepted extends \DomainException
{
    public static function for(OrderId $orderId): self
    {
        return new self(sprintf('Le restaurant a déjà accepté la commande %s, elle ne peut plus être annulée.', $orderId));
    }
}
