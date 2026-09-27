<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Model;

enum OrderStatus: string
{
    case Draft = 'draft';
    case Placed = 'placed';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
}
