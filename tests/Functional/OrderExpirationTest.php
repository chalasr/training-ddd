<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Ordering\Application\Command\ExpireOrderIfNotAnsweredCommand;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

/**
 * R5 de bout en bout : la validation programme une vérification 5 minutes plus tard,
 * que le worker (messenger:consume) exécute à l'échéance.
 */
final class OrderExpirationTest extends ConsoleTestCase
{
    public function test_an_order_is_cancelled_when_the_restaurant_does_not_answer_within_five_minutes(): void
    {
        $orderId = $this->placeAnOrder();

        $scheduled = $this->asyncTransport()->getSent();
        self::assertCount(1, $scheduled);
        self::assertInstanceOf(ExpireOrderIfNotAnsweredCommand::class, $scheduled[0]->getMessage());
        self::assertSame(5 * 60 * 1000, $scheduled[0]->last(DelayStamp::class)?->getDelay());

        $this->mockClock()->sleep(5 * 60 + 1);
        self::runCommand('messenger:consume', ['receivers' => ['async'], '--limit' => 1, '--time-limit' => 2]);

        self::assertMatchesRegularExpression('/Statut\s+: cancelled/', self::runCommand('app:order:show', ['order' => $orderId])->getDisplay());
    }

    public function test_an_accepted_order_is_left_untouched_when_the_check_runs(): void
    {
        $orderId = $this->placeAnOrder();
        self::runCommand('app:order:accept', ['order' => $orderId]);

        $this->mockClock()->sleep(5 * 60 + 1);
        self::runCommand('messenger:consume', ['receivers' => ['async'], '--limit' => 1, '--time-limit' => 2]);

        self::assertMatchesRegularExpression('/Statut\s+: accepted/', self::runCommand('app:order:show', ['order' => $orderId])->getDisplay());
    }

    private function placeAnOrder(): string
    {
        self::runCommand('app:catalog:seed');

        return self::orderIdIn(self::runCommand('app:order:place', [
            'customer' => 'alice',
            'restaurant' => 'chez-ginette',
            'slot' => self::tomorrowAt('12:30'),
            'dishes' => ['blanquette:1'],
        ])->getDisplay());
    }

    private function asyncTransport(): InMemoryTransport
    {
        $transport = self::getContainer()->get('messenger.transport.async');
        \assert($transport instanceof InMemoryTransport);

        return $transport;
    }

    private function mockClock(): MockClock
    {
        $clock = self::clock();
        \assert($clock instanceof MockClock);

        return $clock;
    }
}
