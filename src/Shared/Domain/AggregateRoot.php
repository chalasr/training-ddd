<?php

declare(strict_types=1);

namespace App\Shared\Domain;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Racine d'agrégat : enregistre les événements du domaine survenus pendant une opération.
 * Ils sont publiés par la couche Application après l'enregistrement de l'agrégat.
 */
abstract class AggregateRoot
{
    /** @var list<DomainEvent> */
    private array $recordedEvents = [];

    protected function recordThat(DomainEvent $event): void
    {
        $this->recordedEvents[] = $event;
    }

    /**
     * @return list<DomainEvent>
     */
    public function releaseEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = [];

        return $events;
    }
}
