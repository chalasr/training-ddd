<?php

declare(strict_types=1);

namespace App\Delivery\Application\EventListener;

use App\Delivery\Domain\Model\Run;
use App\Delivery\Domain\Repository\RunRepository;
use App\Delivery\Domain\ValueObject\OrderReference;
use App\Ordering\PublishedLanguage\OrderAcceptedIntegrationEvent;
use App\Shared\Application\Event\EventListenerInterface;
use Symfony\Component\Clock\DatePoint;

/**
 * La Livraison réagit au contrat publié par la Prise de commande, sans rien connaître de son modèle.
 */
final readonly class CreateRunWhenOrderAccepted implements EventListenerInterface
{
    public function __construct(private RunRepository $runs)
    {
    }

    public function __invoke(OrderAcceptedIntegrationEvent $event): void
    {
        $run = Run::propose(
            $this->runs->nextIdentity(),
            OrderReference::fromString($event->orderId),
            DatePoint::createFromFormat(\DATE_ATOM, $event->deliverySlotEnd),
        );

        $this->runs->save($run);
    }
}
