<?php

declare(strict_types=1);

namespace App\Ordering\Application\Command;

use App\Shared\Application\Command\CommandInterface;

final readonly class AcceptOrderCommand implements CommandInterface
{
    public function __construct(public string $orderId)
    {
    }
}
