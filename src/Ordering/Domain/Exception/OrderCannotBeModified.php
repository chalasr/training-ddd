<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Exception;

use App\Ordering\Domain\Model\OrderStatus;
use App\Ordering\Domain\ValueObject\OrderId;

/**
 * R4 : tant que la commande n'est pas validée, le panier est libre. Ensuite, il est figé.
 */
final class OrderCannotBeModified extends \DomainException
{
    public static function because(OrderId $orderId, OrderStatus $status): self
    {
        return new self(sprintf('La commande %s est déjà validée (statut : %s), son contenu est figé.', $orderId, $status->value));
    }
}
