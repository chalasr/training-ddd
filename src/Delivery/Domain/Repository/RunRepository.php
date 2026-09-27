<?php

declare(strict_types=1);

namespace App\Delivery\Domain\Repository;

use App\Delivery\Domain\Model\Run;
use App\Delivery\Domain\ValueObject\RunId;

interface RunRepository
{
    public function save(Run $run): void;

    public function ofId(RunId $id): ?Run;

    /**
     * @return list<Run>
     */
    public function all(): array;

    public function nextIdentity(): RunId;
}
