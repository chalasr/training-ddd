<?php

declare(strict_types=1);

namespace App\Delivery\Domain\ValueObject;

use Webmozart\Assert\Assert;

final readonly class CourierId implements \Stringable
{
    private function __construct(public string $value)
    {
        Assert::stringNotEmpty($value);
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
