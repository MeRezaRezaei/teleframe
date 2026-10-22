<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\App;

use InvalidArgumentException;
use MeRezaRezaei\Teleframe\App\AppCall;
use MeRezaRezaei\Teleframe\App\AppCallSpec;
use MeRezaRezaei\Teleframe\App\AppDefinition;
use MeRezaRezaei\Teleframe\App\AppException;
use MeRezaRezaei\Teleframe\App\AppMode;
use MeRezaRezaei\Teleframe\App\AppRegistry;
use MeRezaRezaei\Teleframe\App\AppRuntime;
use MeRezaRezaei\Teleframe\Store\Schedules\NullSchedulePublisher;
use MeRezaRezaei\Teleframe\Store\Schedules\SchedulePublisher;
use PHPUnit\Framework\TestCase;

/**
 * F16-6 — the two accumulation sites in AppRuntime that merge every
 * registered app's AppCallSpec payload into ONE name=>payload map:
 *
 *   src/Teleframe/App/AppRuntime.php:108  calls()        $spec    += installSpec($app)
 *   src/Teleframe/App/AppRuntime.php:227  installCalls() $payload += installSpec($app)
 *
 * `+=` on a name=>payload map is the shape that loses the SECOND payload on
 * a colliding key without saying so — a silent drop, which is the opposite
 * of this package's refuse-loud posture (AppRegistry::define refuses a
 * duplicate app name; AppDefinition::define refuses a within-app duplicate
 * call name; both loudly).
 *
 * THIS FILE CARRIES TWO INDEPENDENT GATES, and the split is the point:
 *
 *  1. BEHAVIOURAL — `test_*_survives_*`: exercise the accumulation over
 *     enough iterations (64 apps x 4 calls = 256 merges, adversarial
 *     naming, repeated invocations) to expose a WRONG RESULT if one is
 *     reachable. Measured: it does NOT discriminate the merge operator.
 *     See .brain/evidence/2026-10-15-fix-f16-6-latent-accumulation.md for
 *     the mutation evidence. It stays here because it is the regression
 *     net for the union itself (a future AppCallSpec id change that
 *     collides WOULD trip it).
 *
 *  2. SHAPE — `test_*_merge_site_*`: the only gate class that CAN
 *     discriminate here, because a colliding pair is unreachable by
 *     construction (AppCallSpec::callId is a function of the (app, call)
 *     pair plus a 12-hex sha256 digest; duplicate app names and
 *     within-app duplicate call names are both refused loudly). So the
 *     gate reads the two method bodies as tokens and asserts the merge is
 *     the explicit `array_replace(...)` and that the method documents the
 *     latent collision. This goes RED whenever `+=` is reintroduced.
 *
 * A gate never observed failing is not a gate; both halves below were run
 * RED, mutated, and restored.
 */
final class AppRuntimeCallMergeTest extends TestCase
{
    /** Apps registered in the accumulation probes — enough merges to be worth iterating. */
    private const APPS = 64;

    /** Calls per app. */
    private const CALLS_PER_APP = 4;

    private const RUNTIME_FILE = __DIR__.'/../../src/Teleframe/App/AppRuntime.php';

    // ------------------------------------------------------------------
    // GATE 1 — BEHAVIOURAL: the union survives the accumulation.
    // ------------------------------------------------------------------

    public function test_every_declared_pair_survives_the_calls_accumulation(): void
    {
        [$runtime, $declared] = self::runtimeWithManyApps();

        $spec = $runtime->calls();

        self::assertCount(
            self::APPS * self::CALLS_PER_APP,
            $spec,
            'the merged payload must hold one row per DECLARED (app, call) pair — a merge that '
            .'silently folds a colliding key loses rows and this is the assertion that would see it',
        );

        self::assertSame(
            self::expectedKeys($declared),
            array_keys($spec),
            'the merged key set must be exactly the union of every per-app installSpec() key, in registry order',
        );

        foreach ($spec as $id => $payload) {
            $app = (string) $payload['app'];
            $call = (string) $payload['call'];

            // NOT recomputed from callId(). An expectation built with the SUT's own
            // formula cannot disagree with that formula — the same shape of defect as
            // recomputing a Telegram rtrim with rtrim. Measured, not assumed:
            // planting an app/call swap inside AppCallSpec::callId() left every
            // `assertSame($recomputed, $id)` in this loop GREEN, and turned this
            // test red only on the enumerated key shape below. So the key is pinned
            // by its pre-calculated FORM — the part the pair decides, plus the fixed
            // 12-hex digest tail — and the digest's own VALUE is pinned as a literal
            // in the test that follows.
            self::assertMatchesRegularExpression(
                '/\Aapp-call-'.preg_quote($app, '/').'-'.preg_quote($call, '/').'-[0-9a-f]{12}\z/',
                (string) $id,
                "the row for ({$app}, {$call}) must sit under exactly its own wire key — "
                ."app-call-{$app}-{$call}-<12 hex>. A key that does not name ITS pair is the wrong result.",
            );

            self::assertSame(
                'telegram.app.run-call '.$app.' '.$call,
                $payload['activity'],
                "the row under the ({$app}, {$call}) key must carry the activity that pair names",
            );
            self::assertSame(
                self::intervalOf($declared, $app, $call),
                $payload['interval_seconds'],
                "app {$app}'s cadence must survive the merge under its own key",
            );
        }
    }

    /**
     * The digest half of the wire key, ENUMERATED rather than recomputed.
     *
     * The union test above used to assert "this key equals callId() rebuilt from
     * this payload" — an expectation computed with the very formula under test,
     * which is the defect shape this package already shipped once (a Telegram
     * rtrim recomputed with rtrim). Measured: planting an app/call swap inside
     * callId() left all of those assertions green.
     *
     * So the digest is pinned HERE, as literals written before the test ran.
     * No test in the suite pinned a digest VALUE before this one. If the digest
     * input, algorithm or length changes, this goes red and the wire-key
     * contract becomes a decision somebody makes on purpose.
     */
    public function test_the_schedule_id_digests_are_pre_calculated_literals(): void
    {
        [$runtime] = self::runtimeWithManyApps();

        $spec = $runtime->calls();

        // sha256("<app>\0<call>") truncated to 12, written out by hand.
        self::assertArrayHasKey(
            'app-call-join-a-status-ping-72e60eff7ba6',
            $spec,
            'sha256("join-a\0status-ping")[:12] is 72e60eff7ba6 — the first app\'s first call',
        );
        self::assertArrayHasKey(
            'app-call-join-a-b-sweep-1-d04e57f81b4e',
            $spec,
            'sha256("join-a-b\0sweep-1")[:12] is d04e57f81b4e — and the digest is over app.NUL.call, '
            .'not the joined "app-call" string, so an app name containing a hyphen still digests its own pair',
        );
        self::assertArrayHasKey(
            'app-call-worker-2-status-ping-20e401554561',
            $spec,
            'sha256("worker-2\0status-ping")[:12] is 20e401554561 — two apps sharing the call name '
            .'still install two distinct schedules',
        );
    }

    public function test_the_accumulation_is_stable_across_repeated_invocations(): void
    {
        [$runtime] = self::runtimeWithManyApps();

        $first = $runtime->calls();

        for ($round = 2; $round <= 5; $round++) {
            self::assertSame($first, $runtime->calls(), "round {$round} must return the same union — "
                .'an accumulator that leaked across calls would grow, an operator that dropped on the '
                .'second merge would shrink');
        }
    }

    public function test_install_calls_hands_the_publisher_exactly_the_same_union(): void
    {
        [$runtime, $declared] = self::runtimeWithManyApps();
        $publisher = new MergeRecordingSchedulePublisher;

        $publisherRuntime = new AppRuntime(
            registry: self::registryOf($declared),
            schedules: $publisher,
        );

        $status = $publisherRuntime->installCalls();

        self::assertCount(self::APPS * self::CALLS_PER_APP, $status);
        self::assertSame(
            $runtime->calls(),
            $publisher->installed,
            'installCalls() accumulates the same union calls() previews — the write side must not lose '
            .'a row the read side kept',
        );
        self::assertSame(
            self::expectedKeys($declared),
            array_keys($publisher->installed),
        );
    }

    /**
     * WHY GATE 1 CANNOT DISCRIMINATE THE OPERATOR — measured, not assumed.
     *
     * `+=` loses its second payload only when two per-app payloads carry the
     * SAME key. Two reachable routes to that, and both are shut loudly:
     * AppRegistry::define refuses a duplicate app NAME, and
     * AppDefinition::define refuses a within-app duplicate CALL NAME. What
     * remains is a digest collision inside `callId()`, and the grid below
     * searches the adversarial naming space exhaustively — the names that
     * read as the SAME joined `app-call-a-b-c` prefix, the shared call name,
     * the app name reused as another app's call name — and requires every
     * resulting id to be distinct.
     *
     * If this test ever fails, a reachable collision EXISTS and gate 1's
     * union assertion becomes discriminating, which is the moment the
     * behavioural half is promoted to the primary gate.
     */
    public function test_no_reachable_declared_pair_collides_on_a_schedule_id(): void
    {
        $names = ['a', 'b', 'c', 'a-b', 'b-c', 'a-b-c', '-a', 'a-', 'a--b', '', '0', '00'];

        $spec = new AppCallSpec;
        $ids = [];

        foreach ($names as $appName) {
            if (trim($appName) === '') {
                continue;
            }

            foreach ($names as $callName) {
                if (trim($callName) === '') {
                    continue;
                }

                $call = AppCall::every($callName, 60, 'messages.sendMessage');
                $id = $spec->callId(
                    AppDefinition::define($appName, AppMode::Bot, calls: [$call]),
                    $call,
                );

                $clashedWith = $ids[$id] ?? null;

                self::assertNull(
                    $clashedWith,
                    "schedule id collision: the pair ({$appName}, {$callName}) reads the SAME wire key "
                    ."as ({$clashedWith}) — a colliding key is exactly what makes `+=` lose a payload "
                    .'silently, and it would make the union assertions above discriminating',
                );

                $ids[$id] = "{$appName}, {$callName}";
            }
        }

        self::assertGreaterThan(100, count($ids), 'the grid must actually be a grid, not a handful of pairs');
    }

    public function test_a_duplicate_app_name_is_refused_loud_so_two_apps_never_merge_one_key(): void
    {
        $this->expectException(AppException::class);
        $this->expectExceptionMessage('already defined');
        (new AppRegistry)->define(AppDefinition::define('dup', AppMode::Bot))
            ->define(AppDefinition::define('dup', AppMode::Bot));
    }

    public function test_a_within_app_duplicate_call_name_is_refused_loud(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('a call id is unique within its app');
        AppDefinition::define('dup-calls', AppMode::Bot, calls: [
            AppCall::every('status-ping', 3600, 'messages.sendMessage'),
            AppCall::every('status-ping', 60, 'messages.sendMessage'),
        ]);
    }

    // ------------------------------------------------------------------
    // GATE 2 — SHAPE: the only gate class that can discriminate. RED
    // whenever `+=` is back on either merge site.
    // ------------------------------------------------------------------

    public function test_the_calls_merge_site_is_not_the_silent_first_wins_plus_equal(): void
    {
        foreach (['calls', 'installCalls'] as $method) {
            $code = self::codeTokensOf(self::RUNTIME_FILE, $method);

            self::assertNotContains(
                T_PLUS_EQUAL,
                self::operatorTokenIds($code),
                "AppRuntime::{$method}() must not accumulate a name=>payload map with `+=`: on a "
                .'colliding key `+=` keeps the FIRST payload and drops the second without a word. '
                .'Use the explicit array_replace() (mutation of this line back to `+=` is what turns '
                .'this gate red).',
            );

            self::assertContains(
                'array_replace',
                self::calledFunctions($code),
                "AppRuntime::{$method}() must name the merge operator explicitly with array_replace() so "
                .'the last-wins semantic is a decision on the page, not an accident of `+=`.',
            );
        }
    }

    public function test_the_calls_merge_site_documents_the_latent_collision(): void
    {
        foreach (['calls', 'installCalls'] as $method) {
            $comments = self::commentTextOf(self::RUNTIME_FILE, $method);

            self::assertNotSame('', $comments, "AppRuntime::{$method}() must carry a comment about its merge");

            self::assertTrue(
                str_contains(strtolower($comments), 'collision') || str_contains(strtolower($comments), 'collide'),
                "AppRuntime::{$method}() must NAME the latent collision in a comment. The audit's bar for "
                .'this friction is explicit: a comment is only acceptable if it says which key collides '
                .'and what the merge does when it does.',
            );
        }
    }

    public function test_the_gate_itself_reads_the_two_named_methods_and_fails_loud_otherwise(): void
    {
        // A shape gate that silently finds nothing passes vacuously. Pin the
        // extraction: both methods must resolve, and each body must still
        // contain the merge this gate is about.
        self::assertStringContainsString('installSpec', self::codeTextOf(self::RUNTIME_FILE, 'calls'));
        self::assertStringContainsString('installSpec', self::codeTextOf(self::RUNTIME_FILE, 'installCalls'));
        self::assertFileExists(self::RUNTIME_FILE);
    }

    // ------------------------------------------------------------------
    // fixtures
    // ------------------------------------------------------------------

    /**
     * 64 apps x 4 calls, named adversarially: every app reuses the SAME call
     * name (`status-ping`), each app's own names repeat another app's name,
     * and two apps read as the same joined `app-call-a-b-c` prefix. If any
     * two of these produced the same wire key, the union assertions above
     * would lose a row.
     *
     * @return array{0: AppRuntime, 1: list<AppDefinition>}
     */
    private static function runtimeWithManyApps(): array
    {
        $declared = self::declaredApps();
        $runtime = new AppRuntime(registry: self::registryOf($declared), schedules: new NullSchedulePublisher);

        return [$runtime, $declared];
    }

    /** @return list<AppDefinition> */
    private static function declaredApps(): array
    {
        $apps = [];

        for ($i = 0; $i < self::APPS; $i++) {
            $name = $i === 0 ? 'join-a' : ($i === 1 ? 'join-a-b' : 'worker-'.$i);

            $apps[] = AppDefinition::define($name, AppMode::Bot, triggers: [], calls: [
                AppCall::every('status-ping', 3600 + $i, 'messages.sendMessage', ['message' => 'alive']),
                AppCall::every('sweep-'.$i, 60 + $i, 'messages.getHistory', ['peer' => '@ops']),
                AppCall::every($i === 0 ? 'b-c' : 'c-'.$i, 120 + $i, 'messages.sendMessage'),
                AppCall::every('worker-'.$i, 300 + $i, 'messages.sendMessage'),
            ]);
        }

        return $apps;
    }

    /** @param  list<AppDefinition>  $apps */
    private static function registryOf(array $apps): AppRegistry
    {
        $registry = new AppRegistry;

        foreach ($apps as $app) {
            $registry->define($app);
        }

        return $registry;
    }

    /** @param  list<AppDefinition>  $apps */
    private static function expectedKeys(array $apps): array
    {
        $spec = new AppCallSpec;
        $keys = [];

        foreach ($apps as $app) {
            foreach ($app->calls() as $call) {
                $keys[] = $spec->callId($app, $call);
            }
        }

        return $keys;
    }

    /** @param  list<AppDefinition>  $apps */
    private static function intervalOf(array $apps, string $app, string $call): int
    {
        foreach ($apps as $definition) {
            if ($definition->name !== $app) {
                continue;
            }

            foreach ($definition->calls() as $appCall) {
                if ($appCall->name === $call) {
                    return $appCall->intervalSeconds;
                }
            }
        }

        self::fail("no declared pair ({$app}, {$call})");
    }

    // ------------------------------------------------------------------
    // token plumbing (tests/ may use regex; src/ may not — this uses the
    // tokenizer so the gate cannot be fooled by `+=` inside a string)
    // ------------------------------------------------------------------

    /** @return list<array|string> the tokens of one method's body */
    private static function methodTokens(string $file, string $method): array
    {
        $source = file_get_contents($file);
        self::assertIsString($source, "cannot read {$file} — a shape gate that cannot read its target must fail");

        $tokens = array_values(array_filter(
            token_get_all($source),
            static fn ($t): bool => ! is_array($t) || $t[0] !== T_INLINE_HTML,
        ));

        $count = count($tokens);

        for ($i = 0; $i < $count; $i++) {
            if (! is_array($tokens[$i]) || $tokens[$i][0] !== T_FUNCTION) {
                continue;
            }

            $j = self::nextSignificant($tokens, $i + 1, $count);
            if ($j === null || ! is_array($tokens[$j]) || $tokens[$j][0] !== T_STRING || $tokens[$j][1] !== $method) {
                continue;
            }

            $body = self::bodyTokens($tokens, $j, $count);
            if ($body !== null) {
                return $body;
            }
        }

        self::fail("method {$method}() not found in {$file} — the shape gate must never pass vacuously");
    }

    /**
     * @param  list<array|string>  $tokens
     * @return list<array|string>
     */
    private static function bodyTokens(array $tokens, int $from, int $count): ?array
    {
        $depth = 0;
        $interp = 0;
        $start = null;

        for ($k = $from; $k < $count; $k++) {
            $token = $tokens[$k];

            if (is_array($token) && in_array($token[0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], true)) {
                $interp++;

                continue;
            }

            if ($token === '{') {
                $depth++;
                $start ??= $k;

                continue;
            }

            if ($token === '}') {
                if ($interp > 0) {
                    $interp--;

                    continue;
                }

                $depth--;

                if ($depth === 0 && $start !== null) {
                    return array_slice($tokens, $start, $k - $start + 1);
                }
            }
        }

        return null;
    }

    /**
     * @param  list<array|string>  $tokens
     */
    private static function nextSignificant(array $tokens, int $from, int $count): ?int
    {
        for ($j = $from; $j < $count; $j++) {
            $token = $tokens[$j];

            if ($token === '&') {
                continue;
            }

            if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            return $j;
        }

        return null;
    }

    /**
     * The method body with every comment and string literal blanked out, so
     * an operator assertion reads CODE and cannot be satisfied (or defeated)
     * by prose.
     *
     * @return list<array|string>
     */
    private static function codeTokensOf(string $file, string $method): array
    {
        return array_values(array_filter(
            self::methodTokens($file, $method),
            static fn ($t): bool => ! is_array($t)
                || ! in_array($t[0], [T_COMMENT, T_DOC_COMMENT, T_CONSTANT_ENCAPSED_STRING], true),
        ));
    }

    /**
     * Every operator token id in a body. PHP emits compound operators such as
     * `+=` as a T_PLUS_EQUAL array token; the plain-string form is mapped in
     * too so a re-tokenized file cannot slip past the check.
     *
     * @param  list<array|string>  $tokens
     * @return list<int>
     */
    private static function operatorTokenIds(array $tokens): array
    {
        $ids = [];

        foreach ($tokens as $token) {
            if (is_array($token)) {
                $ids[] = $token[0];

                continue;
            }

            if ($token === '+=') {
                $ids[] = T_PLUS_EQUAL;
            }
        }

        return $ids;
    }

    /**
     * @param  list<array|string>  $tokens
     * @return list<string>
     */
    private static function calledFunctions(array $tokens): array
    {
        $called = [];

        foreach ($tokens as $index => $token) {
            if (! is_array($token) || $token[0] !== T_STRING) {
                continue;
            }

            for ($next = $index + 1; $next < count($tokens); $next++) {
                $candidate = $tokens[$next];

                if ($candidate === '(') {
                    $called[] = $token[1];

                    break;
                }

                if ($candidate === '::' || $candidate === '->' || $candidate === '$') {
                    break;
                }
            }
        }

        return $called;
    }

    private static function codeTextOf(string $file, string $method): string
    {
        return self::render(self::codeTokensOf($file, $method));
    }

    /** Every comment on, or inside, one method — the method's docblock included. */
    private static function commentTextOf(string $file, string $method): string
    {
        $text = '';

        foreach (self::methodTokens($file, $method) as $token) {
            if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                $text .= ' '.$token[1];
            }
        }

        // The docblock sits BEFORE the function keyword, so it is not in the
        // body slice; pull the nearest preceding doc comment separately.
        $source = (string) file_get_contents($file);
        $tokens = token_get_all($source);
        $count = count($tokens);

        for ($i = 0; $i < $count; $i++) {
            if (! is_array($tokens[$i]) || $tokens[$i][0] !== T_FUNCTION) {
                continue;
            }

            $j = self::nextSignificant($tokens, $i + 1, $count);
            if ($j === null || ! is_array($tokens[$j]) || $tokens[$j][0] !== T_STRING || $tokens[$j][1] !== $method) {
                continue;
            }

            for ($back = $i - 1; $back >= 0; $back--) {
                if (is_array($tokens[$back]) && $tokens[$back][0] === T_DOC_COMMENT) {
                    $text .= ' '.$tokens[$back][1];

                    break;
                }

                if ($tokens[$back] === '}' || $tokens[$back] === ';') {
                    break;
                }
            }

            break;
        }

        return $text;
    }

    /** @param  list<array|string>  $tokens */
    private static function render(array $tokens): string
    {
        $out = '';

        foreach ($tokens as $token) {
            $out .= is_array($token) ? $token[1] : $token;
        }

        return $out;
    }
}

/** Records install() payloads verbatim — the write side of the union assertion. */
final class MergeRecordingSchedulePublisher implements SchedulePublisher
{
    /** @var array<string, array<string, mixed>> */
    public array $installed = [];

    public function install(array $schedules): array
    {
        foreach ($schedules as $id => $spec) {
            $this->installed[(string) $id] = (array) $spec;
        }

        return array_fill_keys(array_keys($schedules), 'installed');
    }

    public function describe(): string
    {
        return 'merge-recording schedule publisher (test)';
    }
}
