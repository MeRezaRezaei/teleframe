<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\QueryEngine;

use Symfony\Component\Uid\Uuid;

final class BindingId implements \Stringable
{
    public readonly string $id;

    public readonly string $value;

    public readonly string $key;

    public function __construct(string|int|null $id = null)
    {
        $this->id = $id === null
            ? Uuid::v4()->toRfc4122()
            : (string) $id;
        $this->value = $this->id;
        $this->key = $this->id;
    }

    public static function generate(): self
    {
        return new self;
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public static function fromUuid(Uuid|string $uuid): self
    {
        return new self((string) $uuid);
    }

    public static function random(): self
    {
        return self::generate();
    }

    public function toString(): string
    {
        return $this->id;
    }

    public function __toString(): string
    {
        return $this->id;
    }

    public function value(): string
    {
        return $this->id;
    }

    public function getUuid(): Uuid
    {
        return Uuid::fromString($this->id);
    }

    public function uuid(): Uuid
    {
        return $this->getUuid();
    }

    public function equals(self $other): bool
    {
        return $this->id === $other->id;
    }
}
