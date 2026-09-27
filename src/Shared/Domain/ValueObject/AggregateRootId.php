<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

/**
 * Identifiant d'agrégat : un UUID v7 (ordonné dans le temps), généré côté application et non par la base.
 * S'utilise dans un value object dédié : final readonly class OrderId { use AggregateRootId; }
 */
trait AggregateRootId
{
    final private function __construct(public readonly string $value)
    {
        Assert::uuid($value);
    }

    public static function generate(): self
    {
        return new self(Uuid::v7()->toRfc4122());
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
