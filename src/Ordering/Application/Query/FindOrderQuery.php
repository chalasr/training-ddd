<?php

declare(strict_types=1);

namespace App\Ordering\Application\Query;

use App\Shared\Application\Query\QueryInterface;

final readonly class FindOrderQuery implements QueryInterface
{
    public function __construct(public string $orderId)
    {
    }
}
