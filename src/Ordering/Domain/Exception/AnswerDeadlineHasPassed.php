<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Exception;

use App\Ordering\Domain\ValueObject\OrderId;

/**
 * R5 : le restaurant accepte ou refuse dans les 5 minutes.
 */
final class AnswerDeadlineHasPassed extends \DomainException
{
    public static function for(OrderId $orderId, \DateTimeImmutable $deadline): self
    {
        return new self(sprintf('Le délai de réponse pour la commande %s a expiré à %s.', $orderId, $deadline->format('H:i:s')));
    }
}
