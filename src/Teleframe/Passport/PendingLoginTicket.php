<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

/**
 * PendingLoginTicket — the ONE thing an anonymous login start hands out, and
 * the reason an anonymous login can be bound to a browser session at all.
 *
 * THE PROBLEM THIS SOLVES. `PassportLoginService` binds a login to a web
 * session by comparing the session PRESENTING the callback against the userId
 * the consumed nonce was issued for. Both halves are integers that mean "a
 * Laravel user". An anonymous visitor has no Laravel user, so there is nothing
 * to put in that slot — and the tempting fix is exactly the vulnerability the
 * existing check exists to close: drop the comparison, and let the nonce (or
 * the flow token, or the email claim) name whoever gets logged in.
 *
 * THE SHAPE THAT WORKS INSTEAD. A login that has been STARTED but not yet
 * FINISHED is represented by a *pending marker*: an unforgeable 128-bit
 * random token, bound at issue time to
 *
 *   1. a FINGERPRINT of the browser session that started the trip, and
 *   2. a DIGEST of the one-time passport nonce minted for that trip.
 *
 * The token is the handle a callback must present; the marker is what proves
 * the handle belongs to this browser and this nonce. Because the marker
 * exists at all, the callback has something non-optional to compare against —
 * a random value the caller cannot choose, minted server-side, before any
 * Telegram traffic existed. That is the whole trick: not "a weaker check",
 * but a real second half for the binding the anonymous case was missing.
 *
 * WHY THE FINGERPRINT AND NOT THE SESSION ID. A Laravel session id IS a
 * credential (AGENTS.md): it is a bearer value for the session, and writing
 * one into a shared cache store puts a usable session in every other key of
 * that store. Only a SHA-256 digest of it is persisted here, and the digest
 * is not accepted by anything — it can prove "this is the same session" and
 * can do nothing else.
 *
 * Plain-PHP, zero-regex, two readonly strings.
 */
final class PendingLoginTicket
{
    public function __construct(
        /** 128-bit random hex: the handle the callback must present. */
        private readonly string $token,
        /** 128-bit random hex: the passport nonce that leaves in the request. */
        private readonly string $nonce,
    ) {}

    /**
     * The handle to hand back to the browser (as a `flow` parameter on the
     * callback URL, or a field on the callback POST). It is a bearer value
     * for a *pending* login and nothing else: on its own it authenticates
     * nobody, because a marker that has not been matched against this
     * browser's session and this trip's nonce is inert.
     */
    public function token(): string
    {
        return $this->token;
    }

    /**
     * The one-time passport nonce, handed to Telegram inside the
     * `requestPassport` argument list. NOT persisted by the registry — only
     * its digest is (see {@see PendingLoginBinding::nonceDigest()}), so a
     * store dump cannot be used to finish somebody else's login.
     */
    public function nonce(): string
    {
        return $this->nonce;
    }
}
