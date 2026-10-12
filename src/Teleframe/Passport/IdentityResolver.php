<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

use Illuminate\Database\Eloquent\Model;

/**
 * Decrypted passport payload + consumed-nonce session userId → the Laravel User model.
 *
 * The identity seam (design §2/§4 — data / rules layer, right of the
 * orchestrator): `PassportLoginService` asks ONE question and this interface
 * answers it, so the orchestrator stays Eloquent-free and plain-PHP
 * constructible (the standalone-smoke gate). Null = unresolved → the service
 * throws `PassportLoginException` loud, never silent-drop.
 *
 * ONE RULE, TWO IMPLEMENTATIONS (reconciled 2026-09-26 — see
 * `FieldIdentityResolver` for the incident). The identity of a passport login
 * is the web session the consumed nonce was issued to, and NOTHING ELSE may
 * decide it:
 * - session (the shipped default): `SessionIdentityResolver` resolves the user
 *   by that id with a plain class/key lookup. No PII matching, fully
 *   offline-testable. This is the strong property and it is the default.
 * - field: `FieldIdentityResolver` looks a payload email/phone up in the users
 *   table, but ONLY as a cross-check: it refuses outright when the id it was
 *   handed is null, and refuses a match whose row is not that same user. It
 *   adds evidence, never authority.
 *
 * So `$sessionUserId` is not an optional hint: an implementation that returns a
 * user for a null id, or for a session it was not given, is reintroducing an
 * account takeover — the nonce travels through Telegram and can appear in a
 * URL, a referrer log or a proxy log, so it proves the payload is intact and
 * never who is presenting it.
 *
 * Plain-PHP contract: no container, facade, or config() calls in
 * implementations (the session default is a bare Eloquent static lookup);
 * the payload is evidence for the login, never stored.
 */
interface IdentityResolver
{
    /**
     * Resolve the Laravel User for a passport login attempt, or null when
     * unresolved (the service then throws).
     *
     * @param  PassportPayload  $payload  Decrypted, nonce-presence-enforced payload.
     * @param  int|null  $sessionUserId  The userId the consumed nonce was issued
     *                                   for. Null means the caller could not
     *                                   prove who is presenting the login; an
     *                                   implementation MUST return null rather
     *                                   than fall back to a payload claim.
     */
    public function resolve(PassportPayload $payload, ?int $sessionUserId): ?Model;
}
