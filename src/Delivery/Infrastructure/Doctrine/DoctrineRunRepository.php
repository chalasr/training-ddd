<?php

declare(strict_types=1);

namespace App\Delivery\Infrastructure\Doctrine;

use App\Delivery\Domain\Model\Run;
use App\Delivery\Domain\Repository\RunRepository;
use App\Delivery\Domain\ValueObject\RunId;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineRunRepository implements RunRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(Run $run): void
    {
        $this->entityManager->persist($run);
        $this->entityManager->flush();
    }

    public function ofId(RunId $id): ?Run
    {
        return $this->entityManager->find(Run::class, $id->value);
    }

    public function all(): array
    {
        return $this->entityManager->getRepository(Run::class)->findBy([], ['deliverBy' => 'ASC']);
    }

    public function nextIdentity(): RunId
    {
        return RunId::generate();
    }
}
