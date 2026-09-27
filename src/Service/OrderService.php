<?php

namespace App\Service;

use Doctrine\DBAL\Connection;

/**
 * Ce qu'il reste de l'ancien OrderService : l'annulation client (R6), pas encore migrée vers
 * l'agrégat Order. Elle écrit directement dans la table, en contournant l'agrégat :
 * aucun invariant vérifié par le domaine, aucun événement publié (la Livraison n'en saura rien).
 */
class OrderService
{
    public function __construct(private Connection $connection)
    {
    }

    public function cancelOrder(string $orderId): void
    {
        $status = $this->connection->fetchOne('SELECT status FROM ordering_order WHERE id = ?', [$orderId]);

        if (false === $status) {
            throw new \InvalidArgumentException('Commande introuvable : '.$orderId);
        }
        if ('accepted' == $status) {
            throw new \RuntimeException('Le restaurant a déjà accepté la commande, elle ne peut plus être annulée.');
        }
        if ('cancelled' == $status || 'rejected' == $status) {
            throw new \RuntimeException('Cette commande est déjà annulée.');
        }

        $this->connection->update('ordering_order', ['status' => 'cancelled'], ['id' => $orderId]);

        // TODO: rembourser le client (Stripe)
    }
}
