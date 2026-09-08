<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Handler;

/**
 * Pure matcher over the registry — the router half of the two-stage split
 * (Q4): match the raw constructor name now, hydrate models later via DI in
 * the handler. Same zero-regex grammar as the bus ``RouteTable``: exact,
 * trailing ``*`` prefix, bare ``*`` catch-all, plus a single ``%s`` sscanf
 * token (spec 5a §2). First-match-wins.
 */
final class HandlerMatcher
{
    public function __construct(
        private readonly HandlerRegistry $registry,
    ) {
    }

    public function match(string $constructor): ?HandlerMatch
    {
        foreach ($this->registry->all() as $handler) {
            $args = self::extractArgs($handler->match, $constructor);
            if ($args !== null) {
                return new HandlerMatch($handler, $args);
            }
        }

        return null;
    }

    /**
     * Extract sscanf args when the pattern matches, else null.
     *
     * @return list<string>|null
     */
    private static function extractArgs(string $pattern, string $constructor): ?array
    {
        if ($pattern === '*') {
            return [];
        }

        if (str_ends_with($pattern, '*')) {
            return str_starts_with($constructor, substr($pattern, 0, -1)) ? [] : null;
        }

        if (! str_contains($pattern, '%s')) {
            return $pattern === $constructor ? [] : null;
        }

        $captured = null;
        $matched = sscanf($constructor, $pattern, $captured);

        return $matched > 0 ? [(string) $captured] : null;
    }
}