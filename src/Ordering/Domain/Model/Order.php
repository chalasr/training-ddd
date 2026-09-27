<?php

declare(strict_types=1);

namespace App\Ordering\Domain\Model;

use App\Ordering\Domain\Event\OrderAccepted;
use App\Ordering\Domain\Event\OrderCancelled;
use App\Ordering\Domain\Event\OrderPlaced;
use App\Ordering\Domain\Event\OrderRejected;
use App\Ordering\Domain\Exception\AnswerDeadlineHasPassed;
use App\Ordering\Domain\Exception\DeliverySlotIsOutsideOpeningHours;
use App\Ordering\Domain\Exception\EmptyOrderCannotBePlaced;
use App\Ordering\Domain\Exception\OrderCannotBeCancelledOnceAccepted;
use App\Ordering\Domain\Exception\OrderCannotBeModified;
use App\Ordering\Domain\Exception\OrderIsBelowMinimumAmount;
use App\Ordering\Domain\Exception\OrderIsNotAwaitingAnswer;
use App\Ordering\Domain\Exception\OrderMustConcernASingleRestaurant;
use App\Ordering\Domain\Service\DeliveryFeeCalculator;
use App\Ordering\Domain\ValueObject\CustomerId;
use App\Ordering\Domain\ValueObject\DeliverySlot;
use App\Ordering\Domain\ValueObject\Dish;
use App\Ordering\Domain\ValueObject\Money;
use App\Ordering\Domain\ValueObject\OrderId;
use App\Ordering\Domain\ValueObject\Quantity;
use App\Ordering\Domain\ValueObject\RestaurantId;
use App\Ordering\Domain\ValueObject\RestaurantTerms;
use App\Shared\Domain\AggregateRoot;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Agrégat Commande : la frontière de cohérence de la Prise de commande.
 * Toute modification passe par ses méthodes métier, qui garantissent R1 à R6.
 */
#[ORM\Entity]
#[ORM\Table(name: 'ordering_order')]
final class Order extends AggregateRoot
{
    public const int ANSWER_DELAY_IN_MINUTES = 5;

    /** @var Collection<int, OrderLine> */
    #[ORM\OneToMany(targetEntity: OrderLine::class, mappedBy: 'order', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $lines;

    #[ORM\Column(length: 20, enumType: OrderStatus::class)]
    private OrderStatus $status = OrderStatus::Draft;

    #[ORM\Embedded(columnPrefix: 'delivery_slot_')]
    private ?DeliverySlot $deliverySlot = null;

    #[ORM\Embedded(columnPrefix: 'delivery_fee_')]
    private ?Money $deliveryFee = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $placedAt = null;

    private function __construct(
        #[ORM\Embedded(columnPrefix: false)]
        private readonly OrderId $id,
        #[ORM\Embedded(columnPrefix: 'customer_')]
        private readonly CustomerId $customerId,
        #[ORM\Embedded(columnPrefix: 'restaurant_')]
        private readonly RestaurantId $restaurantId,
    ) {
        $this->lines = new ArrayCollection();
    }

    public static function draft(OrderId $id, CustomerId $customerId, RestaurantId $restaurantId): self
    {
        return new self($id, $customerId, $restaurantId);
    }

    public function addLine(Dish $dish, Quantity $quantity): void
    {
        $this->assertIsDraft();

        if (!$dish->restaurantId->equals($this->restaurantId)) {
            throw OrderMustConcernASingleRestaurant::cannotAdd($dish, $this->restaurantId);
        }

        foreach ($this->lines as $line) {
            if ($line->dishId() === $dish->id) {
                $line->increaseBy($quantity);

                return;
            }
        }

        $this->lines->add(new OrderLine($this, $dish, $quantity));
    }

    public function removeLine(string $dishId): void
    {
        $this->assertIsDraft();

        foreach ($this->lines as $key => $line) {
            if ($line->dishId() === $dishId) {
                $this->lines->remove($key);
            }
        }
    }

    public function place(DeliverySlot $slot, RestaurantTerms $terms, DeliveryFeeCalculator $deliveryFees, \DateTimeImmutable $now): void
    {
        $this->assertIsDraft();

        if ($this->lines->isEmpty()) {
            throw new EmptyOrderCannotBePlaced();
        }

        if (!$this->subtotal()->isGreaterThanOrEqual($terms->minimumAmount)) {
            throw OrderIsBelowMinimumAmount::of($this->subtotal(), $terms->minimumAmount);
        }

        if (!$terms->openingHours->covers($slot)) {
            throw DeliverySlotIsOutsideOpeningHours::for($slot, $terms->openingHours);
        }

        $this->deliverySlot = $slot;
        $this->deliveryFee = $deliveryFees->feeFor($this->subtotal(), $slot);
        $this->placedAt = $now;
        $this->status = OrderStatus::Placed;

        $this->recordThat(new OrderPlaced($this->id, $this->restaurantId, $this->total(), $this->answerDeadline(), $now));
    }

    public function accept(\DateTimeImmutable $now): void
    {
        $this->assertIsAwaitingAnswer();

        if ($now > $this->answerDeadline()) {
            throw AnswerDeadlineHasPassed::for($this->id, $this->answerDeadline());
        }

        $this->status = OrderStatus::Accepted;

        $this->recordThat(new OrderAccepted($this->id, $this->restaurantId, $this->customerId, $this->placedDeliverySlot(), $now));
    }

    public function reject(\DateTimeImmutable $now): void
    {
        $this->assertIsAwaitingAnswer();

        $this->status = OrderStatus::Rejected;

        $this->recordThat(new OrderRejected($this->id, $now));
    }

    /**
     * R6 : le client annule sans frais tant que le restaurant n'a pas accepté ; ensuite, c'est impossible.
     */
    public function cancel(\DateTimeImmutable $now): void
    {
        if (OrderStatus::Accepted === $this->status) {
            throw OrderCannotBeCancelledOnceAccepted::for($this->id);
        }

        $this->assertIsAwaitingAnswer();

        $this->status = OrderStatus::Cancelled;

        $this->recordThat(new OrderCancelled($this->id, CancellationReason::CustomerRequest, $now));
    }

    /**
     * R5 : sans réponse du restaurant dans les 5 minutes, la commande est annulée et le client remboursé.
     * Appelée à l'échéance ; sans effet si le restaurant a répondu entre-temps.
     */
    public function expireIfNotAnswered(\DateTimeImmutable $now): void
    {
        if (OrderStatus::Placed !== $this->status || $now < $this->answerDeadline()) {
            return;
        }

        $this->status = OrderStatus::Cancelled;

        $this->recordThat(new OrderCancelled($this->id, CancellationReason::RestaurantDidNotAnswer, $now));
    }

    public function id(): OrderId
    {
        return $this->id;
    }

    public function customerId(): CustomerId
    {
        return $this->customerId;
    }

    public function restaurantId(): RestaurantId
    {
        return $this->restaurantId;
    }

    public function status(): OrderStatus
    {
        return $this->status;
    }

    /**
     * @return list<OrderLine>
     */
    public function lines(): array
    {
        return array_values($this->lines->toArray());
    }

    public function subtotal(): Money
    {
        return array_reduce(
            $this->lines(),
            static fn (Money $subtotal, OrderLine $line): Money => $subtotal->add($line->subtotal()),
            Money::zero(),
        );
    }

    public function deliveryFee(): ?Money
    {
        return $this->deliveryFee;
    }

    public function total(): Money
    {
        return $this->subtotal()->add($this->deliveryFee ?? Money::zero());
    }

    public function deliverySlot(): ?DeliverySlot
    {
        return $this->deliverySlot;
    }

    /**
     * R5 : le restaurant a 5 minutes pour répondre après la validation.
     */
    public function answerDeadline(): \DateTimeImmutable
    {
        if (null === $this->placedAt) {
            throw new \LogicException('Une commande non validée n\'a pas de délai de réponse.');
        }

        return $this->placedAt->modify(sprintf('+%d minutes', self::ANSWER_DELAY_IN_MINUTES));
    }

    private function placedDeliverySlot(): DeliverySlot
    {
        return $this->deliverySlot ?? throw new \LogicException('Une commande validée a toujours un créneau.');
    }

    private function assertIsDraft(): void
    {
        if (OrderStatus::Draft !== $this->status) {
            throw OrderCannotBeModified::because($this->id, $this->status);
        }
    }

    private function assertIsAwaitingAnswer(): void
    {
        if (OrderStatus::Placed !== $this->status) {
            throw OrderIsNotAwaitingAnswer::because($this->id, $this->status);
        }
    }
}
