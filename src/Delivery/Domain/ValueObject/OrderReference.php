<?php

declare(strict_types=1);

namespace App\Delivery\Domain\ValueObject;

use Webmozart\Assert\Assert;

/**
 * La commande telle que la Livraison la voit : un colis à transporter, identifié par une référence.
 * Rien à voir avec l'agrégat Order de la Prise de commande (même mot, autre contexte).
 */
final readonly class OrderReference implements \Stringable
{
    private function __construct(public string $value)
    {
        Assert::stringNotEmpty($value);
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
