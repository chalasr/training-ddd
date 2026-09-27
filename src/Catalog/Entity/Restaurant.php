<?php

namespace App\Catalog\Entity;

use App\Catalog\Repository\RestaurantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RestaurantRepository::class)]
#[ORM\Table(name: 'catalog_restaurant')]
class Restaurant
{
    #[ORM\Id]
    #[ORM\Column(length: 36)]
    private ?string $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /** Montant minimum de commande, en centimes, hors frais de livraison. */
    #[ORM\Column]
    private int $minimumOrderAmount = 0;

    /** Format "HH:MM". */
    #[ORM\Column(length: 5)]
    private ?string $openingTime = null;

    /** Format "HH:MM". */
    #[ORM\Column(length: 5)]
    private ?string $closingTime = null;

    #[ORM\Column]
    private bool $active = true;

    /** @var Collection<int, Dish> */
    #[ORM\OneToMany(targetEntity: Dish::class, mappedBy: 'restaurant', cascade: ['persist'], orphanRemoval: true)]
    private Collection $dishes;

    /** @var Collection<int, Review> */
    #[ORM\OneToMany(targetEntity: Review::class, mappedBy: 'restaurant', cascade: ['persist'], orphanRemoval: true)]
    private Collection $reviews;

    public function __construct()
    {
        $this->dishes = new ArrayCollection();
        $this->reviews = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getMinimumOrderAmount(): int
    {
        return $this->minimumOrderAmount;
    }

    public function setMinimumOrderAmount(int $minimumOrderAmount): static
    {
        $this->minimumOrderAmount = $minimumOrderAmount;

        return $this;
    }

    public function getOpeningTime(): ?string
    {
        return $this->openingTime;
    }

    public function setOpeningTime(string $openingTime): static
    {
        $this->openingTime = $openingTime;

        return $this;
    }

    public function getClosingTime(): ?string
    {
        return $this->closingTime;
    }

    public function setClosingTime(string $closingTime): static
    {
        $this->closingTime = $closingTime;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @return Collection<int, Dish>
     */
    public function getDishes(): Collection
    {
        return $this->dishes;
    }

    public function addDish(Dish $dish): static
    {
        if (!$this->dishes->contains($dish)) {
            $this->dishes->add($dish);
            $dish->setRestaurant($this);
        }

        return $this;
    }

    public function removeDish(Dish $dish): static
    {
        $this->dishes->removeElement($dish);

        return $this;
    }

    /**
     * @return Collection<int, Review>
     */
    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    public function addReview(Review $review): static
    {
        if (!$this->reviews->contains($review)) {
            $this->reviews->add($review);
            $review->setRestaurant($this);
        }

        return $this;
    }

    public function getAverageRating(): ?float
    {
        if ($this->reviews->isEmpty()) {
            return null;
        }

        $sum = 0;
        foreach ($this->reviews as $review) {
            $sum += $review->getRating();
        }

        return round($sum / $this->reviews->count(), 1);
    }
}
