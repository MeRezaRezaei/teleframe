<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

/**
 * SessionFingerprint — the ONE place the "which session?" formula lives.
 *
 * An anonymous login binds a pending marker to a browser session by digesting
 * the session id, because the raw id is a credential: it is a bearer value
 * for the session, so persisting it in a shared cache store would leak a
 * usable session alongside every pending login. A digest proves sameness and
 * authorises nothing.
 *
 * This class exists so the writer (`start`) and the checker (`callback`) can
 * never disagree about the formula. Two callers each rolling their own
 * `hash('sha256', $id)` is the same class of bug as the takeovers this module
 * already survived: the check exists, in two spellings, and one of them is
 * wrong. One static function, no state, no container, no regex.
 */
final class SessionFingerprint
{
    private function __construct() {}

    /**
     * The fingerprint of a session id.
     *
     * A BLANK ID THROWS, and that is the load-bearing part of this method.
     * `hash('sha256', '')` is a CONSTANT: every sessionless request in the
     * world would produce it, so a caller that failed to obtain a session
     * would mint a marker bound to "whichever browser also has no session" —
     * and the binding this whole module exists to provide would be a no-op
     * that still reads like a check. Refusing loudly is the only outcome that
     * cannot be mistaken for a session.
     *
     * (This was documented and not implemented in the first draft of this
     * class: the promise lived in the docblock and the code digested the
     * empty string. A caller that checks for a blank id before calling — as
     * `PassportController::sessionFingerprint()` does — was safe, but the
     * method itself was one careless call away from the failure it describes.)
     *
     * @throws PassportLoginException when the id is blank
     */
    public static function of(string $sessionId): string
    {
        if ($sessionId === '') {
            throw new PassportLoginException(
                'a passport login fingerprint needs a real session id: a blank one is refused rather than digested, '
                .'because every sessionless request would otherwise share one fingerprint and the binding would be a no-op'
            );
        }

        return hash('sha256', $sessionId);
    }

    /**
     * Constant-time equality, so a caller comparing two fingerprints cannot be
     * walked one byte at a time. The inputs are digests of values the caller
     * already holds, so this is defence in depth rather than the
     * load-bearing part of the check.
     *
     * A BLANK FINGERPRINT MATCHES NOTHING, and that includes another blank
     * one. `hash_equals('', '')` is true, so without this line the one
     * comparison that could wave two empty values through is the one a
     * half-initialised caller is most likely to reach. It is unreachable today
     * — `of()` refuses a blank id and every registry refuses a blank stored
     * value — and this is the belt to that braces: the check is closed at the
     * comparison as well as at the formula.
     */
    public static function matches(string $expected, string $presented): bool
    {
        if ($expected === '' || $presented === '') {
            return false;
        }

        return hash_equals($expected, $presented);
    }
}
