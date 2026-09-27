<?php

declare(strict_types=1);

namespace App\Ordering\Domain\ValueObject;

use Doctrine\ORM\Mapping as ORM;
use Webmozart\Assert\Assert;

/**
 * Un montant en centimes dans une devise. Jamais de float pour de l'argent.
 */
#[ORM\Embeddable]
final readonly class Money
{
    private function __construct(
        #[ORM\Column]
        public int $cents,
        #[ORM\Column(length: 3)]
        public string $currency,
    ) {
        Assert::greaterThanEq($cents, 0, 'Un montant ne peut pas être négatif.');
        Assert::length($currency, 3, 'Code devise ISO 4217 attendu (ex. EUR).');
    }

    public static function ofCents(int $cents, string $currency = 'EUR'): self
    {
        return new self($cents, $currency);
    }

    public static function zero(string $currency = 'EUR'): self
    {
        return new self(0, $currency);
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->cents + $other->cents, $this->currency);
    }

    public function multiply(int $factor): self
    {
        return new self($this->cents * $factor, $this->currency);
    }

    public function isGreaterThanOrEqual(self $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->cents >= $other->cents;
    }

    public function equals(self $other): bool
    {
        return $this->cents === $other->cents && $this->currency === $other->currency;
    }

    private function assertSameCurrency(self $other): void
    {
        Assert::same($other->currency, $this->currency, 'Impossible de combiner des montants de devises différentes.');
    }
}
