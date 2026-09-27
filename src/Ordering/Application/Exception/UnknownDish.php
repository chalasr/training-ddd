<?php

declare(strict_types=1);

namespace App\Ordering\Application\Exception;

final class UnknownDish extends \DomainException
{
    public static function withId(string $dishId): self
    {
        return new self(sprintf('Le plat %s n\'existe pas ou n\'est plus disponible.', $dishId));
    }
}
