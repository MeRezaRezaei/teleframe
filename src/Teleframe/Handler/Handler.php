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
 *
 * ``tag`` is the hot-registration pass-through (Cycle-5): any string a
 * caller wants to hang on the record — a kind marker, a worker/route label —
 * defaulting to null (no tag = current behavior). Nothing in the pipeline
 * reads it; it is identity + introspection data for consumers of the shared
 * hot table.
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
        /** Opaque hot-registration marker; null when untagged. */
        public readonly ?string $tag = null,
    ) {}

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
        $tag = $this->tag !== null ? "\0label=".$this->tag : '';

        if ($this->handler instanceof \Closure) {
            return 'closure:'.(++self::$closureSeq).$tag;
        }

        if (is_array($this->handler)) {
            return implode('::', $this->handler).$tag;
        }

        return $this->handler.$tag;
    }
}
