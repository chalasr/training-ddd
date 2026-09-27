<?php

declare(strict_types=1);

namespace App\Ordering\Application\Command;

use App\Shared\Application\Command\CommandInterface;

/**
 * Programmée à la validation de la commande, exécutée 5 minutes plus tard par le worker (R5).
 */
final readonly class ExpireOrderIfNotAnsweredCommand implements CommandInterface
{
    public function __construct(public string $orderId)
    {
    }
}
