<?php

declare(strict_types=1);

namespace App\Tests\Functional;

final class OrderJourneyTest extends ConsoleTestCase
{
    public function test_a_customer_places_an_order_and_the_restaurant_accepts_it(): void
    {
        self::runCommand('app:catalog:seed');

        $place = self::runCommand('app:order:place', [
            'customer' => 'alice',
            'restaurant' => 'chez-ginette',
            'slot' => self::tomorrowAt('12:30'),
            'dishes' => ['blanquette:1', 'tarte-tatin:2'],
        ]);
        $this->assertCommandIsSuccessful($place);
        self::assertStringContainsString('Total : 32,40 €', $place->getDisplay());

        $orderId = self::orderIdIn($place->getDisplay());

        $this->assertCommandIsSuccessful(self::runCommand('app:order:accept', ['order' => $orderId]));

        $show = self::runCommand('app:order:show', ['order' => $orderId]);
        $this->assertCommandIsSuccessful($show);
        self::assertMatchesRegularExpression('/Statut\s+: accepted/', $show->getDisplay());
    }
}
