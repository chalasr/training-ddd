<?php

namespace App\Service;

use App\Entity\Dish;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Restaurant;
use App\Repository\DishRepository;
use App\Repository\OrderRepository;
use App\Repository\RestaurantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class OrderService
{
    // Frais de livraison (en centimes)
    const DELIVERY_FEE = 290;
    const FREE_DELIVERY_THRESHOLD = 3000;
    const RUSH_HOUR_SURCHARGE = 100;

    private LoggerInterface $logger;

    public function __construct(
        private EntityManagerInterface $em,
        private OrderRepository $orderRepository,
        private RestaurantRepository $restaurantRepository,
        private DishRepository $dishRepository,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Crée et valide une commande en une seule fois.
     *
     * @param array<string, int> $items dishId => quantité
     */
    public function placeOrder(string $customerId, string $restaurantId, array $items, string $slot): Order
    {
        $restaurant = $this->restaurantRepository->find($restaurantId);
        if (!$restaurant) {
            throw new \InvalidArgumentException('Restaurant introuvable : '.$restaurantId);
        }
        if (!$restaurant->isActive()) {
            throw new \InvalidArgumentException('Ce restaurant ne prend pas de commandes pour le moment.');
        }

        $order = new Order();
        $order->setCustomerId($customerId);
        $order->setRestaurant($restaurant);
        $order->setCreatedAt(new \DateTime());
        $order->setStatus(Order::STATUS_DRAFT);

        foreach ($items as $dishId => $quantity) {
            $this->addItem($order, (string) $dishId, $quantity);
        }

        $this->validateOrder($order, $slot);

        $this->em->persist($order);
        $this->em->flush();

        $this->logger->info('Commande validée', ['order' => $order->getId()]);

        return $order;
    }

    public function addItem(Order $order, string $dishId, int $quantity): void
    {
        if ($order->getStatus() != Order::STATUS_DRAFT) {
            throw new \RuntimeException('La commande est déjà validée, impossible de modifier le panier.');
        }
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantité invalide.');
        }

        $dish = $this->dishRepository->find($dishId);
        if (!$dish instanceof Dish) {
            throw new \InvalidArgumentException('Plat introuvable : '.$dishId);
        }
        if (!$dish->isAvailable()) {
            throw new \InvalidArgumentException('Le plat '.$dish->getName().' n\'est plus disponible.');
        }

        // Une commande ne concerne qu'un seul restaurant
        if ($order->getRestaurant() !== null && $dish->getRestaurant()?->getId() !== $order->getRestaurant()->getId()) {
            throw new \InvalidArgumentException('Tous les plats doivent venir du même restaurant.');
        }

        // Si le plat est déjà dans le panier, on cumule
        foreach ($order->getItems() as $existing) {
            if ($existing->getDish()?->getId() === $dish->getId()) {
                $existing->setQuantity($existing->getQuantity() + $quantity);
                $this->computeTotals($order);

                return;
            }
        }

        $item = new OrderItem();
        $item->setDish($dish);
        $item->setDishName((string) $dish->getName());
        $item->setUnitPrice($dish->getPrice());
        $item->setQuantity($quantity);
        $order->addItem($item);

        $this->computeTotals($order);
    }

    public function removeItem(Order $order, string $dishId): void
    {
        if ($order->getStatus() != Order::STATUS_DRAFT) {
            throw new \RuntimeException('La commande est déjà validée, impossible de modifier le panier.');
        }

        foreach ($order->getItems() as $item) {
            if ($item->getDish()?->getId() === $dishId) {
                $order->removeItem($item);
            }
        }

        $this->computeTotals($order);
    }

    private function validateOrder(Order $order, string $slot): void
    {
        if (count($order->getItems()) == 0) {
            throw new \InvalidArgumentException('Le panier est vide.');
        }

        $restaurant = $order->getRestaurant();
        \assert($restaurant instanceof Restaurant);

        // Montant minimum (hors frais de livraison)
        if ($order->getSubtotal() < $restaurant->getMinimumOrderAmount()) {
            throw new \InvalidArgumentException(sprintf(
                'Le montant minimum de commande pour ce restaurant est de %s €.',
                number_format($restaurant->getMinimumOrderAmount() / 100, 2, ',', ' ')
            ));
        }

        // Créneau
        $start = \DateTime::createFromFormat('Y-m-d H:i', $slot);
        if (!$start) {
            throw new \InvalidArgumentException('Créneau invalide, format attendu : AAAA-MM-JJ HH:MM');
        }
        $start->setTime((int) $start->format('H'), (int) $start->format('i'), 0);

        if ((int) $start->format('i') % 15 != 0) {
            throw new \InvalidArgumentException('Les créneaux commencent à l\'heure, et quart, et demie ou moins le quart.');
        }

        $now = new \DateTime();
        $diff = $start->getTimestamp() - $now->getTimestamp();
        if ($diff < 30 * 60) {
            throw new \InvalidArgumentException('Le créneau doit commencer au moins 30 minutes après la commande.');
        }

        $end = (clone $start)->modify('+15 minutes');

        if ($start->format('H:i') < $restaurant->getOpeningTime() || $end->format('H:i') > $restaurant->getClosingTime()) {
            throw new \InvalidArgumentException('Le restaurant est fermé sur ce créneau.');
        }

        $order->setDeliverySlotStart($start);
        $order->setDeliverySlotEnd($end);

        // Frais de livraison
        $order->setDeliveryFee($this->computeDeliveryFee($order));
        $order->setTotal($order->getSubtotal() + $order->getDeliveryFee());

        $order->setStatus(Order::STATUS_PLACED);
        $order->setPlacedAt($now);
    }

    public function computeTotals(Order $order): void
    {
        $subtotal = 0;
        foreach ($order->getItems() as $item) {
            $subtotal += $item->getUnitPrice() * $item->getQuantity();
        }
        $order->setSubtotal($subtotal);
        $order->setTotal($subtotal + $order->getDeliveryFee());
    }

    private function computeDeliveryFee(Order $order): int
    {
        if ($order->getSubtotal() >= self::FREE_DELIVERY_THRESHOLD) {
            return 0;
        }

        $fee = self::DELIVERY_FEE;

        // Majoration aux heures de pointe (12h-13h30 et 19h-20h30)
        $start = $order->getDeliverySlotStart();
        if ($start) {
            $time = $start->format('H:i');
            if (($time >= '12:00' && $time < '13:30') || ($time >= '19:00' && $time < '20:30')) {
                $fee += self::RUSH_HOUR_SURCHARGE;
            }
        }

        return $fee;
    }

    public function acceptOrder(int $orderId): Order
    {
        $order = $this->getOrder($orderId);

        if ($order->getStatus() != Order::STATUS_PLACED) {
            throw new \RuntimeException('Seule une commande validée peut être acceptée (statut actuel : '.$order->getStatus().').');
        }

        // Le restaurant a 5 minutes pour répondre
        $now = new \DateTime();
        if ($order->getPlacedAt() && $now->getTimestamp() - $order->getPlacedAt()->getTimestamp() > 5 * 60) {
            throw new \RuntimeException('Le délai de réponse de 5 minutes est dépassé.');
        }

        $order->setStatus(Order::STATUS_ACCEPTED);
        $order->setAcceptedAt($now);
        $this->em->flush();

        // TODO: prévenir le service de livraison pour qu'il trouve un coursier
        $this->logger->info('Commande acceptée', ['order' => $order->getId()]);

        return $order;
    }

    public function rejectOrder(int $orderId): Order
    {
        $order = $this->getOrder($orderId);

        if ($order->getStatus() != Order::STATUS_PLACED) {
            throw new \RuntimeException('Seule une commande validée peut être refusée (statut actuel : '.$order->getStatus().').');
        }

        $order->setStatus(Order::STATUS_REJECTED);
        $order->setCancelledAt(new \DateTime());
        $order->setCancellationReason('rejected_by_restaurant');
        $this->em->flush();

        // TODO: rembourser le client (Stripe)
        $this->logger->info('Commande refusée', ['order' => $order->getId()]);

        return $order;
    }

    public function cancelOrder(int $orderId): Order
    {
        $order = $this->getOrder($orderId);

        if ($order->getStatus() == Order::STATUS_ACCEPTED) {
            throw new \RuntimeException('Le restaurant a déjà accepté la commande, elle ne peut plus être annulée.');
        }
        if ($order->getStatus() == Order::STATUS_CANCELLED || $order->getStatus() == Order::STATUS_REJECTED) {
            throw new \RuntimeException('Cette commande est déjà annulée.');
        }

        $order->setStatus(Order::STATUS_CANCELLED);
        $order->setCancelledAt(new \DateTime());
        $order->setCancellationReason('cancelled_by_customer');
        $this->em->flush();

        // TODO: rembourser le client (Stripe)
        $this->logger->info('Commande annulée par le client', ['order' => $order->getId()]);

        return $order;
    }

    /**
     * Appelé toutes les minutes par un cron.
     */
    public function expireUnansweredOrders(): int
    {
        $limit = new \DateTime('-5 minutes');
        $orders = $this->orderRepository->findPlacedBefore($limit);

        foreach ($orders as $order) {
            $order->setStatus(Order::STATUS_CANCELLED);
            $order->setCancelledAt(new \DateTime());
            $order->setCancellationReason('restaurant_timeout');
            // TODO: rembourser le client (Stripe)
        }
        $this->em->flush();

        return count($orders);
    }

    public function getOrder(int $orderId): Order
    {
        $order = $this->orderRepository->find($orderId);
        if (!$order) {
            throw new \InvalidArgumentException('Commande introuvable : '.$orderId);
        }

        return $order;
    }
}
