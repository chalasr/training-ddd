<?php

declare(strict_types=1);

namespace App\Ordering\PublishedLanguage;

/**
 * Published Language : le contrat public que la Prise de commande offre aux autres contextes.
 *
 * - Il appartient à Ordering (l'upstream), comme un package "Ordering.Contracts" en .NET.
 * - Uniquement des scalaires : sérialisable, versionnable, sans aucun type interne d'Ordering.
 * - Il change rarement et prudemment ; le modèle interne (OrderAccepted, Order...) reste libre d'évoluer.
 */
final readonly class OrderAcceptedIntegrationEvent
{
    public function __construct(
        public string $orderId,
        public string $restaurantId,
        public string $customerId,
        /** ISO 8601 */
        public string $deliverySlotStart,
        /** ISO 8601 */
        public string $deliverySlotEnd,
        /** ISO 8601 */
        public string $acceptedAt,
    ) {
    }
}
