<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Handler;

/**
 * Container-bound context (Q18): during a dispatch the current uprate is
 * available to any handler that type-hints this class, without globals or
 * long argument lists. Bound in the delegate container per dispatch (the
 * PSR-11 delegate wire — Laravel app's container or the standalone one).
 */
final class TelegramContext
{
    public function __construct(
        public readonly Update $update,
    ) {
    }

    public function accountId(): int
    {
        return $this->update->accountId;
    }
}