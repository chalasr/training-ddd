<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Model;

use App\Ordering\Domain\ValueObject\Dish;
use App\Ordering\Domain\ValueObject\Money;
use App\Ordering\Domain\ValueObject\Quantity;

/**
 * Entité interne à l'agrégat Order : son identité (le plat) n'a de sens qu'à l'intérieur d'une commande.
 * On ne la manipule qu'à travers Order, jamais directement.
 */
final class OrderLine
{
    private string $dishId;
    private string $dishName;
    private Money $unitPrice;

    /**
     * @internal seul Order crée des lignes
     */
    public function __construct(
        Dish $dish,
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
