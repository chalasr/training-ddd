<?php

declare(strict_types=1);

namespace App\Ordering\Application\Query;

use App\Ordering\Domain\Model\Order;
use App\Ordering\Domain\Repository\OrderRepository;
use App\Ordering\Domain\ValueObject\OrderId;
use App\Shared\Application\Query\QueryHandlerInterface;

final readonly class FindOrderQueryHandler implements QueryHandlerInterface
{
    public function __construct(private OrderRepository $orders)
    {
    }

    public function __invoke(FindOrderQuery $query): ?Order
    {
        return $this->orders->ofId(OrderId::fromString($query->orderId));
    }
}
