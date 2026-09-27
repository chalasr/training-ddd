<?php

declare(strict_types=1);

namespace App\Tests\Functional;

final class DeliveryJourneyTest extends ConsoleTestCase
{
    public function test_a_run_is_proposed_as_soon_as_the_restaurant_accepts_an_order(): void
    {
        self::runCommand('app:catalog:seed');
        $orderId = self::orderIdIn(self::runCommand('app:order:place', [
            'customer' => 'alice',
            'restaurant' => 'chez-ginette',
            'slot' => self::tomorrowAt('12:30'),
            'dishes' => ['blanquette:1'],
        ])->getDisplay());

        self::assertStringContainsString('Aucune course', self::runCommand('app:delivery:runs')->getDisplay());

        self::runCommand('app:order:accept', ['order' => $orderId]);

        $runs = self::runCommand('app:delivery:runs')->getDisplay();
        self::assertStringContainsString('commande(s) '.$orderId, $runs);
        self::assertStringContainsString('à livrer avant '.self::tomorrowAt('12:45'), $runs);
    }
}
