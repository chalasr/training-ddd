<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Model;

use App\Ordering\Domain\ValueObject\Dish;
use App\Ordering\Domain\ValueObject\Money;
use App\Ordering\Domain\ValueObject\Quantity;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entité interne à l'agrégat Order : son identité (le plat) n'a de sens qu'à l'intérieur d'une commande.
 * On ne la manipule qu'à travers Order, jamais directement.
 */
#[ORM\Entity]
#[ORM\Table(name: 'ordering_order_line')]
final class OrderLine
{
    #[ORM\Id]
    #[ORM\Column(length: 100)]
    private string $dishId;

    #[ORM\Column]
    private string $dishName;

    #[ORM\Embedded(columnPrefix: 'unit_price_')]
    private Money $unitPrice;

    /**
     * @internal seul Order crée des lignes
     */
    public function __construct(
        /** Référence vers la racine, exigée par la persistance ; le domaine ne s'en sert pas. */
        #[ORM\Id]
        #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'lines')]
        #[ORM\JoinColumn(name: 'order_id', referencedColumnName: 'id', nullable: false)]
        private Order $order,
        Dish $dish,
        #[ORM\Embedded(columnPrefix: false)]
        private Quantity $quantity,
    ) {
        $this->dishId = $dish->id;
        $this->dishName = $dish->name;
        $this->unitPrice = $dish->price;
    }

    public function dishId(): string
    {
        return $this->dishId;
    }

    public function dishName(): string
    {
        return $this->dishName;
    }

    public function unitPrice(): Money
    {
        return $this->unitPrice;
    }

    public function quantity(): Quantity
    {
        return $this->quantity;
    }

    public function subtotal(): Money
    {
        return $this->unitPrice->multiply($this->quantity->value);
    }

    /**
     * @internal seul Order modifie ses lignes
     */
    public function increaseBy(Quantity $quantity): void
    {
        $this->quantity = $this->quantity->add($quantity);
    }
}
