<?php

declare(strict_types=1);

namespace App\Ordering\Domain\ValueObject;

use App\Shared\Domain\ValueObject\AggregateRootId;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final readonly class OrderId implements \Stringable
{
    use AggregateRootId;
}
