<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony\Messenger;

use App\Shared\Application\Command\CommandInterface;
use App\Shared\Application\Command\CommandSchedulerInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;

/**
 * La commande part dans le transport "async" (cf. messenger.yaml) avec un délai :
 * c'est le worker (bin/console messenger:consume async) qui l'exécutera à l'échéance.
 */
final readonly class MessengerCommandScheduler implements CommandSchedulerInterface
{
    public function __construct(
        #[Autowire(service: 'command.bus')] private MessageBusInterface $commandBus,
        private ClockInterface $clock,
    ) {
    }

    public function schedule(CommandInterface $command, \DateTimeImmutable $at): void
    {
        $delayInMilliseconds = max(0, ($at->getTimestamp() - $this->clock->now()->getTimestamp()) * 1000);

        $this->commandBus->dispatch($command, [new DelayStamp($delayInMilliseconds), new DispatchAfterCurrentBusStamp()]);
    }
}
