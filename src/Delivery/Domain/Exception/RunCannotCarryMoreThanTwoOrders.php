<?php

declare(strict_types=1);

namespace App\Delivery\Domain\Exception;

use App\Delivery\Domain\ValueObject\RunId;

/**
 * R7 : un coursier transporte au plus deux commandes à la fois.
 */
final class RunCannotCarryMoreThanTwoOrders extends \DomainException
{
    public static function for(RunId $runId): self
    {
        return new self(sprintf('La course %s transporte déjà %d commandes.', $runId, 2));
    }
}
