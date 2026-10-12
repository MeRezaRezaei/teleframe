<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generator\Model;

/** An abstract TL type (tagged union of constructors). */
final class TlType
{
    /** @var array<string, TlConstructor> keyed by full constructor name */
    private array $constructors = [];

    public function __construct(public readonly string $name)
    {
    }

    public function addConstructor(TlConstructor $c): void
    {
        $this->constructors[$c->name] = $c;
    }

    /** @return array<string, TlConstructor> */
    public function constructors(): array
    {
        return $this->constructors;
    }

    public function isUnion(): bool
    {
        return $this->constructors !== [];
    }

    public function namespace(): ?string
    {
        return str_contains($this->name, '.')
            ? substr($this->name, 0, (int) strrpos($this->name, '.'))
            : null;
    }

    public function shortName(): string
    {
        return str_contains($this->name, '.')
            ? substr($this->name, (int) strrpos($this->name, '.') + 1)
            : $this->name;
    }
}
