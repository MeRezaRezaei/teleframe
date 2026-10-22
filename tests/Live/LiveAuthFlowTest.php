<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Live;

use MeRezaRezaei\Teleframe\Core\MTProto\Client;
use MeRezaRezaei\Teleframe\Core\MTProto\SessionData;
use MeRezaRezaei\Teleframe\Tests\Concerns\RunsLiveTelegram;
use MeRezaRezaei\Teleframe\Warm\LiveEndpointPolicy;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * THE THREE FLOWS THE OPERATOR NAMED AS LOAD-BEARING.
 *
 * ## WHY EACH STEP IS A SEPARATE TEST, AND WHY NOTHING RUNS END TO END
 *
 * The first flow is `auth.sendCode` → `auth.signIn` → `auth.exportLoginToken`
 * → import on a second device. It cannot be one test: `auth.signIn` needs a
 * code a human reads off a phone, and MTProto has no dry run and no way to
 * synthesise that code. So the flow is CUT INTO STEPS, each with its own opt-in,
 * and the operator drives it by running the steps they are ready to run.
 *
 * The consequence is that this file deliberately does NOT thread state from one
 * step to the next. A stateful chain would have to carry the auth key
 * `auth.sendCode` generates and the `phone_code_hash` bound to it between steps —
 * which means writing credentials to a file, a temp dir or a fixture. **That is
 * refused, not worked around.** The intermediate values come from the
 * operator's own `auth.sendCode` run and are passed in through the environment,
 * which is where credentials already belong. The gate's job is to prove the
 * CALL works, not to be the thing that moves the secret around.
 *
 * `test_the_auth_flow_is_never_wired_end_to_end()` asserts that structurally,
 * on this file's own source, so a later "let me just chain these" goes red.
 *
 * ## THE SECOND FLOW HAS NO EVIDENCE OF ANY KIND
 *
 * `auth.importBotAuthorization` is the call the entire bot-over-MTProto story
 * rests on (`src/Bot/Services/BotAccountScope.php:36-44`), and it has never
 * been dialed against a real DC. `VaultMtprotoAnswerer`
 * (`src/Core/MTProto/Transport/VaultMtprotoAnswerer.php:404-407`) answers every
 * `auth.*` locally and in memory, which is exactly why "the offline suite is
 * green" says nothing about it. Only this step can.
 *
 * ## WHAT IS ASSERTED
 *
 * The ANSWER, always. A refused dial, an `rpc_error`, or a locally-fabricated
 * shape is a failure of this gate — never a pass with a note attached.
 */
final class LiveAuthFlowTest extends TestCase
{
    use RunsLiveTelegram;

    // ── FLOW 1, STEP 1 ────────────────────────────────────────────────────

    /**
     * `auth.sendCode` on a real DC, over a real freshly-handshaken auth key.
     *
     * The client is built with an EMPTY auth key, which is what
     * `TeleframeAuthService::sendPhoneCode()` does
     * (`src/Laravel/Services/TeleframeAuthService.php:100-118`): the DH
     * handshake runs, and `phone_code_hash` comes back bound to the key the
     * client just generated. Asserting the hash is present and non-empty proves
     * Telegram minted a code against THIS session.
     *
     * The hash is not printed. It is a live credential for an unfinished login.
     */
    #[Test]
    public function send_code_returns_a_code_hash_bound_to_this_session(): void
    {
        $this->requireLiveStep('auth_send_code');

        $phone = self::liveEnvRequired(
            'TELEFRAME_LIVE_PHONE',
            'the funded account\'s phone number in international format, e.g. +10000000000',
        );

        $dcId = self::liveDcId();

        $client = new Client(
            apiId: (int) self::liveEnvRequired('TELEGRAM_API_ID', 'the api_id the handshake is keyed on'),
            apiHash: self::liveEnvRequired('TELEGRAM_API_HASH', 'the api_hash paired with TELEGRAM_API_ID'),
            session: new SessionData(dcId: $dcId, authKey: ''),
            live: true,
        );

        try {
            $answer = $client->call('auth.sendCode', [
                'phone_number' => $phone,
                'api_id' => $client->apiId,
                'api_hash' => $client->apiHash,
            ]);

            self::assertArrayNotHasKey('_offline', $answer, 'nothing was sent, so nothing was proved');
            self::assertArrayHasKey(
                'phone_code_hash',
                $answer,
                'auth.sendCode must return phone_code_hash; its absence means Telegram did not '
                .'accept the request as sent. The value itself is never printed.',
            );
            self::assertNotSame('', (string) $answer['phone_code_hash']);
        } finally {
            $client->close();
        }

        self::liveAppendReport(
            'auth.sendCode',
            'A code was sent and `phone_code_hash` was returned. The hash is a live credential and is '
            ."deliberately NOT recorded here.\n\nNext: sign in with the code from the phone, then export "
            .'the pending session and the code, and run the `auth_sign_in` step.',
        );
    }

    // ── FLOW 1, STEP 2 ────────────────────────────────────────────────────

    /**
     * `auth.signIn` — the only step that cannot be unattended.
     *
     * It consumes a real code from a real phone. Everything it needs arrives by
     * environment, which is why `auth.sendCode`'s own output is not threaded
     * through this file: `TELEFRAME_LIVE_PENDING_SESSION` must be the session
     * exported immediately after the operator's own `auth.sendCode`, because
     * `phone_code_hash` is bound to the auth key that call generated.
     */
    #[Test]
    public function sign_in_exchanges_the_code_for_a_real_authorization(): void
    {
        $this->requireLiveStep('auth_sign_in');

        $phone = self::liveEnvRequired('TELEFRAME_LIVE_PHONE', 'the phone the code was sent to');
        $code = self::liveEnvRequired(
            'TELEFRAME_LIVE_PHONE_CODE',
            'the code read off the phone. It is consumed on use and is never printed by this gate.',
        );
        $hash = self::liveEnvRequired(
            'TELEFRAME_LIVE_PHONE_CODE_HASH',
            'the phone_code_hash returned by the send-code step, bound to the auth key that minted it',
        );
        $pending = self::liveEnvRequired(
            'TELEFRAME_LIVE_PENDING_SESSION',
            'the session string exported immediately after auth.sendCode — the same auth key the '
            .'phone_code_hash is bound to. Export it from your own run; this gate will not print it.',
        );

        $client = self::liveClientFromSessionString($pending);

        try {
            $answer = $client->call('auth.signIn', [
                'phone_number' => $phone,
                'phone_code_hash' => $hash,
                'phone_code' => $code,
            ]);

            self::assertArrayNotHasKey('_offline', $answer);
            self::assertSame(
                'auth.authorization',
                $answer['_'] ?? null,
                'auth.signIn must return auth.authorization. Any other constructor means the code was '
                .'not accepted — SESSION_PASSWORD_NEEDED (2FA) is a legitimate alternative and is '
                .'named here so it is not mistaken for a wire fault: '.(string) ($answer['_'] ?? 'nothing'),
            );
            self::assertSame(
                'user',
                $answer['user']['_'] ?? null,
                'an authorization carries the account it belongs to; without it the answer was not decoded',
            );
            self::assertArrayHasKey(
                'user',
                $answer,
                'the authorization must carry an account, or Telegram accepted nothing',
            );
        } finally {
            $client->close();
        }

        self::liveAppendReport(
            'auth.signIn',
            "A real code was exchanged for an `auth.authorization` carrying the account's user id. "
            ."The code, the hash and the session are not recorded.\n\nNext: run "
            .'`auth_export_login_token` to mint a transferable token, then `auth_import_login_token` '
            .'from the second device.',
        );
    }

    // ── FLOW 1, STEP 3 ────────────────────────────────────────────────────

    /**
     * `auth.exportLoginToken` — the half only the source account can produce.
     *
     * This is the mint side of a device transfer, and it is the method
     * `ReadOnlyVaultDispatch` refuses outright as a latent account-takeover
     * primitive (`src/Laravel/Services/ReadOnlyVaultDispatch.php:72-75`), which
     * is the right refusal for a read-only scope and the reason this step needs
     * its own opt-in.
     *
     * The token is a credential that grants a full login. It is asserted to be
     * present and is NEVER written to the report.
     */
    #[Test]
    public function export_login_token_mints_a_transferable_token(): void
    {
        $this->requireLiveStep('auth_export_login_token');

        $client = self::liveUserClient();

        try {
            $answer = $client->call('auth.exportLoginToken', [
                'api_id' => $client->apiId,
                'api_hash' => $client->apiHash,
                'except_ids' => [],
            ]);

            self::assertArrayNotHasKey('_offline', $answer, 'nothing was sent, so nothing was proved');
            self::assertSame(
                'auth.loginToken',
                $answer['_'] ?? null,
                'auth.exportLoginToken must return auth.loginToken; anything else means Telegram did '
                .'not accept the request',
            );
            self::assertArrayHasKey('token', $answer, 'a login token must carry a token');
            self::assertNotSame('', (string) ($answer['token'] ?? ''), 'the token must not be empty');

            if (isset($answer['expires'])) {
                self::assertGreaterThan(
                    time(),
                    (int) $answer['expires'],
                    'a login token that has already expired cannot prove a transfer is possible',
                );
            }
        } finally {
            $client->close();
        }

        self::liveAppendReport(
            'auth.exportLoginToken',
            'A transferable login token was minted and it is NOT recorded here — it grants a full '
            ."login.\n\nNext: on the SECOND device, export the token and run "
            .'`auth_import_login_token`.',
        );
    }

    // ── FLOW 1, STEP 4 — the second device ────────────────────────────────

    /**
     * `auth.importLoginToken` — the other half, on a different session.
     *
     * This is the step that makes the transfer claim meaningful. An export that
     * is only ever imported back into the session that minted it would prove
     * nothing, and a locally-fabricated `auth.authorization` would look
     * identical. So the session is a SEPARATE one, supplied by the operator,
     * and the assertion is that Telegram — not this package — minted the
     * authorization.
     */
    #[Test]
    public function import_login_token_installs_a_authorization_on_a_second_device(): void
    {
        $this->requireLiveStep('auth_import_login_token');

        $token = self::liveEnvRequired(
            'TELEFRAME_LIVE_LOGIN_TOKEN',
            'the token minted by auth.exportLoginToken on the FIRST device. It is a full-login '
            .'credential and is never printed by this gate.',
        );

        $second = self::liveEnvRequired(
            'TELEFRAME_LIVE_DEVICE2_SESSION',
            'the second device\'s own session string — a different auth key from the one that minted '
            .'the token. A transfer into the minting session would prove nothing.',
        );

        $client = self::liveClientFromSessionString($second);

        try {
            $answer = $client->call('auth.importLoginToken', ['token' => $token]);

            self::assertArrayNotHasKey('_offline', $answer, 'nothing was sent, so nothing was proved');
            self::assertSame(
                'auth.loginTokenSuccess',
                $answer['_'] ?? null,
                'the second device must receive auth.loginTokenSuccess. A `auth.loginTokenMigrateTo` '
                .'answer means the token must be imported at another DC, which this step does not do; '
                .'any other constructor means the transfer did not happen. Got: '
                .(string) ($answer['_'] ?? 'nothing'),
            );
            self::assertSame(
                'auth.authorization',
                $answer['authorization']['_'] ?? null,
                'a successful import carries the authorization it installed',
            );
        } finally {
            $client->close();
        }

        self::liveAppendReport(
            'auth.importLoginToken (second device)',
            'A token minted on the FIRST device was imported on a SECOND device with a different auth '
            .'key, and Telegram returned `auth.loginTokenSuccess` carrying the authorization it '
            .'installed. The token is not recorded. The device transfer is proven.',
        );
    }

    // ── FLOW 2 — the one with no evidence at all ───────────────────────────

    /**
     * `auth.importBotAuthorization` against a real DC.
     *
     * The one call the bot transport is made of, and the one no offline test can
     * stand in for: `VaultMtprotoAnswerer` answers `auth.*` in memory
     * (`src/Core/MTProto/Transport/VaultMtprotoAnswerer.php:404-407`), so the
     * entire bot-over-MTProto story is currently unevidenced.
     *
     * The build matches `TeleframeAuthService::loginBot()`
     * (`src/Laravel/Services/TeleframeAuthService.php:449-456`) and
     * `BotAccountScope::login()` (`src/Bot/Services/BotAccountScope.php:36-44`):
     * an empty auth key so the handshake runs, `flags` 0, and the api pair
     * carried in the params exactly as the shipped code does.
     */
    #[Test]
    public function import_bot_authorization_authenticates_a_bot_on_a_real_dc(): void
    {
        $this->requireLiveStep('auth_import_bot_authorization');

        $client = self::liveBotClient();
        $token = self::liveBotToken();

        try {
            $answer = $client->call('auth.importBotAuthorization', [
                'flags' => 0,
                'api_id' => $client->apiId,
                'api_hash' => $client->apiHash,
                'bot_auth_token' => $token,
            ]);

            self::assertArrayNotHasKey(
                '_offline',
                $answer,
                'nothing was sent, so nothing was proved about the bot transport',
            );
            self::assertSame(
                'auth.authorization',
                $answer['_'] ?? null,
                'auth.importBotAuthorization must return auth.authorization. Anything else — including '
                .'a locally fabricated shape — means Telegram did not accept the bot token.',
            );
            self::assertSame(
                'user',
                $answer['user']['_'] ?? null,
                'the authorization must carry the bot account it authenticated',
            );
            self::assertTrue(
                (bool) ($answer['user']['bot'] ?? false),
                'the authorized account must report itself as a bot. An authorization for a human '
                .'account means the token was not the bot token the operator exported.',
            );
        } finally {
            $client->close();
        }

        self::liveAppendReport(
            'auth.importBotAuthorization',
            'A bot token was accepted by a real DC and came back as an `auth.authorization` whose '
            .'account reports `bot: true`. This is the first live evidence for the bot-over-MTProto '
            .'path in this repository. The token is not recorded.',
        );
    }

    /**
     * THE AUTH SURFACE OF THIS LANE IS FIVE METHODS. CLOSED.
     *
     * Derived from the shipped catalogue rather than from a hand-written list,
     * so a new `auth.*` method cannot become dialable by omission and a removed
     * one cannot quietly linger in the carve-out.
     */
    #[Test]
    public function the_lane_may_dial_exactly_five_auth_methods_and_no_others(): void
    {
        // Deliberately UNGATED. It reads the catalogue and the policy, opens no
        // socket and needs no account, so gating it would trade real CI coverage
        // for a skip that means nothing.

        $catalogue = json_decode(
            (string) file_get_contents(__DIR__.'/../../src/Schema/schema/methods-mtproto.json'),
            true,
        );
        self::assertIsArray($catalogue);

        $allowed = [];
        $refused = 0;
        foreach (array_keys($catalogue['methods']) as $name) {
            $name = (string) $name;

            if (! str_starts_with($name, 'auth.')) {
                continue; // the warm allowlist's non-auth reads are a different question
            }

            try {
                self::assertLivePolicyAllows($name);
                $allowed[] = $name;
            } catch (RuntimeException) {
                $refused++;
            }
        }

        // The AUTH subset of the carve-out, and only that: the carve-out is
        // shared with steps that are not about auth (`backup_vault` holds nine
        // vault methods), so comparing the whole list against an auth-only sweep
        // would fail on entries this test has no opinion about. Comparing the
        // auth subset instead keeps the claim this test actually makes — the
        // live lane's auth surface is exactly the five the operator named —
        // closed in both directions.
        $expected = array_values(array_filter(
            array_keys(self::liveFlowMethods()),
            static fn (string $method): bool => str_starts_with($method, 'auth.'),
        ));
        sort($allowed);
        sort($expected);

        self::assertCount(
            5,
            $expected,
            'the carve-out\'s auth subset is five methods. A different number is a deliberate change '
            .'to the lane\'s auth surface, not something to discover from a failed assertion.',
        );

        self::assertSame(
            $expected,
            $allowed,
            'the live lane\'s auth surface is exactly the five operator-named calls. Anything else '
            .'being allowed means the carve-out was widened without saying so.',
        );
        self::assertGreaterThan(
            5,
            $refused,
            'sanity: the rest of the auth namespace must be refused, otherwise the check above is vacuous',
        );

        // The warm lane is a strict subset and must stay that way.
        foreach ($expected as $method) {
            self::assertNotNull(
                LiveEndpointPolicy::refusalReason($method),
                "{$method} must remain refused by the warm lane — the carve-out must not leak back in",
            );
        }
    }

    // ── the structural refusal ────────────────────────────────────────────

    /**
     * NEVER END TO END — asserted on this file's own source.
     *
     * Chaining the four steps would require carrying a live auth key and a
     * `phone_code_hash` between test methods, which means putting credentials on
     * disk. So each test body may contain AT MOST ONE of the four flow methods,
     * and that is checked here rather than left to review.
     */
    #[Test]
    public function the_auth_flow_is_never_wired_end_to_end(): void
    {
        // Deliberately UNGATED: it scans this file's own source, so it is the
        // cheapest possible standing guard against the chain this file forbids.

        $source = (string) file_get_contents(__FILE__);

        $flow = ['auth.sendCode', 'auth.signIn', 'auth.exportLoginToken', 'auth.importLoginToken'];

        // This method's own body necessarily NAMES all four, to check them, so
        // it is excluded from its own scan. Nothing else is: the exclusion is
        // by name, so it cannot grow.
        $self = 'the_auth_flow_is_never_wired_end_to_end';

        $bodies = explode('public function ', $source);
        self::assertGreaterThan(5, count($bodies), 'the split produced no usable bodies');

        $checked = 0;
        $seen = [];
        foreach ($bodies as $body) {
            $name = substr($body, 0, (int) strpos($body, '('));

            if ($name === $self) {
                continue;
            }

            $found = [];
            foreach ($flow as $method) {
                if (str_contains($body, "'{$method}'")) {
                    $found[] = $method;
                    $seen[$method] = ($seen[$method] ?? 0) + 1;
                }
            }

            self::assertLessThanOrEqual(
                1,
                count($found),
                "{$name}() drives more than one step of the login flow ("
                .implode(', ', $found).'). Chaining them would require moving a live auth key and a '
                .'phone_code_hash between steps, which means credentials on disk. Keep them separate.',
            );

            $checked++;
        }

        self::assertGreaterThan(
            4,
            $checked,
            'the scan must actually have looked at the step bodies, not found none of them',
        );

        // Each step must be exercised exactly once outside this guard, so the
        // assertion above cannot pass by the flow simply not being written.
        foreach ($flow as $method) {
            self::assertSame(
                1,
                $seen[$method] ?? 0,
                "{$method} must appear in exactly one test body outside this guard — zero means the "
                .'step was deleted and the anti-chaining check became vacuous, more than one means a '
                .'step is exercised twice.',
            );
        }
    }

    // ── internals ─────────────────────────────────────────────────────────

    /**
     * A live client from a session string the operator exported, with the same
     * shape checks `liveUserClient()` applies. Used by the two steps that need a
     * session the gate cannot itself produce.
     */
    private static function liveClientFromSessionString(string $sessionString): Client
    {
        $apiId = self::liveEnvRequired('TELEGRAM_API_ID', 'the api_id every MTProto connection is keyed on');

        if (! ctype_digit($apiId) || (int) $apiId <= 0) {
            throw new RuntimeException(
                'live gate: TELEGRAM_API_ID is present but is not a positive integer. Its value is '
                .'not printed by this gate.'
            );
        }

        $session = SessionData::importString($sessionString);
        self::assertLiveSessionIsReal($session);

        return new Client(
            apiId: (int) $apiId,
            apiHash: self::liveEnvRequired('TELEGRAM_API_HASH', 'the api_hash paired with TELEGRAM_API_ID'),
            session: $session,
            live: true,
        );
    }
}
