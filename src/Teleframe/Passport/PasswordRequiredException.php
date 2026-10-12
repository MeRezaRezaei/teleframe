<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

use Throwable;

/**
 * PasswordRequiredException (Cycle 18 W3) — Telegram answered a passport call
 * with `PASSWORD_REQUIRED` (RpcErrorCatalog: "A 2FA password must be
 * configured to use Telegram Passport") and the orchestrator could NOT route it
 * to the existing PasswordConfirmer, because no confirmer seam is bound or no
 * 2FA password was supplied.
 *
 * It exists so this refusal is NEVER a silent drop and never an
 * indistinguishable generic failure: the host catches
 * `PassportLoginException` as usual, and this type tells it the one thing that
 * will fix it — supply the 2FA password (or bind a confirmer).
 *
 * Extends PassportLoginException, so the module keeps ONE base type to catch.
 */
final class PasswordRequiredException extends PassportLoginException
{
    /** 400 — the documented rpc code for PASSWORD_REQUIRED (RpcErrorCatalog). */
    public const CODE = 400;

    public function __construct(
        string $message = 'telegram refused the passport call with PASSWORD_REQUIRED — a 2FA password is required (the PasswordConfirmer seam could not complete it)',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $previous, self::CODE);
    }
}
