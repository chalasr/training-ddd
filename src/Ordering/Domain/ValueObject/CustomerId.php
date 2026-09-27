<?php

declare(strict_types=1);

namespace App\Ordering\Domain\ValueObject;

use Webmozart\Assert\Assert;

/**
 * Référence vers un customer géré par un autre contexte : un identifiant opaque, rien de plus.
 */
final readonly class CustomerId implements \Stringable
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
