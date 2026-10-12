<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Control;

/**
 * The control-layer onion, folded exactly like the Handler Pipeline: each
 * filter may pass the message on ($next), rewrite it (withMethod/withParams)
 * and pass, or short-circuit by returning its own RelayMessage — dropping
 * data before it reaches the client is a filter returning without $next.
 */
final class RelayPipeline
{
    /**
     * @param  list<RelayFilter>  $filters
     * @param  callable(RelayMessage): RelayMessage  $terminal
     * @return callable(RelayMessage): RelayMessage
     */
    public function then(array $filters, callable $terminal): callable
    {
        $runner = $terminal;

        foreach (array_reverse($filters) as $filter) {
            $runner = fn (RelayMessage $message): RelayMessage => $filter->handle($message, $runner);
        }

        return $runner;
    }
}
