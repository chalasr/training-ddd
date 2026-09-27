<?php

namespace App\Service;

use Doctrine\DBAL\Connection;
use Psr\Clock\ClockInterface;

/**
 * Ce qu'il reste de l'ancien OrderService : l'annulation client (R6) et l'expiration (R5),
 * pas encore migrées vers l'agrégat Order. Elles écrivent directement dans la table, en contournant
 * l'agrégat : aucun invariant vérifié par le domaine, aucun événement enregistré.
 */
class OrderService
{
    public function __construct(
        private Connection $connection,
        private ClockInterface $clock,
    ) {
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

    /**
     * Appelé toutes les minutes par un cron.
     */
    public function expireUnansweredOrders(): int
    {
        return (int) $this->connection->executeStatement(
            'UPDATE ordering_order SET status = ? WHERE status = ? AND placed_at < ?',
            ['cancelled', 'placed', $this->clock->now()->modify('-5 minutes')->format('Y-m-d H:i:s')],
        );
    }
}
