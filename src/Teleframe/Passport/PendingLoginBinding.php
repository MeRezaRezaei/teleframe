<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

/**
 * PendingLoginBinding — what a one-time consume of a pending marker gives
 * back: the two facts a callback has to earn, not choose.
 *
 * RETURNED BY, AND ONLY BY, {@see PendingLoginRegistry::consume()}. A caller
 * cannot construct one that corresponds to anything real, because the registry
 * holds the only copy of the state and removes it on consume. A null from
 * consume() is the refusal: unknown token, already spent, or expired.
 *
 * The two fields are the ENTIRE security argument of the anonymous login
 * path, so each is deliberately useless on its own:
 *
 *   sessionFingerprint — SHA-256 of the session id that ran `start`. It
 *       names WHO MAY FINISH, and it is compared against a fingerprint
 *       recomputed from the session presenting the callback. A stolen token
 *       presented from another browser is refused here, and the refusal is
 *       not optional: the parameter that carries the comparison in
 *       `PassportAnonymousLoginService::login()` is a non-nullable string,
 *       the same shape of decision the authenticated path made when
 *       `?int $sessionUserId = null` was removed from `login()`.
 *   nonceDigest — SHA-256 of the passport nonce minted at `start`. It ties
 *       the credentials to THIS trip, so credentials harvested from a
 *       different flow (or a flow whose marker is spent) cannot be used
 *       here even from the right browser. The plaintext nonce is never
 *       stored, so this digest cannot be reversed into a usable one.
 *
 * Plain-PHP, zero-regex, two readonly strings.
 */
final class PendingLoginBinding
{
    public function __construct(
        private readonly string $sessionFingerprint,
        private readonly string $nonceDigest,
    ) {}

    /** SHA-256 of the session id `start` ran under. Never the session itself. */
    public function sessionFingerprint(): string
    {
        return $this->sessionFingerprint;
    }

    /** SHA-256 of the passport nonce minted for this flow. */
    public function nonceDigest(): string
    {
        return $this->nonceDigest;
    }
}
