<?php

declare(strict_types=1);

namespace App\Ordering\Application\Command;

use App\Shared\Application\Command\CommandInterface;

final readonly class PlaceOrderCommand implements CommandInterface
{
    /**
     * @param array<string, int> $dishes identifiant du plat => quantité
     */
    public function __construct(
        public string $customerId,
        public string $restaurantId,
        public \DateTimeImmutable $deliverySlotStart,
        public array $dishes,
    ) {
    }
}
