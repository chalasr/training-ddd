<?php

declare(strict_types=1);

namespace App\Ordering\Domain\ValueObject;

use Webmozart\Assert\Assert;

/**
 * Horaires d'ouverture d'un restaurant sur une journée, au format "HH:MM".
 */
final readonly class OpeningHours
{
    private function __construct(
        public string $opensAt,
        public string $closesAt,
    ) {
        Assert::regex($opensAt, '/^([01]\d|2[0-3]):[0-5]\d$/');
        Assert::regex($closesAt, '/^([01]\d|2[0-3]):[0-5]\d$/');
        Assert::true($opensAt < $closesAt, 'Un restaurant ferme après avoir ouvert.');
    }

    public static function between(string $opensAt, string $closesAt): self
    {
        return new self($opensAt, $closesAt);
    }

    public function covers(DeliverySlot $slot): bool
    {
        return $slot->start->format('Y-m-d') === $slot->end()->format('Y-m-d')
            && $slot->start->format('H:i') >= $this->opensAt
            && $slot->end()->format('H:i') <= $this->closesAt;
    }
}
