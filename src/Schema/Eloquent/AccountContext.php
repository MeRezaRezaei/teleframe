<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Eloquent;

use Illuminate\Container\Container;

/**
 * Container singleton that carries the "current" account id for the
 * duration of a request or scoped callback.  Static API delegates to the
 * singleton instance resolved from the container.
 *
 * Falls back to `config('teleframe.primary_account_id')` when no explicit
 * current has been set and a container + config are available.
 */
final class AccountContext
{
    private ?int $current = null;

    /** @var list<int> Nesting stack so `for()` can restore the previous value. */
    private array $stack = [];

    public static function current(): ?int
    {
        $instance = self::getInstance();

        return $instance->current ?? self::configDefault();
    }

    /**
     * Temporarily set the current account to $accountId, run $cb, then
     * restore whatever was active before.
     *
     * @template T
     *
     * @param callable(): T $cb
     *
     * @return T
     */
    public static function for(int $accountId, callable $cb): mixed
    {
        $instance   = self::getInstance();
        $previous   = $instance->current ?? self::configDefault();

        $instance->stack[] = $previous;
        $instance->current = $accountId;

        try {
            return $cb();
        } finally {
            $instance->current = array_pop($instance->stack);
        }
    }

    public static function set(int $accountId): void
    {
        self::getInstance()->current = $accountId;
    }

    public static function reset(): void
    {
        $instance          = self::getInstance();
        $instance->current = null;
        $instance->stack   = [];
    }

    private static function getInstance(): self
    {
        $container = Container::getInstance();

        if (! $container->bound(self::class)) {
            $container->singleton(self::class, static fn (): self => new self());
        }

        /** @var self */
        return $container->make(self::class);
    }

    private static function configDefault(): ?int
    {
        $container = Container::getInstance();

        if (! $container->bound('config')) {
            return null;
        }

        /** @var int|string|null $value */
        $value = config('teleframe.primary_account_id');

        if ($value === null || $value === '' || $value === 0 || $value === '0') {
            return null;
        }

        return (int) $value;
    }
}
