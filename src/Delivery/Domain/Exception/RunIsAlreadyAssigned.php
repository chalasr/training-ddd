<?php

declare(strict_types=1);

namespace App\Delivery\Domain\Exception;

use App\Delivery\Domain\ValueObject\CourierId;
use App\Delivery\Domain\ValueObject\RunId;

final class RunIsAlreadyAssigned extends \DomainException
{
    public static function to(RunId $runId, CourierId $courierId): self
    {
        return new self(sprintf('La course %s est déjà attribuée au coursier %s.', $runId, $courierId));
    }
}
