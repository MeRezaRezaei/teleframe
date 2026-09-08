<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Handler;

/**
 * Pure matcher over the registry — the router half of the two-stage split
 * (Q4): match the raw constructor name now, hydrate models later via DI in
 * the handler. Same zero-regex grammar as the bus ``RouteTable``: exact,
 * trailing ``*`` prefix, bare ``*`` catch-all, first-match-wins.
 */
final class HandlerMatcher
{
    public function __construct(
        private readonly HandlerRegistry $registry,
    ) {
    }

    public function match(string $constructor): ?Handler
    {
        foreach ($this->registry->all() as $handler) {
            if (self::patternMatches($handler->match, $constructor)) {
                return $handler;
            }
        }

        return null;
    }

    private static function patternMatches(string $pattern, string $constructor): bool
    {
        if ($pattern === '*') {
            return true;
        }

        if (str_ends_with($pattern, '*')) {
            return str_starts_with($constructor, substr($pattern, 0, -1));
        }

        return $pattern === $constructor;
    }
}