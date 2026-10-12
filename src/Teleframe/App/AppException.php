<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\App;

use RuntimeException;
use Throwable;

/**
 * AppException (design §2) — ONE loud domain exception for the whole app
 * module: an unknown app name at resolve() (the BotMap::for() posture),
 * a duplicate define() against the first-exact-match registry, a refused
 * transport, a failed schedule install. Loud, never silent — the caller
 * (facade / CLI / host) owns the recovery; nothing in the module swallows.
 *
 * Constructor order is message → previous → code (the repo's domain-base
 * pattern: BackupException / NotifyException); the standard RuntimeException
 * argument order sits behind it.
 */
final class AppException extends RuntimeException
{
    public function __construct(string $message, ?Throwable $previous = null, int $code = 0)
    {
        parent::__construct($message, $code, $previous);
    }
}
