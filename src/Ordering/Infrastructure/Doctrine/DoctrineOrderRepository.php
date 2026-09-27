<?php

declare(strict_types=1);

namespace App\Ordering\Infrastructure\Doctrine;

use App\Ordering\Domain\Model\Order;
use App\Ordering\Domain\Repository\OrderRepository;
use App\Ordering\Domain\ValueObject\OrderId;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Adaptateur Doctrine du port OrderRepository.
 */
final readonly class DoctrineOrderRepository implements OrderRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(Order $order): void
    {
        $this->entityManager->persist($order);
        $this->entityManager->flush();
    }

    public function ofId(OrderId $id): ?Order
    {
        return $this->entityManager->find(Order::class, $id->value);
    }

    public function nextIdentity(): OrderId
    {
        return OrderId::generate();
    }
}
