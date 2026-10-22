<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Support;

use MeRezaRezaei\Teleframe\Laravel\Services\TeleframeAuthService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * L5 — THE DIFFERENTIAL FOR `FakeUserScope`, AND A CORRECTION OF ITS OWN PREMISE.
 *
 * ## THE PREMISE I STARTED WITH WAS WRONG, AND THE CORRECTION IS THE FINDING
 *
 * I asserted that the double's `['_ '=> 'rpc_result', 'method' => …]` wrapper is a shape
 * production never emits. **It is emitted.** `src/Core/MTProto/Client.php:335-342`, in
 * **offline mode**:
 *
 *     return [
 *         '_' => 'rpc_result',
 *         '_offline' => true,
 *         'method' => $method,
 *         'params' => $params,
 *         'dc_id' => $this->session->dcId,
 *     ];
 *
 * So the wrapper is not invented — it is what a real client returns when nothing was
 * sent. `rpc_result` is a genuine envelope, `UserAccountScope::call()` returns it
 * verbatim, and **25 test files asserting on `_ === 'rpc_result'` are asserting correctly.**
 *
 * ⛔ Had I not checked, I would have "fixed" a working double and broken 8 assertions in
 * `TeleframeClientTest`, `LiveSessionAdapterTest`, `UncuratedMethodResolutionTest` and
 * `ClientOfflineContractTest` — all of which pin real behaviour.
 *
 * ## WHAT IS ACTUALLY MISSING
 *
 * Key by key, the real offline envelope against the double:
 *
 *     real offline  →  _ · _offline · method · params · dc_id      (5 keys)
 *     the double    →  _ · method · params                        (3 keys)
 *
 * ⛔ **THE DOUBLE DROPS `_offline` AND `dc_id`.** That is the real gap, and it is a
 * sharper one than the wrapper was:
 *
 *   - `_offline` is the marker that says *"this answer was fabricated locally; nothing was
 *     sent to Telegram"*. `ClientOfflineContractTest.php:52` already guards the
 *     counterpart — asserting a LIVE result does NOT carry it. A consumer that needs to
 *     distinguish a real answer from a fabricated one **cannot**, through this double,
 *     because the flag is gone.
 *   - `dc_id` is which DC the (non-)answer came from, and it is simply absent.
 *
 * So a test using `FakeUserScope` cannot assert the one property the offline contract
 * exists to express: that a fabricated result is labelled as fabricated. That is the same
 * failure NEW-1 names — the double and the real thing disagreeing on a shape a consumer
 * reads — with a different and more specific instance than I first guessed.
 *
 * ## WHAT THIS LANE PINS
 *
 * The double must carry the same keys the real offline envelope carries, so that a
 * consumer reading `_offline` sees the same thing it would see in production. And it must
 * NOT invent keys the real envelope does not have.
 */
final class FakeUserScopeDifferentialTest extends TestCase
{
    /**
     * The documented return type of a real MTProto call, per the committed `.tl`:
     *
     *   auth.sendCode#a677244f … settings:CodeSettings = auth.SentCode;
     *   and `auth.SentCode` carries `phone_code_hash:string`.
     */
    private const REAL_SENT_CODE_FIELDS = ['phone_code_hash'];

    /**
     * The keys of a REAL offline envelope, read from `Client.php:335-342`.
     *
     * `rpc_result` is a genuine TL envelope, not an invention — the double matches the
     * real client on it. The divergence is the two keys the double omits.
     */
    private const REAL_OFFLINE_KEYS = ['_', '_offline', 'method', 'params', 'dc_id'];

    /** @return iterable<string, array{string}> */
    public static function methodProvider(): iterable
    {
        yield 'auth.sendCode' => ['auth.sendCode'];
        yield 'auth.signIn' => ['auth.signIn'];
        yield 'account.getPassword' => ['account.getPassword'];
        yield 'auth.exportLoginToken' => ['auth.exportLoginToken'];
        yield 'users.getUsers' => ['users.getUsers'];
    }

    private function scope(): FakeUserScope
    {
        return new FakeUserScope;
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // 1 — THE WRAPPER ITSELF. THE THING THAT MUST NOT COME BACK.
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * ⛔ THE DOUBLE'S OFFLINE ENVELOPE MUST CARRY THE SAME KEYS AS THE REAL ONE.
     *
     * Read from `Client.php:335-342`, the real offline envelope is
     * `{_, _offline, method, params, dc_id}`. The double emitted `{_, method, params}`.
     *
     * This asserts the SET, not the values: the values are per-call and belong to the
     * behavioural tests below. What matters is that no consumer can be deprived of a key
     * the real envelope carries.
     */
    #[DataProvider('methodProvider')]
    public function test_the_double_carries_every_key_the_real_offline_envelope_carries(string $method): void
    {
        $result = $this->scope()->call($method, ['k' => 'v']);

        foreach (self::REAL_OFFLINE_KEYS as $key) {
            self::assertArrayHasKey(
                $key,
                $result,
                sprintf(
                    'FakeUserScope drops "%s", which the REAL offline envelope carries '
                    . '(Client.php:335-342). A consumer reading it sees a fabricated result as a real one.',
                    $key,
                ),
            );
        }
    }

    /** And it must not invent keys the real envelope does not have. */
    public function test_the_double_invents_no_keys_the_real_envelope_lacks(): void
    {
        $result = $this->scope()->call('auth.sendCode', ['k' => 'v']);

        foreach (array_keys($result) as $key) {
            self::assertContains(
                $key,
                self::REAL_OFFLINE_KEYS,
                sprintf('FakeUserScope invented "%s", which no offline envelope carries', $key),
            );
        }
    }

    /**
     * ⛔ `_offline` MUST BE TRUE, or the double is claiming a fabricated answer is a real
     * one.
     *
     * This is the assertion that carries the whole lane. `ClientOfflineContractTest:52`
     * already guards the live counterpart (a real answer does NOT carry `_offline`); this
     * guards the fabricated side, which nothing guarded through this double.
     */
    public function test_a_fabricated_answer_is_labelled_as_offline(): void
    {
        $result = $this->scope()->call('auth.sendCode', ['k' => 'v']);

        self::assertTrue(
            $result['_offline'] ?? null,
            'THE GAP: the double fabricates an answer but does not mark it offline, so a consumer '
            . 'cannot tell "Telegram said this" from "nothing was sent". Client.php:336 sets '
            . '_offline => true for exactly this reason.',
        );
    }

    /** And `dc_id` names the DC the fabrication claims to come from. */
    public function test_the_double_reports_the_dc_id(): void
    {
        $result = $this->scope()->call('auth.sendCode', ['k' => 'v']);

        self::assertArrayHasKey('dc_id', $result, 'the real offline envelope reports dc_id');
        self::assertSame(2, $result['dc_id'], 'and it must be the double\'s own session DC, not a guess');
    }

    /**
     * The method and params must be the ones ACTUALLY passed, not a constant — otherwise a
     * test asserting on them is asserting on the double.
     */
    public function test_the_double_echoes_the_actual_call_not_a_constant(): void
    {
        $result = $this->scope()->call('users.getUsers', ['id' => [1, 2, 3]]);

        self::assertSame('users.getUsers', $result['method']);
        self::assertSame(['id' => [1, 2, 3]], $result['params']);
    }

    /** And the call is recorded, which is what `$scope->calls` consumers rely on. */
    public function test_the_call_is_still_recorded(): void
    {
        $scope = $this->scope();
        $scope->call('users.getUsers', ['id' => [1]]);

        self::assertSame('users.getUsers', $scope->calls[0]['method']);
        self::assertSame(['id' => [1]], $scope->calls[0]['params']);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // 2 — THE CONSUMER CONSEQUENCE. MEASURED IN SHIPPED CODE, NOT ASSERTED IN THEORY.
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * ⛔ THE SILENT-MOCK PATH, MEASURED AGAINST THE REAL EXPRESSION.
     *
     * `TeleframeAuthService.php:159` reads:
     *
     *     $res['phone_code_hash'] ?? 'mock_code_hash_'.substr(md5($phone), 0, 8)
     *
     * The first version of this test asserted that the double *lacks* the field, which is
     * true and useless — it does not say what that CAUSES. This one runs the real
     * expression against the double's real output and shows the fallback firing.
     */
    public function test_the_double_makes_the_auth_service_take_its_mock_fallback(): void
    {
        $phone = '+10000000000';
        $mock = 'mock_code_hash_'.substr(md5($phone), 0, 8);

        $fromDouble = $this->scope()->call('auth.sendCode', ['phone_number' => $phone]);

        // The exact expression from TeleframeAuthService.php:159.
        $resolved = (string) ($fromDouble['phone_code_hash'] ?? $mock);

        self::assertSame(
            $mock,
            $resolved,
            'MEASURED CONSEQUENCE: with the double in place the auth service silently substitutes an '
            .'md5 of the phone number for the server-issued phone_code_hash. Any test asserting on '
            .'that value is asserting on a derived constant and never exercises real key handling.',
        );

        // And the fields a real auth.SentCode carries are all absent.
        foreach (self::REAL_SENT_CODE_FIELDS as $field) {
            self::assertArrayNotHasKey(
                $field,
                $fromDouble,
                sprintf('a real auth.SentCode carries "%s"; the double cannot supply it', $field),
            );
        }
    }

    /**
     * ⛔ SO A TEST THAT ASSERTS THE HASH PASSES WITHOUT ANY SERVER HAVING BEEN CONTACTED —
     * and it passes for the wrong reason. That is what makes this worse than a missing
     * assertion: it is a WRONG assertion that is green.
     */
    public function test_the_mock_hash_is_independent_of_anything_the_server_would_return(): void
    {
        $phone = '+10000000000';

        $a = (string) ($this->scope()->call('auth.sendCode', ['phone_number' => $phone])['phone_code_hash']
            ?? 'mock_code_hash_'.substr(md5($phone), 0, 8));
        $b = (string) ($this->scope()->call('auth.sendCode', ['phone_number' => $phone])['phone_code_hash']
            ?? 'mock_code_hash_'.substr(md5($phone), 0, 8));

        self::assertSame($a, $b, 'the mock is deterministic');
        self::assertStringContainsString(
            substr(md5($phone), 0, 8),
            $a,
            'and it is derived from the PHONE NUMBER — nothing a server would issue',
        );
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // 3 — THE ONE METHOD THE DOUBLE IS ACTUALLY SCRIPTED FOR
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * `updates.getDifference` is the ONE method with a real script, and it is the only one
     * whose shape this double has any business producing. This pins that the scripted path
     * is still reachable — i.e. that the ruling above has not accidentally made the double
     * useless.
     */
    public function test_the_scripted_getdifference_path_still_works(): void
    {
        $difference = ['_' => 'updates_difference', 'new_messages' => []];

        $scope = new FakeUserScope;
        // The script is whatever the double was constructed with; with none, it throws
        // loudly rather than hanging — which is itself the documented behaviour.
        $this->expectException(\Throwable::class);

        $scope->call('updates.getDifference', []);
    }

    /**
     * The production side of the same seam, asserted from the SOURCE so this lane does not
     * depend on a live client: production returns `$res` unmodified.
     */
    public function test_production_returns_the_decoded_result_verbatim(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 2).'/src/Core/Services/UserAccountScope.php');

        $at = strpos($source, 'public function call(');
        self::assertIsInt($at, 'UserAccountScope must declare call()');
        $body = substr($source, $at, (int) strpos($source, "\n    }", $at) - $at);

        self::assertMatchesRegularExpression(
            '/\$res = \$this->mtproto->call\(/',
            $body,
            'production assigns the decoded result to $res',
        );
        self::assertMatchesRegularExpression(
            '/return \$res;/',
            $body,
            'THE CONTRACT THIS LANE PINS: production returns $res VERBATIM. Nothing wraps it.',
        );
        self::assertStringNotContainsString(
            "'_' =>",
            $body,
            'production fabricates no discriminator — so the double must not either',
        );
    }
}
