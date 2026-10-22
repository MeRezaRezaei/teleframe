<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Support;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Plain-PHP PSR-11 container for tests and the standalone smoke: an array
 * of bindings, container.has/get semantics, no Laravel boot required.
 */
final class ArrayContainer implements ContainerInterface
{
    /** @var array<string, mixed> */
    private array $bindings = [];

    /** @param array<string, mixed> $bindings */
    public function __construct(array $bindings = [])
    {
        $this->bindings = $bindings;
    }

    public function set(string $id, mixed $value): void
    {
        $this->bindings[$id] = $value;
    }

    public function get(string $id): mixed
    {
        if (! $this->has($id)) {
            throw new ServiceNotFound($id);
        }

        return $this->bindings[$id];
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->bindings);
    }
}

final class ServiceNotFound extends \RuntimeException implements NotFoundExceptionInterface
{
    public function __construct(string $id)
    {
        parent::__construct("Service [$id] not found in ArrayContainer.");
    }
}