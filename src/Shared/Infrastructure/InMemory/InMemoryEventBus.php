<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\InMemory;

use App\Shared\Application\Event\EventBusInterface;

/**
 * Pour les tests des cas d'usage : garde les événements publiés pour les inspecter.
 */
final class InMemoryEventBus implements EventBusInterface
{
    /** @var list<object> */
    public private(set) array $published = [];

    public function publish(object ...$events): void
    {
        foreach ($events as $event) {
            $this->published[] = $event;
        }
    }
}
