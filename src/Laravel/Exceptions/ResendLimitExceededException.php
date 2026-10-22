<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Exceptions;

use MeRezaRezaei\Teleframe\Core\Exceptions\TelegramException;

/**
 * Refusal to send an (N+1)-th login code within one login attempt.
 *
 * The cap is the whole point: Telegram answers a resend with a flood wait and
 * then with PHONE_CODE_EXPIRED once the hash is spent, so an unbounded resend
 * loop burns a real account's rate budget (PHONE_NUMBER_FLOOD / FLOOD_WAIT_x)
 * instead of helping. Past the cap the only remaining move is a fresh
 * auth.sendCode.
 */
final class ResendLimitExceededException extends TelegramException
{
    public function __construct(public readonly int $resendsUsed, public readonly int $cap)
    {
        parent::__construct(
            "Refusing to resend the login code: {$resendsUsed} of {$cap} resends already used in this login attempt. "
            .'A new code costs a fresh auth.sendCode (and its own rate budget) — restart the login instead of looping.',
            420
        );
    }
}
