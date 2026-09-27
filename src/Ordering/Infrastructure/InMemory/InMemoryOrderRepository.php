<?php

declare(strict_types=1);

namespace App\Ordering\Infrastructure\InMemory;

use App\Ordering\Domain\Model\Order;
use App\Ordering\Domain\Repository\OrderRepository;
use App\Ordering\Domain\ValueObject\OrderId;

/**
 * Adaptateur en mémoire du même port : pour les tests des cas d'usage, sans base de données.
 */
final class InMemoryOrderRepository implements OrderRepository
{
    /** @var array<string, Order> */
    private array $orders = [];

    public function save(Order $order): void
    {
        $this->orders[$order->id()->value] = $order;
    }

    public function ofId(OrderId $id): ?Order
    {
        return $this->orders[$id->value] ?? null;
    }

    public function nextIdentity(): OrderId
    {
        return OrderId::generate();
    }
}
