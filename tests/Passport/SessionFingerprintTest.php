<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Passport;

use MeRezaRezaei\Teleframe\Passport\PassportLoginException;
use MeRezaRezaei\Teleframe\Passport\SessionFingerprint;
use PHPUnit\Framework\TestCase;

/**
 * SessionFingerprint — the ONE place the "which session?" formula lives.
 *
 * This class exists because two callers each rolling their own
 * `hash('sha256', $id)` is the same class of bug as the takeovers this module
 * survived: the check exists in two spellings and one of them is wrong. So the
 * tests here are about the FORMULA, not about a call site.
 *
 * The load-bearing test is the blank one. `hash('sha256', '')` is a CONSTANT:
 * every sessionless request on earth would produce it. A version of this method
 * that digested the empty string would hand every caller a "fingerprint" that
 * every other caller without a session also has, so a marker bound to it would
 * bind to nobody in particular — a check that reads like a binding and is worth
 * nothing. The first draft of this class documented that refusal and did not
 * implement it, which is exactly the failure this file exists to prevent.
 */
final class SessionFingerprintTest extends TestCase
{
    public function test_a_fingerprint_is_the_digest_of_the_session_id(): void
    {
        self::assertSame(hash('sha256', 'session-a'), SessionFingerprint::of('session-a'));
    }

    public function test_different_sessions_have_different_fingerprints(): void
    {
        self::assertNotSame(
            SessionFingerprint::of('session-a'),
            SessionFingerprint::of('session-b'),
        );
    }

    public function test_the_fingerprint_is_stable_for_one_session(): void
    {
        self::assertSame(SessionFingerprint::of('session-a'), SessionFingerprint::of('session-a'));
    }

    public function test_a_fingerprint_is_never_the_session_id_itself(): void
    {
        $id = 'a-real-laravel-session-id';

        self::assertNotSame($id, SessionFingerprint::of($id));
        self::assertStringNotContainsString($id, SessionFingerprint::of($id));
    }

    /**
     * THE ONE THAT MATTERS. A blank session id is REFUSED, not digested.
     *
     * Without this, `of('')` would return the same constant to every caller that
     * failed to obtain a session — which is the state an unauthenticated request
     * is in — and the anonymous login's binding would compare a constant with a
     * constant and wave everything through.
     */
    public function test_a_blank_session_id_is_refused_rather_than_digested(): void
    {
        $refused = false;
        try {
            SessionFingerprint::of('');
        } catch (PassportLoginException $e) {
            $refused = true;
            self::assertStringContainsString('blank', $e->getMessage());
        }

        self::assertTrue($refused, 'a blank session id must be refused, never hashed into a shared constant');
    }

    public function test_matches_is_an_equality_not_a_prefix_or_a_containment(): void
    {
        $a = SessionFingerprint::of('session-a');
        $b = SessionFingerprint::of('session-b');

        self::assertTrue(SessionFingerprint::matches($a, $a));
        self::assertFalse(SessionFingerprint::matches($a, $b));
        self::assertFalse(SessionFingerprint::matches($a, ''), 'an absent fingerprint matches nothing');
        self::assertFalse(SessionFingerprint::matches('', ''), 'not even the empty pair');
        self::assertFalse(
            SessionFingerprint::matches($a, substr($a, 0, 63)),
            'a prefix of a fingerprint is not a fingerprint',
        );
    }
}
