<?php

declare(strict_types=1);

namespace App\Shared\Application\Command;

interface CommandBusInterface
{
    /**
     * Transmet la commande à son handler et renvoie ce qu'il renvoie (souvent rien, parfois un identifiant).
     */
    public function dispatch(CommandInterface $command): mixed;
}
