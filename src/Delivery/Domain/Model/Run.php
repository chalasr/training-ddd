<?php

declare(strict_types=1);

namespace App\Delivery\Domain\Model;

use App\Delivery\Domain\Exception\RunCannotCarryMoreThanTwoOrders;
use App\Delivery\Domain\Exception\RunIsAlreadyAssigned;
use App\Delivery\Domain\ValueObject\CourierId;
use App\Delivery\Domain\ValueObject\OrderReference;
use App\Delivery\Domain\ValueObject\RunId;
use App\Shared\Domain\AggregateRoot;
use Doctrine\ORM\Mapping as ORM;

/**
 * Agrégat Course : un trajet d'un coursier, qui transporte une ou deux commandes (R7).
 */
#[ORM\Entity]
#[ORM\Table(name: 'delivery_run')]
final class Run extends AggregateRoot
{
    public const int MAXIMUM_ORDERS = 2;

    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    private array $orders;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $courierId = null;

    private function __construct(
        #[ORM\Embedded(columnPrefix: false)]
        private readonly RunId $id,
        OrderReference $firstOrder,
        #[ORM\Column]
        private readonly \DateTimeImmutable $deliverBy,
    ) {
        $this->orders = [$firstOrder->value];
    }

    /**
     * Une course est proposée dès qu'un restaurant accepte une commande (R7).
     */
    public static function propose(RunId $id, OrderReference $order, \DateTimeImmutable $deliverBy): self
    {
        return new self($id, $order, $deliverBy);
    }

    public function addOrder(OrderReference $order): void
    {
        if (\count($this->orders) >= self::MAXIMUM_ORDERS) {
            throw RunCannotCarryMoreThanTwoOrders::for($this->id);
        }

        $this->orders[] = $order->value;
    }

    public function assignTo(CourierId $courierId): void
    {
        if (null !== $this->courierId) {
            throw RunIsAlreadyAssigned::to($this->id, CourierId::fromString($this->courierId));
        }

        $this->courierId = $courierId->value;
    }

    public function id(): RunId
    {
        return $this->id;
    }

    /**
     * @return list<OrderReference>
     */
    public function orders(): array
    {
        return array_map(OrderReference::fromString(...), $this->orders);
    }

    public function deliverBy(): \DateTimeImmutable
    {
        return $this->deliverBy;
    }

    public function courierId(): ?CourierId
    {
        return null === $this->courierId ? null : CourierId::fromString($this->courierId);
    }
}
