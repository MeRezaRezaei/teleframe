<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Handler;

/**
 * A registered handler, as data (unification spec D2): an immutable record
 * of {match, invokable, priority} plus a deterministic content-hash id.
 *
 * The invokable is PSR-11-addressable: an invokable class name, a
 * ``[Class::class, 'method']`` pair, or a plain closure. The dispatcher
 * resolves it lazily through the container at dispatch time, exactly once
 * per update (route matching stays separate — two-stage, Q4).
 */
final class Handler
{
    /** Per-process monotonic counter for closure id fallback. */
    private static int $closureSeq = 0;

    public function __construct(
        public readonly string $match,
        public readonly string|array|\Closure $handler,
        public readonly int $priority = 0,
        /** Escape hatch for the Q2 echo eliminator: run even on self-echoes. */
        public readonly bool $onOwn = false,
    ) {
    }

    /**
     * Deterministic content-hash id (Q16-style): stable across deploys and
     * processes for string/pair callables. Closures have no stable bytes, so
     * they fall back to a per-process ordinal — unique within this process,
     * stable for the length of a run.
     */
    public function id(): string
    {
        return substr(hash('sha256', $this->hashable()), 0, 16);
    }

    private function hashable(): string
    {
        if ($this->handler instanceof \Closure) {
            return 'closure:' . (++self::$closureSeq);
        }

        if (is_array($this->handler)) {
            return implode('::', $this->handler);
        }

        return $this->handler;
    }
}