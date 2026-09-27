<?php

declare(strict_types=1);

namespace App\Delivery\Domain\ValueObject;

use App\Shared\Domain\ValueObject\AggregateRootId;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final readonly class RunId implements \Stringable
{
    use AggregateRootId;
}
