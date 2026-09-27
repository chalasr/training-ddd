<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Model;

enum CancellationReason: string
{
    case RestaurantDidNotAnswer = 'restaurant_did_not_answer';
}
