<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Handler;

/**
 * The handler table — code wins (Q1: declarations live here, in code; the
 * Redis hash stays the ops-level cache, not the source of truth).
 *
 * Ordering contract: first-match-wins in priority order, then registration
 * order (PHP 8's stable sort). `on()` returns $this for fluency; every
 * registration is one immutable ``Handler`` record, so the table is
 * reviewable like a route file and compile-time crash-free.
 */
final class HandlerRegistry
{
    /** @var list<Handler> */
    private array $handlers = [];

    /**
     * Register one match → invokable. Match grammar (zero-regex):
     *  - `*`              catch-all (also the `onMessage()` surface)
     *  - `Foo`            exact constructor name
     *  - `FooBar*`        constructor-name prefix
     *
     * @param string|array|\Closure $handler PSR-11-addressable invokable
     */
    public function on(string $match, string|array|\Closure $handler, int $priority = 0, bool $onOwn = false): static
    {
        $this->handlers[] = new Handler($match, $handler, $priority, $onOwn);

        return $this;
    }

    /**
     * Subscribe to every update — the ``onMessage()``-style fan-in surface
     * (spec D1 first; ``onCommand``-type sugar layers on later, non-breaking).
     */
    public function onMessage(string|array|\Closure $handler, int $priority = 0): static
    {
        return $this->on('*', $handler, $priority);
    }

    /**
     * All registrations, high priority first; equal priorities keep
     * registration order (stable sort, PHP >= 8).
     *
     * @return list<Handler>
     */
    public function all(): array
    {
        $handlers = $this->handlers;
        usort($handlers, static fn (Handler $a, Handler $b) => $b->priority <=> $a->priority);

        return $handlers;
    }

    /**
     * Compiled snapshot — already sorted; in-memory for this phase (the
     * Redis hash cache / route:cache-style artifact is post-Phase-3).
     *
     * @return list<Handler>
     */
    public function compile(): array
    {
        return $this->all();
    }

    /** Registering hand-observed invokables is introspection-only below. */
    public function count(): int
    {
        return count($this->handlers);
    }
}