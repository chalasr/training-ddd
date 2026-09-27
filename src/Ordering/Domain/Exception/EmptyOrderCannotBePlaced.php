<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Exception;

final class EmptyOrderCannotBePlaced extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Le panier est vide.');
    }
}
