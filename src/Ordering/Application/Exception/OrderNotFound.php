<?php

declare(strict_types=1);

namespace App\Ordering\Application\Exception;

final class OrderNotFound extends \DomainException
{
    public static function withId(string $orderId): self
    {
        return new self(sprintf('Commande introuvable : %s', $orderId));
    }
}
