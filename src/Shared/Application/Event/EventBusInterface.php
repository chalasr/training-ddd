<?php

declare(strict_types=1);

namespace App\Shared\Application\Event;

interface EventBusInterface
{
    /**
     * Publie des événements. Ils sont traités une fois la commande en cours terminée :
     * un contexte abonné ne voit jamais un fait qui n'a pas été enregistré.
     */
    public function publish(object ...$events): void;
}
