<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Handler;

/**
 * The matcher result (spec 5a §2): the matched handler plus any sscanf args
 * extracted from the constructor name. ``arg()`` is the single-``%s``
 * convenience; ``args`` carries the list for forward-compat.
 */
final class HandlerMatch
{
    /**
     * @param list<string> $args
     */
    public function __construct(
        public readonly Handler $handler,
        public readonly array $args = [],
    ) {
    }

    /** The single extracted ``%s`` token, or null when the pattern had none. */
    public function arg(): ?string
    {
        return $this->args[0] ?? null;
    }
}