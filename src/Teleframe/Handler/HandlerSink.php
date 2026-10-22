<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Handler;

use MeRezaRezaei\Teleframe\Core\Contracts\UpdateSinkInterface;

/**
 * The bus intake row funnel (friction I.1): a sink that feeds raw updates
 * into the SAME handler pipeline as the event path. Host apps point a
 * routed target stream (or the consumer's ``onRouted`` seam) at this sink —
 * one payload shape, one pipeline, two transport rows.
 */
final class HandlerSink implements UpdateSinkInterface
{
    public function __construct(
        private readonly UpdateDispatcher $dispatcher,
    ) {
    }

    /**
     * `$ts` is the bus entry's own timestamp. It was accepted and then never
     * threaded through, so `Update::updateId()` hashed `''` for the timestamp
     * component on every bus dispatch (NEW-1, 2026-10-17). The bus entry carries
     * it (`StreamSchema::CANONICAL_KEYS`), so dropping it here was a pure loss.
     */
    public function handle(array $update, ?string $source = null, ?int $ts = null): bool
    {
        $this->dispatcher->dispatch(Update::fromBus($update, (int) (string) $source, $ts));

        return true;
    }
}