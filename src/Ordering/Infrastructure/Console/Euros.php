<?php

declare(strict_types=1);

namespace App\Ordering\Infrastructure\Console;

use App\Ordering\Domain\ValueObject\Money;

/**
 * Présentation d'un montant pour la console. L'affichage n'est pas l'affaire du domaine.
 */
final class Euros
{
    public static function format(?Money $money): string
    {
        return number_format(($money->cents ?? 0) / 100, 2, ',', ' ');
    }
}
