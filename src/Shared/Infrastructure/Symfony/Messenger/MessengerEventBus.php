<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony\Messenger;

use App\Shared\Application\Event\EventBusInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;

final readonly class MessengerEventBus implements EventBusInterface
{
    public function __construct(#[Autowire(service: 'event.bus')] private MessageBusInterface $eventBus)
    {
    }

    public function publish(object ...$events): void
    {
        foreach ($events as $event) {
            // Traité après la fin de la commande en cours (et donc après l'enregistrement de l'agrégat)
            $this->eventBus->dispatch($event, [new DispatchAfterCurrentBusStamp()]);
        }
    }
}
