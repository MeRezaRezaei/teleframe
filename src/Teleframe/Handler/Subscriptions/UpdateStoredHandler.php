<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Handler\Subscriptions;

use MeRezaRezaei\Teleframe\Handler\Update;
use MeRezaRezaei\Teleframe\Handler\UpdateDispatcher;
use MeRezaRezaei\Teleframe\Ingest\Events\UpdateStored;

/**
 * One intake row collapses into the pipeline (friction I.1): the Laravel
 * ``UpdateStored`` event path. Resolvable by the host's container (the
 * provider registers it as the `UpdateStored` listener), so handlers and
 * this bridge share the SAME registry + onion.
 *
 * The event path carries the mirrored model, not the raw array — the
 * constructor key is empty, which matches the ``*`` catch-all surface
 * (`onMessage`); precise constructor routing rides the bus path.
 */
final class UpdateStoredHandler
{
    public function __construct(
        private readonly UpdateDispatcher $dispatcher,
    ) {
    }

    public function __invoke(UpdateStored $stored): void
    {
        $this->dispatcher->dispatch(Update::fromMirror($stored->model, $stored->accountId));
    }
}