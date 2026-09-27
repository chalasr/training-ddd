<?php

declare(strict_types=1);

namespace App\Ordering\Application\EventListener;

use App\Ordering\Domain\Event\OrderAccepted;
use App\Ordering\PublishedLanguage\OrderAcceptedIntegrationEvent;
use App\Shared\Application\Event\EventBusInterface;
use App\Shared\Application\Event\EventListenerInterface;

/**
 * Traduit l'événement interne du domaine en contrat public (Published Language).
 */
final readonly class PublishIntegrationEventWhenOrderAccepted implements EventListenerInterface
{
    public function __construct(private EventBusInterface $eventBus)
    {
    }

    public function __invoke(OrderAccepted $event): void
    {
        $this->eventBus->publish(new OrderAcceptedIntegrationEvent(
            orderId: $event->orderId->value,
            restaurantId: $event->restaurantId->value,
            customerId: $event->customerId->value,
            deliverySlotStart: $event->deliverySlot->start->format(\DATE_ATOM),
            deliverySlotEnd: $event->deliverySlot->end()->format(\DATE_ATOM),
            acceptedAt: $event->acceptedAt->format(\DATE_ATOM),
        ));
    }
}
