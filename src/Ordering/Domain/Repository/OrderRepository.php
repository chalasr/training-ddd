<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Repository;

use App\Ordering\Domain\Model\Order;
use App\Ordering\Domain\ValueObject\OrderId;

/**
 * Port : la collection des commandes, vue du domaine. Aucune notion de SQL ici.
 * Une commande n'est enregistrée qu'une fois validée : le panier vit en mémoire jusque-là.
 */
interface OrderRepository
{
    public function save(Order $order): void;

    public function ofId(OrderId $id): ?Order;

    public function nextIdentity(): OrderId;
}
