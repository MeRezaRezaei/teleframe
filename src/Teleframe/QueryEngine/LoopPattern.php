<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\QueryEngine;

final class LoopPattern
{
    public string $type;

    /** @var list<string> */
    public array $nodes;

    /** @var list<string> */
    public array $cycle;

    /** @var list<string> */
    public array $breakers;

    public float $severity;

    public string $description;

    /** @var array<string, mixed> */
    public array $metadata;

    /** @var list<mixed> */
    public array $bindings;

    public function __construct(
        string $type = 'unknown',
        array $nodes = [],
        array $cycle = [],
        array $breakers = [],
        float $severity = 0.0,
        string $description = '',
        array $metadata = [],
        array $bindings = [],
    ) {
        $this->type = $type;
        $this->nodes = array_values($nodes);
        $this->cycle = array_values($cycle === [] ? $nodes : $cycle);
        $this->breakers = array_values($breakers);
        $this->severity = $severity;
        $this->description = $description;
        $this->metadata = $metadata;
        $this->bindings = array_values($bindings);
    }

    public static function mutualRecursion(array $nodes, array $breakers = [], float $severity = 1.0, array $bindings = []): self
    {
        return new self('mutual_recursion', $nodes, $nodes, $breakers, $severity, 'Mutual recursion detected', [], $bindings);
    }

    public static function chain(array $nodes, array $breakers = [], float $severity = 0.5, array $bindings = []): self
    {
        return new self('chain', $nodes, $nodes, $breakers, $severity, 'Chain detected', [], $bindings);
    }

    public static function broadcast(array $nodes, array $breakers = [], float $severity = 0.4, array $bindings = []): self
    {
        return new self('broadcast', $nodes, $nodes, $breakers, $severity, 'Broadcast detected', [], $bindings);
    }

    public function __get(string $name): mixed
    {
        return match ($name) {
            'kind', 'pattern' => $this->type,
            'members', 'cycleNodes' => $this->cycle,
            'length' => count($this->cycle),
            'recommendedBreakers' => $this->breakers,
            default => null,
        };
    }
}
