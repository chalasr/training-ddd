<?php

declare(strict_types=1);

namespace App\Ordering\Domain\ValueObject;

use Webmozart\Assert\Assert;

final readonly class Quantity
{
    private function __construct(public int $value)
    {
        Assert::greaterThan($value, 0, 'Une quantité est strictement positive.');
    }

    public static function of(int $value): self
    {
        return new self($value);
    }

    public function add(self $other): self
    {
        return new self($this->value + $other->value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
