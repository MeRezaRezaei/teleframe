<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Daemon;

use MeRezaRezaei\Teleframe\Laravel\Services\UpdatePollerService;
use Throwable;

/**
 * UpdatePollerService tuned for supervised polling (plan Phase 3, Task 4).
 *
 * The stock teleframe poller answers every non-fatal error with internal
 * backoff — correct for a self-contained worker, but it makes the loop
 * opaque to a supervisor: FloodWait, DC migration and transport failures
 * would spin invisibly inside pollUser() forever.
 *
 * This subclass inverts the policy: the ONLY internal reaction left is
 * "stop() won the race → break" (a clean return). Everything else
 * propagates to AccountWorker, which owns the supervision semantics
 * (FloodWait-aware interruptible sleep, DC rebuild, 3-strike rethrow).
 */
class AccountPoller extends UpdatePollerService
{
    protected function errorLoopAction(Throwable $e): string
    {
        if (! $this->running) {
            return self::ACTION_BREAK;
        }

        return self::ACTION_RETHROW;
    }
}
