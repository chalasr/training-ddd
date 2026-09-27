<?php

declare(strict_types=1);

namespace App\Delivery\Infrastructure\InMemory;

use App\Delivery\Domain\Model\Run;
use App\Delivery\Domain\Repository\RunRepository;
use App\Delivery\Domain\ValueObject\RunId;

final class InMemoryRunRepository implements RunRepository
{
    /** @var array<string, Run> */
    private array $runs = [];

    public function save(Run $run): void
    {
        $this->runs[$run->id()->value] = $run;
    }

    public function ofId(RunId $id): ?Run
    {
        return $this->runs[$id->value] ?? null;
    }

    public function all(): array
    {
        return array_values($this->runs);
    }

    public function nextIdentity(): RunId
    {
        return RunId::generate();
    }
}
