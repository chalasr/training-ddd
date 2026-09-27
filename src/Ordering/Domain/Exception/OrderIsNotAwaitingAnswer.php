<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Exception;

use App\Ordering\Domain\Model\OrderStatus;
use App\Ordering\Domain\ValueObject\OrderId;

/**
 * Le restaurant ne répond (accepte ou refuse) qu'à une commande validée qui attend sa réponse.
 */
final class OrderIsNotAwaitingAnswer extends \DomainException
{
    public static function because(OrderId $orderId, OrderStatus $status): self
    {
        return new self(sprintf('La commande %s n\'attend pas de réponse du restaurant (statut : %s).', $orderId, $status->value));
    }
}
