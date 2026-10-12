<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Control;

/**
 * A control-layer filter: sees every RelayMessage in flight and decides
 * whether it passes, is rewritten, or is replaced. Signatures mirror the
 * Handler Pipeline onion (Nutgram-style): call $next to continue, return a
 * RelayMessage to short-circuit.
 */
interface RelayFilter
{
    public function handle(RelayMessage $message, callable $next): RelayMessage;
}
