<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\QueryEngine;

use MeRezaRezaei\Teleframe\Handler\Update;
use MeRezaRezaei\Teleframe\Telegram\MultiSession\QueryEngine\QueryContract as BaseQueryContract;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class BinderRegistry
{
    /** @var array<string, array{contract: mixed, preconditions: array, postconditions: array, breakCycle: bool, metadata: array}> */
    private array $bindings = [];

    private LoggerInterface $logger;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger;
    }

    public function bind(mixed $contract, array $preconditions = [], array $postconditions = [], bool $breakCycle = false, array $metadata = []): BindingId
    {
        if ($contract instanceof Binding) {
            $preconditions = $contract->preconditions;
            $postconditions = $contract->postconditions;
            $breakCycle = $contract->breakCycle;
            $metadata = $contract->metadata;
            $contract = $contract->contract;
        }

        $id = new BindingId;
        $this->logger->debug('BinderRegistry bind', ['bindingId' => (string) $id]);
        $this->bindings[(string) $id] = [
            'contract' => $contract,
            'preconditions' => $preconditions,
            'postconditions' => $postconditions,
            'breakCycle' => $breakCycle,
            'metadata' => $metadata,
        ];

        return $id;
    }

    public function register(mixed $contract, array $preconditions = [], array $postconditions = [], bool $breakCycle = false, array $metadata = []): BindingId
    {
        return $this->bind($contract, $preconditions, $postconditions, $breakCycle, $metadata);
    }

    public function add(mixed $contract, array $preconditions = [], array $postconditions = [], bool $breakCycle = false, array $metadata = []): BindingId
    {
        return $this->bind($contract, $preconditions, $postconditions, $breakCycle, $metadata);
    }

    public function addBinding(mixed $contract, array $preconditions = [], array $postconditions = [], bool $breakCycle = false, array $metadata = []): BindingId
    {
        return $this->bind($contract, $preconditions, $postconditions, $breakCycle, $metadata);
    }

    public function registerBinding(mixed $contract, array $preconditions = [], array $postconditions = [], bool $breakCycle = false, array $metadata = []): BindingId
    {
        return $this->bind($contract, $preconditions, $postconditions, $breakCycle, $metadata);
    }

    public function unbind(BindingId|string $id): void
    {
        unset($this->bindings[(string) $id]);
    }

    public function get(mixed $id): ?Binding
    {
        $key = (string) $id;
        if (! isset($this->bindings[$key])) {
            return null;
        }
        $row = $this->bindings[$key];

        return new Binding(
            contract: $row['contract'],
            preconditions: $row['preconditions'],
            postconditions: $row['postconditions'],
            breakCycle: $row['breakCycle'],
            metadata: $row['metadata'],
            id: $key,
        );
    }

    public function getBinding(mixed $id): ?Binding
    {
        return $this->get($id);
    }

    public function getBindingData(mixed $id): ?array
    {
        return $this->bindings[(string) $id] ?? null;
    }

    public function getAllBindings(): array
    {
        $result = [];
        foreach ($this->bindings as $key => $row) {
            $result[$key] = $row['contract'];
        }

        return $result;
    }

    public function all(): array
    {
        return $this->getAllBindings();
    }

    public function bindings(): array
    {
        return $this->getAllBindings();
    }

    public function getBindings(): array
    {
        return $this->getAllBindings();
    }

    public function count(): int
    {
        return count($this->bindings);
    }

    /** @return list<Binding> */
    public function allBindings(): array
    {
        $result = [];
        foreach (array_keys($this->bindings) as $key) {
            $b = $this->get($key);
            if ($b !== null) {
                $result[] = $b;
            }
        }

        return $result;
    }

    public function getPreconditions(mixed $id): array
    {
        return $this->bindings[(string) $id]['preconditions'] ?? [];
    }

    public function getPostconditions(mixed $id): array
    {
        return $this->bindings[(string) $id]['postconditions'] ?? [];
    }

    public function hasBreakCycle(mixed $id): bool
    {
        return (bool) ($this->bindings[(string) $id]['breakCycle'] ?? false);
    }

    public function isBreakCycle(mixed $id): bool
    {
        return $this->hasBreakCycle($id);
    }

    public function setPreconditions(mixed $id, array $preconditions): void
    {
        if (isset($this->bindings[(string) $id])) {
            $this->bindings[(string) $id]['preconditions'] = $preconditions;
        }
    }

    public function setPostconditions(mixed $id, array $postconditions): void
    {
        if (isset($this->bindings[(string) $id])) {
            $this->bindings[(string) $id]['postconditions'] = $postconditions;
        }
    }

    public function setBreakCycle(mixed $id, bool $value): void
    {
        if (isset($this->bindings[(string) $id])) {
            $this->bindings[(string) $id]['breakCycle'] = $value;
        }
    }

    public function getBindingsFor(?Update $update = null): array
    {
        if ($update === null) {
            return $this->getAllBindings();
        }
        // Delegate to base? Minimal: return all bindings whose query matches type.
        $result = [];
        foreach ($this->bindings as $key => $row) {
            $result[$key] = $row['contract'];
        }

        return array_values($result);
    }

    public function getCycles(): array
    {
        return [];
    }

    public function recordCycle(mixed $report): void
    {
        // no-op
    }
}
