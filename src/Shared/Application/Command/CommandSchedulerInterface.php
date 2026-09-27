<?php

declare(strict_types=1);

namespace App\Shared\Application\Command;

interface CommandSchedulerInterface
{
    /**
     * Programme l'exécution d'une commande à une date donnée (en asynchrone).
     */
    public function schedule(CommandInterface $command, \DateTimeImmutable $at): void;
}
