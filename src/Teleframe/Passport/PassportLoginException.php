<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

use RuntimeException;
use Throwable;

/**
 * PassportLoginException — ONE loud domain exception for the passport login
 * module (design §2): malformed credentials, a decrypted payload without a
 * nonce, a refused passport type, a missing required field. Loud, never
 * silent — the caller (facade / CLI / host) owns the recovery; nothing in
 * the module swallows.
 *
 * Constructor order is message → previous → code (the repo's domain-base
 * pattern: AppException / BackupException); the standard RuntimeException
 * argument order sits behind it.
 *
 * NOT final since 2026-09-25 (Cycle 18): the module's ONE base for the host to
 * catch. The wire path's two typed refusals — `PasswordRequiredException`
 * (Telegram's PASSWORD_REQUIRED, routed to the 2FA confirmer) and
 * `PassportFieldErrorsException` (per-field secureValueError) — extend it, so
 * `catch (PassportLoginException)` still catches every passport refusal while
 * a caller who cares can narrow on the type.
 */
class PassportLoginException extends RuntimeException
{
    public function __construct(string $message, ?Throwable $previous = null, int $code = 0)
    {
        parent::__construct($message, $code, $previous);
    }
}
