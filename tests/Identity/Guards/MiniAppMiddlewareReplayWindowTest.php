<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Identity\Guards;

use MeRezaRezaei\Teleframe\Laravel\Http\Middleware\VerifyMiniAppInitData;
use PHPUnit\Framework\TestCase;

/**
 * THE MIDDLEWARE MUST REJECT A REPLAYED initData, NOT JUST THE GUARD.
 *
 * A valid HMAC proves Telegram signed a string. It says nothing about WHEN.
 * For a long time the replay window lived in exactly one place —
 * `TgWebAppGuard::isFreshEnough()` — while `VerifyMiniAppInitData`, the class
 * behind the `tg.miniapp` middleware alias the package advertises and its own
 * stub Mini App documents, verified the HMAC and stopped there.
 *
 * The capability therefore existed, was configured, and was tested, while the
 * advertised HTTP path had no replay protection at all: a captured initData URL
 * — from browser history, a proxy log, a referrer, a screenshot — stayed valid
 * for ever. `IdentityConfig` even documented that the two layers "must agree
 * with it word for word". They did not.
 *
 * These tests pin the middleware path, which is the one that was open.
 */
final class MiniAppMiddlewareReplayWindowTest extends TestCase
{
    private const TOKEN = '123456789:ABCdefGHIjklMNOpqrsTUVwxyz1234567';

    // ---- the gap that was open -----------------------------------------

    public function test_the_middleware_rejects_a_correctly_signed_but_stale_init_data(): void
    {
        // Signed by the REAL bot token, so the HMAC is valid — and signed two
        // days ago. This is the replay. Before the fix the middleware returned
        // the user and the request sailed through.
        $stale = self::signed(self::TOKEN, time() - (2 * 86400));

        self::assertNull(
            (new VerifyMiniAppInitData)->validateInitData($stale, self::TOKEN),
            'a captured initData must stop authenticating once it ages out',
        );
    }

    public function test_the_middleware_rejects_a_signed_init_data_with_no_auth_date(): void
    {
        $noDate = self::signedWithout(self::TOKEN, 'auth_date');

        self::assertNull(
            (new VerifyMiniAppInitData)->validateInitData($noDate, self::TOKEN),
            'an absent auth_date cannot be shown to be fresh, so it must not authenticate',
        );
    }

    public function test_the_middleware_rejects_a_signed_init_data_with_a_nonsense_auth_date(): void
    {
        foreach (['not-a-number', '', '-1'] as $bad) {
            self::assertNull(
                (new VerifyMiniAppInitData)->validateInitData(self::signedWithout(self::TOKEN, 'auth_date', $bad), self::TOKEN),
                'auth_date='.var_export($bad, true).' must not authenticate',
            );
        }
    }

    public function test_the_middleware_rejects_a_far_future_auth_date(): void
    {
        // A future auth_date is not "very fresh", it is a clock-skew or forgery
        // attempt. Without this, a forged string could claim a timestamp the
        // staleness check can never reach.
        $future = self::signed(self::TOKEN, time() + 86_400);

        self::assertNull(
            (new VerifyMiniAppInitData)->validateInitData($future, self::TOKEN),
            'a far-future auth_date must not authenticate',
        );
    }

    // ---- duplicate / malformed auth_date: the attacks, empirically -------
    //
    // auth_date is a query-string key, so an attacker controlling the URL can
    // simply include it twice. The parser keeps the LAST occurrence, but that
    // does not help them: the freshness gate reads the SAME $params array the
    // hash was computed over, so whichever value wins is the one Telegram
    // signed. Swapping in a fresh one changes the data-check string and the
    // hash no longer matches. Verified both orders, because "last wins" makes
    // the two directions behave differently and only one of them is obvious.

    public function test_a_duplicated_auth_date_cannot_be_used_to_replay(): void
    {
        $stale = time() - (2 * 86400);
        $signed = self::signed(self::TOKEN, $stale);

        // Fresh value appended after the signed stale one: the stale one still
        // wins the parse, so freshness sees stale and refuses.
        self::assertNull(
            (new VerifyMiniAppInitData)->validateInitData($signed.'&auth_date='.time(), self::TOKEN),
            'appending a fresh auth_date must not resurrect a stale signed payload',
        );

        // Fresh value prepended: now the FRESH one wins the parse, which
        // changes the data-check string, so the hash no longer matches.
        self::assertNull(
            (new VerifyMiniAppInitData)->validateInitData('auth_date='.time().'&'.$signed, self::TOKEN),
            'prepending a fresh auth_date must break the HMAC rather than pass freshness',
        );
    }

    public function test_a_negative_auth_date_is_refused(): void
    {
        // time() - (-1) is a large FUTURE age, so a signed negative timestamp
        // would pass a naive "older than max" check. The direction of the
        // comparison is what stops it.
        $params = ['auth_date' => '-1', 'query_id' => 'q', 'user' => '{"id":1}'];

        self::assertNull(
            (new VerifyMiniAppInitData)->validateInitData(self::sign(self::TOKEN, $params), self::TOKEN),
            'a negative auth_date is not "very old", it is malformed, and must not authenticate',
        );
    }

    // ---- the window still works both ways -------------------------------

    public function test_the_middleware_still_accepts_a_fresh_signature(): void
    {
        $fresh = self::signed(self::TOKEN, time());

        $validated = (new VerifyMiniAppInitData)->validateInitData($fresh, self::TOKEN);

        self::assertNotNull($validated, 'a fresh, correctly signed initData must still authenticate');
        self::assertSame(987654321, $validated['id']);
    }

    public function test_a_host_whose_clock_lags_telegram_is_not_locked_out(): void
    {
        // Small skew in the operator's favour is tolerated; a whole day is not.
        $slightlyAhead = self::signed(self::TOKEN, time() + 30);

        self::assertNotNull(
            (new VerifyMiniAppInitData)->validateInitData($slightlyAhead, self::TOKEN),
            'a few seconds of clock skew must not break a legitimate Mini App',
        );
    }

    public function test_the_hmac_is_still_the_first_gate(): void
    {
        // Freshness must never be what rejects a forged payload, and a forged
        // payload must not be rescued by a plausible auth_date.
        $params = [
            'auth_date' => (string) time(),
            'user' => (string) json_encode(['id' => 987654321, 'username' => 'johndoe']),
        ];
        ksort($params);
        $initData = http_build_query(array_merge($params, ['hash' => 'deadbeef']));

        self::assertNull((new VerifyMiniAppInitData)->validateInitData($initData, self::TOKEN));
    }

    public function test_the_window_is_the_configured_one(): void
    {
        $inside = time() - 1700; // under the 1800s default
        $outside = time() - 1900; // over it

        self::assertTrue(VerifyMiniAppInitData::isFreshEnough(['auth_date' => (string) $inside]));
        self::assertFalse(VerifyMiniAppInitData::isFreshEnough(['auth_date' => (string) $outside]));
    }

    // ---- helpers ---------------------------------------------------------

    /** A correctly signed initData, authenticated at $authDate. */
    private static function signed(string $token, int $authDate): string
    {
        return self::sign($token, [
            'auth_date' => (string) $authDate,
            'query_id' => 'AAHdF6IQAAAAAN0XohBSnE5c',
            'user' => (string) json_encode([
                'id' => 987654321,
                'first_name' => 'John',
                'last_name' => 'Doe',
                'username' => 'johndoe',
                'language_code' => 'en',
            ]),
        ]);
    }

    /** A correctly signed initData with $omit absent, or present but $replaceWith. */
    private static function signedWithout(string $token, string $omit, string $replaceWith = ''): string
    {
        $params = [
            'query_id' => 'AAHdF6IQAAAAAN0XohBSnE5c',
            'user' => (string) json_encode(['id' => 987654321, 'username' => 'johndoe']),
        ];

        if ($replaceWith !== '') {
            $params[$omit] = $replaceWith;
        }

        return self::sign($token, $params);
    }

    /** @param array<string, string> $params */
    private static function sign(string $token, array $params): string
    {
        ksort($params);

        $check = [];
        foreach ($params as $k => $v) {
            $check[] = $k.'='.$v;
        }

        $secretKey = hash_hmac('sha256', $token, 'WebAppData', true);
        $hash = hash_hmac('sha256', implode("\n", $check), $secretKey);

        return http_build_query(array_merge($params, ['hash' => $hash]));
    }
}
