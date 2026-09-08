<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generator\Model;

/** One TL constructor: name#crc params = ResultType; */
final class TlConstructor
{
    /** @var list<TlParam> */
    private array $paramList;

    /**
     * @param array<TlParam> $params
     */
    public function __construct(
        public readonly string $name,
        public readonly int $id,
        array $params,
        public readonly string $resultType,
    ) {
        $this->paramList = array_values($params);
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

    public function resultNamespace(): ?string
    {
        return str_contains($this->resultType, '.')
            ? substr($this->resultType, 0, (int) strrpos($this->resultType, '.'))
            : null;
    }

    public function resultShortName(): string
    {
        return str_contains($this->resultType, '.')
            ? substr($this->resultType, (int) strrpos($this->resultType, '.') + 1)
            : $this->resultType;
    }

    /** @return array<string, TlParam> named params (generic fillers excluded), keyed by param name */
    public function params(): array
    {
        $map = [];
        foreach ($this->paramList as $p) {
            if (!$p->isFiller) {
                $map[$p->name] = $p;
            }
        }
        return $map;
    }

    /** @return list<TlParam> all params in declaration order, including generic fillers */
    public function allParams(): array
    {
        return $this->paramList;
    }

    /** Canonical single-spaced form (no trailing ';') used for crc32 verification. */
    public function canonical(): string
    {
        $parts = [$this->name];
        foreach ($this->paramList as $p) {
            $parts[] = $p->token;
        }
        $parts[] = '=';
        $parts[] = $this->resultType;
        return implode(' ', $parts);
    }
}
