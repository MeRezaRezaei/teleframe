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
        public readonly bool $inStageFlow = false,
    ) {
    }

    public function accountId(): int
    {
        return $this->update->accountId;
    }

    /**
     * True when this exchange is a telegram-paced stage flow (Phase 5e):
     * raised by StageMiddleware for the whole dispatch it consumes.
     */
    public function inStageFlow(): bool
    {
        return $this->inStageFlow;
    }
}