<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Handler;

/**
 * The middleware onion — the phase's named 26-line chain (spec §5 Phase 3,
 * gap §I). ``array_reduce`` over ``array_reverse`` folds every middleware so
 * the terminal runs only after each layer calls ``$next``; any layer may
 * short-circuit by returning without calling ``$next`` (echo elimination
 * does exactly that).
 *
 * A middleware signature is ``fn (Update $update, callable $next): mixed`` —
 * Nutgram-style, but our own pipeline running the merged modules' updates.
 */
final class Pipeline
{
    /**
     * Build the composed runner.
     *
     * @param list<callable> $middleware
     * @param callable(Update): mixed $terminal
     *
     * @return callable(Update): mixed
     */
    public function then(array $middleware, callable $terminal): callable
    {
        $runner = $terminal;

        foreach (array_reverse($middleware) as $layer) {
            $runner = fn (Update $update): mixed => $layer($update, $runner);
        }

        return $runner;
    }
}