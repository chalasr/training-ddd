<?php

declare(strict_types=1);

namespace App\Ordering\Application\EventListener;

use App\Ordering\Application\Command\ExpireOrderIfNotAnsweredCommand;
use App\Ordering\Domain\Event\OrderPlaced;
use App\Shared\Application\Command\CommandSchedulerInterface;
use App\Shared\Application\Event\EventListenerInterface;

/**
 * R5 : le domaine fixe l'échéance (OrderPlaced::answerDeadline), l'application programme la vérification.
 */
final readonly class ScheduleExpirationWhenOrderPlaced implements EventListenerInterface
{
    public function __construct(private CommandSchedulerInterface $scheduler)
    {
    }

    public function __invoke(OrderPlaced $event): void
    {
        $this->scheduler->schedule(new ExpireOrderIfNotAnsweredCommand($event->orderId->value), $event->answerDeadline);
    }
}
