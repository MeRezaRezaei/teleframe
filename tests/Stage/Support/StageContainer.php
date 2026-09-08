<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Stage\Support;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * PSR-11 delegate with set()/delete() so StageMiddleware can raise AND clear
 * its container flag between dispatches (ArrayContainer can only set).
 */
final class StageContainer implements ContainerInterface
{
    /** @var array<string, mixed> */
    private array $entries = [];

    public function set(string $key, mixed $value): void
    {
        $this->entries[$key] = $value;
    }

    public function delete(string $key): void
    {
        unset($this->entries[$key]);
    }

    public function get(string $id): mixed
    {
        if (! $this->has($id)) {
            throw new class ($id) extends \Exception implements NotFoundExceptionInterface {
                public function __construct(string $id)
                {
                    parent::__construct("Container entry [{$id}] is not bound.");
                }
            };
        }

        return $this->entries[$id];
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->entries);
    }
}