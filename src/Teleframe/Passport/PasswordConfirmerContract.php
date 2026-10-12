<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

/**
 * PasswordConfirmerContract (Cycle 18, 2026-09-25) — the ONE thing the wire
 * login needs from the 2FA step, stated as a contract.
 *
 * It exists for one reason: `PASSWORD_REQUIRED` has to be routed to the
 * existing 2FA chain, and routing has to be TESTABLE without a socket. The
 * shipped `PasswordConfirmer` is `final` and its `confirm()` runs the real SRP
 * chain against a live `UserAccountScope` — correct for production, and the
 * wrong thing to reach for in a test. Depending on this contract instead lets
 * a test substitute the 2FA step while the PRODUCTION binding stays the very
 * same `PasswordConfirmer` instance the webhook path uses.
 *
 * Adding an interface to a shipped final class changes no behaviour and no
 * method: PasswordConfirmer still runs `account.getPassword` →
 * `compute2faProof` → `auth.checkPassword`, still logs at error, still
 * rethrows. Its 2FA role is untouched.
 */
interface PasswordConfirmerContract
{
    /**
     * Re-confirm the user's 2FA cloud password over the SRP chain.
     *
     * @return array<string, mixed> the auth.checkPassword result
     *
     * @throws PassportLoginException when the chain cannot run or fails
     */
    public function confirm(string $password): array;
}
