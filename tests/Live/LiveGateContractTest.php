<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Live;

use MeRezaRezaei\Teleframe\Core\MTProto\TL\ParsedSignature;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLRegistry;
use MeRezaRezaei\Teleframe\Core\Schema\MethodRegistry;
use MeRezaRezaei\Teleframe\Tests\Concerns\RunsLiveTelegram;
use MeRezaRezaei\Teleframe\Warm\LiveEndpointPolicy;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * THE GATE'S OWN GATE — and the only file in `tests/Live` that runs
 * unconditionally.
 *
 * Every other test in this directory is an *evidence* test: it needs a funded
 * Telegram account and therefore cannot run in CI. A set of tests that can only
 * run on the operator's machine, checking a gate that has never been observed
 * to fail, is the definition of an unproven gate (friction F5 in
 * `.brain/plans/2026-10-13-v2-end-to-end.md`).
 *
 * So the gate is itself gated — by this file, which needs no account, opens no
 * socket, and contacts nothing. It proves the properties that make the rest of
 * the lane trustworthy:
 *
 *   1. the force flag arms the lane on its OWN, before any credential is read;
 *   2. an armed lane FAILS by name rather than skipping;
 *   3. a missing variable is named and NO value is ever printed — canary-proven;
 *   4. the only `markTestSkipped` in the live tree is the sanctioned one, and no
 *      live test reads the environment by any route except the trait;
 *   5. the flow carve-out is a narrow second list, disjoint from the warm
 *      allowlist, and stays that way.
 *
 * Point 4 is why this file does not simply assert the rules it documents: it
 * scans the source for them, so a later commit that adds a skip or a raw env
 * read goes red on the commit that adds it.
 */
final class LiveGateContractTest extends TestCase
{
    use RunsLiveTelegram;

    /**
     * Every variable the gate reads. Planted with a canary in the leak tests and
     * cleared in the arming test, so "the gate consulted a credential" is
     * observable rather than assumed.
     *
     * @var list<string>
     */
    private const LIVE_VARIABLES = [
        'TELEFRAME_LIVE_TESTS',
        'TELEFRAME_LIVE_TESTS_SKIP',
        'TELEFRAME_LIVE_STEPS',
        'TELEFRAME_LIVE_DC_ID',
        'TELEFRAME_LIVE_REPORT',
        'TELEGRAM_API_ID',
        'TELEGRAM_API_HASH',
        'TELEGRAM_USER_SESSION',
        'TELEGRAM_BOT_TOKEN',
        'TELEFRAME_PASSPORT_PRIVATE_KEY',
        'TELEFRAME_LIVE_PASSPORT_DATA',
        'TELEFRAME_LIVE_PASSPORT_SECRET',
        'TELEFRAME_LIVE_PASSPORT_HASH',
        'TELEFRAME_LIVE_PHONE',
        'TELEFRAME_LIVE_PHONE_CODE',
        'TELEFRAME_LIVE_PHONE_CODE_HASH',
        'TELEFRAME_LIVE_PENDING_SESSION',
        'TELEFRAME_LIVE_LOGIN_TOKEN',
        'TELEFRAME_LIVE_DEVICE2_SESSION',
        'TELEFRAME_LIVE_VAULT_SET',
    ];

    /** A value that would be unmistakable in a leak. Not a credential. */
    private const CANARY = 'canary-9f2b7c41-not-a-secret-3e8a';

    /** @var array<string, string> */
    private array $originalEnvironment = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalEnvironment = self::liveEnvSnapshot(self::LIVE_VARIABLES);
        self::liveEnvUnset(self::LIVE_VARIABLES);
    }

    protected function tearDown(): void
    {
        self::liveEnvRestore($this->originalEnvironment);
        $this->originalEnvironment = [];

        parent::tearDown();
    }

    // ── 1. THE FORCE FLAG ARMS BEFORE ANYTHING ELSE ───────────────────────

    /**
     * THE ORDERING PROOF.
     *
     * `setUp()` has just cleared every live variable, so a predicate that read
     * even one credential before deciding would return false here — which is
     * precisely the bug that would turn "operator armed the lane without
     * exporting a session" into a green run full of skips. It must be true on
     * the strength of the force flag alone.
     */
    #[Test]
    public function the_force_flag_arms_the_gate_with_every_other_live_variable_unset(): void
    {
        putenv('TELEFRAME_LIVE_TESTS=1');

        self::assertTrue(
            self::liveGateArmed(),
            'a forced run must be armed on the force flag alone: if this is false the gate '
            .'consulted a credential before deciding, and a forced run with a missing '
            .'credential would SKIP instead of failing.',
        );
        self::assertSame('FORCED', self::liveGateLabel());
    }

    #[Test]
    public function the_gate_is_disarmed_when_nothing_is_set(): void
    {
        self::assertFalse(self::liveGateArmed());
        self::assertSame('DISARMED', self::liveGateLabel());
    }

    /** Mirrors `TELEFRAME_MYSQL_SKIP`: an explicit escape hatch that wins. */
    #[Test]
    public function the_explicit_skip_variable_overrides_the_force_flag(): void
    {
        putenv('TELEFRAME_LIVE_TESTS=1');
        putenv('TELEFRAME_LIVE_TESTS_SKIP=1');

        self::assertFalse(
            self::liveGateArmed(),
            'the explicit skip variable is the operator saying "not now" and must win, '
            .'exactly as TELEFRAME_MYSQL_SKIP wins over TELEFRAME_MYSQL.',
        );
    }

    /** Anything other than a literal `1` is not consent. */
    #[Test]
    public function only_a_literal_one_arms_the_gate(): void
    {
        foreach (['0', 'true', 'yes', 'on', ''] as $value) {
            putenv('TELEFRAME_LIVE_TESTS='.$value);
            self::assertFalse(
                self::liveGateArmed(),
                "TELEFRAME_LIVE_TESTS={$value} must not arm a lane that dials a real account.",
            );
        }

        putenv('TELEFRAME_LIVE_TESTS');
    }

    // ── 2. AN ARMED LANE FAILS BY NAME ────────────────────────────────────

    /**
     * THE PROOF THAT THE GATE CAN FAIL.
     *
     * Armed, and nothing declared. Every step must refuse loudly. This is the
     * exact shape of a real operator mistake — export the flag, forget the steps
     * — and the shape it must produce is a red run naming the way out, never a
     * run full of skips that reads as green.
     */
    #[Test]
    public function an_armed_lane_refuses_an_undeclared_step_by_name(): void
    {
        putenv('TELEFRAME_LIVE_TESTS=1');

        $thrown = null;
        try {
            self::requireLiveStep('wire_read');
        } catch (RuntimeException $e) {
            $thrown = $e;
        }

        self::assertInstanceOf(RuntimeException::class, $thrown, 'the gate must raise, not skip');
        $message = $thrown->getMessage();

        self::assertStringContainsString('wire_read', $message, 'the step must be named');
        self::assertStringContainsString('TELEFRAME_LIVE_STEPS', $message, 'the way out must be named');
        self::assertStringContainsString('loopback_dial', $message, 'the known steps must be listed');
        self::assertStringContainsString('NOTHING', $message, 'the empty declaration must be stated, not hidden');
    }

    #[Test]
    public function a_declared_step_passes_the_gate_without_contacting_anything(): void
    {
        putenv('TELEFRAME_LIVE_TESTS=1');
        putenv('TELEFRAME_LIVE_STEPS=loopback_dial, wire_read ,LOOPBACK_DIAL');

        self::assertSame(
            ['loopback_dial', 'wire_read'],
            self::liveDeclaredSteps(),
            'steps are trimmed, lower-cased and de-duplicated so a copied list cannot silently double-arm',
        );

        // Must not throw. This opens no socket; it is a declaration check only.
        self::requireLiveStep('wire_read');
    }

    #[Test]
    public function every_step_the_lane_defines_is_a_step_a_test_can_declare(): void
    {
        $known = self::liveKnownSteps();

        self::assertNotSame([], $known, 'a lane with no steps is a lane that cannot run');
        self::assertSame($known, array_values(array_unique($known)), 'step names must be unique');

        foreach (['loopback_dial', 'wire_read', 'dc_dial', 'auth_send_code', 'auth_sign_in',
            'auth_export_login_token', 'auth_import_login_token',
            'auth_import_bot_authorization', 'passport_decrypt', 'backup_vault', ] as $step) {
            self::assertContains($step, $known, "step \"{$step}\" is used by a test but not declared as known");
        }
    }

    /**
     * THE REGISTRATION PROOF — A KNOWN STEP IS REACHABLE, NOT MERELY DECLARED.
     *
     * Friction F12-4 cost three points for a claim that sounds narrower than it
     * is. The vault's live proof was not MISSING — `tests/Backup/LiveVaultSmokeTest.php`
     * existed and drove the real `scopeApi` map. It was UNREACHABLE: keyed on a
     * `TELECLIENT_LIVE` flag the shipped registry knows nothing about, so no
     * `TELEFRAME_LIVE_STEPS` declaration could ever select it, and no amount of
     * CI, rollup or documentation could route a run through the gate.
     *
     * A registry therefore proves nothing on its own. A step listed in
     * `liveKnownSteps()` that no test declares is a name an operator can put in
     * `TELEFRAME_LIVE_STEPS` and get a green no-op from, which is the same
     * failure wearing the opposite hat — and it is exactly what a class that
     * sits outside this lane looks like from the inside. So both directions are
     * checked here, on the tokens, over the real files:
     *
     *   - every KNOWN step is declared by a test that calls `requireLiveStep()`;
     *   - every step a test declares is KNOWN (a rename cannot leave a declared
     *     step that resolves to nothing).
     *
     * A lane with steps that no test declares, or a test declaring steps the
     * lane has never heard of, is a registration gap and goes red on the commit
     * that opens it rather than on the commit that notices it a week later.
     */
    #[Test]
    public function every_registered_step_is_reachable_from_a_test_that_declares_it(): void
    {
        $declared = [];

        foreach ($this->liveLaneSources() as $label => $source) {
            if (basename($label) === 'RunsLiveTelegram.php') {
                continue;
            }

            foreach ($this->stepArgumentsIn($source) as $step) {
                $declared[$step][] = basename($label);
            }
        }

        $known = self::liveKnownSteps();

        $unreachable = [];
        foreach ($known as $step) {
            if (! isset($declared[$step])) {
                $unreachable[] = $step;
            }
        }

        self::assertSame(
            [],
            $unreachable,
            'these steps are registered but NO test declares them with requireLiveStep(), so an operator '
            .'can name them in TELEFRAME_LIVE_STEPS and get a green no-op: '
            .($unreachable === [] ? '' : implode(', ', $unreachable))
            .'. A step is a route to a test; with no test it is a word in a list.',
        );

        $unknown = [];
        foreach (array_keys($declared) as $step) {
            if (! in_array($step, $known, true)) {
                $unknown[] = $step.' (in '.implode(', ', $declared[$step]).')';
            }
        }

        self::assertSame(
            [],
            $unknown,
            'these tests declare steps the registry does not know, so an armed run fails with "not '
            .'declared" naming a step the lane has never heard of: '
            .($unknown === [] ? '' : implode(', ', $unknown)),
        );
    }

    /**
     * A STEP THAT DECLARES A METHOD SURFACE IS GATED ON THAT SURFACE.
     *
     * `requireLiveStep()` refuses a step while any method in
     * `liveStepMethods()` sits on neither allowlist, so a declared step cannot
     * reach the wire with a method the fence never heard of. That is a
     * property of the REGISTRY, so it is asserted here rather than inside a
     * test that would only prove it about itself: every declared surface method
     * must resolve in the shipped catalogue, must be permitted by one of the two
     * lists, and must remain refused by the warm lane.
     *
     * The third assertion is the one that keeps F12-4's fix honest. The vault's
     * nine methods are permitted HERE because they are scoped to a channel the
     * step owns; if one of them ever appears in `LiveEndpointPolicy::LIVE_SAFE`,
     * the warm fence has been widened by the back door and the two lists are no
     * longer disjoint.
     */
    #[Test]
    public function every_declared_step_surface_is_permitted_and_keeps_the_warm_fence_closed(): void
    {
        $surface = self::liveStepMethods();

        self::assertNotSame(
            [],
            $surface,
            'a step surface exists — the vault step is the reason this map is here, and an empty map '
            .'means requireLiveStep() is gating steps on their NAME alone again',
        );

        foreach ($surface as $step => $methods) {
            self::assertContains(
                $step,
                self::liveKnownSteps(),
                "step \"{$step}\" declares a method surface but is not a known step, so requireLiveStep() "
                .'can never reach the surface check for it',
            );

            self::assertNotSame([], $methods, "step \"{$step}\" declares an EMPTY surface");

            foreach ($methods as $method) {
                self::assertTrue(
                    MethodRegistry::isCallable($method),
                    "step \"{$step}\" declares {$method}, which the method registry will not dial by name — "
                    .'the step would fail before a socket opens, for a reason that has nothing to do with the account',
                );
                self::assertInstanceOf(
                    ParsedSignature::class,
                    TLRegistry::signatureOf($method),
                    "{$method} must carry a TL signature or it can never reach the wire",
                );

                // The gate itself: this is the assertion requireLiveStep() makes,
                // made here so a future edit to the surface cannot quietly pass.
                self::assertLivePolicyAllows($method);

                self::assertNotNull(
                    LiveEndpointPolicy::refusalReason($method),
                    "{$method} is live-eligible for step \"{$step}\" and is ALSO on the warm allowlist — the "
                    .'two lists must stay disjoint, or the warm fence has been widened by the back door',
                );
            }
        }
    }

    // ── 3. THE GATE REFUSES TO SKIP WHEN ARMED ────────────────────────────

    /**
     * If `skipUnlessArmed()` skipped anyway under the force flag, this test
     * would be reported as SKIPPED rather than passed — which is the tell. It is
     * asserted here so the skip count in a rollup cannot quietly start meaning
     * something else.
     */
    #[Test]
    public function the_sanctioned_skip_does_not_fire_when_the_lane_is_armed(): void
    {
        putenv('TELEFRAME_LIVE_TESTS=1');

        // Returns without skipping. Had it skipped, THIS test would be reported
        // skipped and this file's skip count would rise by one.
        self::skipUnlessArmed('contract_probe');

        self::assertTrue(true, 'reached only if the sanctioned skip stayed silent under the force flag');
    }

    // ── 4. NO VALUE IS EVER PRINTED ───────────────────────────────────────

    /**
     * THE CANARY TEST. A missing variable is refused by name; the refusal
     * cannot contain a value because there is none, and it must not contain any
     * OTHER variable's value either.
     */
    #[Test]
    public function a_missing_variable_is_named_and_no_value_appears(): void
    {
        putenv('TELEGRAM_API_HASH='.self::CANARY);

        $thrown = null;
        try {
            self::liveEnvRequired('TELEGRAM_API_ID', 'the api_id every MTProto connection is keyed on');
        } catch (RuntimeException $e) {
            $thrown = $e;
        }

        self::assertInstanceOf(RuntimeException::class, $thrown);
        $message = $thrown->getMessage();

        self::assertStringContainsString('TELEGRAM_API_ID', $message, 'the missing variable must be named');
        self::assertStringContainsString(
            'the api_id every MTProto connection is keyed on',
            $message,
            'the refusal must say what the variable is FOR, or the operator cannot act on it',
        );
        self::assertStringNotContainsString(
            self::CANARY,
            $message,
            'a live gate must never emit any environment value, not even a neighbouring one',
        );
    }

    /** The presence helper is a boolean, not a value and not a length. */
    #[Test]
    public function presence_is_reported_as_a_boolean_and_never_as_a_value(): void
    {
        putenv('TELEGRAM_USER_SESSION='.self::CANARY);

        self::assertSame('SET', self::liveEnvPresence('TELEGRAM_USER_SESSION'));
        self::assertSame('UNSET', self::liveEnvPresence('TELEGRAM_BOT_TOKEN'));

        self::assertStringNotContainsString(self::CANARY, self::liveEnvPresence('TELEGRAM_USER_SESSION'));
    }

    /** A present value IS returned to the caller — that is how the gate works. */
    #[Test]
    public function a_present_value_is_returned_rather_than_reported(): void
    {
        putenv('TELEGRAM_API_ID=12345');

        self::assertSame('12345', self::liveEnvRequired('TELEGRAM_API_ID', 'a test id'));
    }

    /** SET-EMPTY is the same operational fact as UNSET: there is no credential. */
    #[Test]
    public function an_empty_variable_is_treated_as_absent_rather_than_defaulted(): void
    {
        putenv('TELEGRAM_API_HASH=');

        self::assertNull(self::liveEnv('TELEGRAM_API_HASH'));

        $this->expectException(RuntimeException::class);
        self::liveEnvRequired('TELEGRAM_API_HASH', 'a test hash');
    }

    /**
     * The missing-credential path an operator actually hits: armed, with no
     * session exported. The client builder must raise a named failure, not
     * return a half-configured client that will fail later against Telegram.
     */
    #[Test]
    public function an_armed_run_with_no_credentials_fails_by_name_at_client_construction(): void
    {
        putenv('TELEFRAME_LIVE_TESTS=1');

        $thrown = null;
        try {
            self::liveUserClient();
        } catch (RuntimeException $e) {
            $thrown = $e;
        }

        self::assertInstanceOf(RuntimeException::class, $thrown, 'a forced run with no api_id must fail, not skip');
        self::assertStringContainsString('TELEGRAM_API_ID', $thrown->getMessage());
        self::assertStringNotContainsString(self::CANARY, $thrown->getMessage());
    }

    /** A non-numeric api_id is refused by name, and its value is not echoed. */
    #[Test]
    public function a_malformed_api_id_is_refused_without_printing_it(): void
    {
        putenv('TELEFRAME_LIVE_TESTS=1');
        putenv('TELEGRAM_API_ID='.self::CANARY);

        $thrown = null;
        try {
            self::liveUserClient();
        } catch (RuntimeException $e) {
            $thrown = $e;
        }

        self::assertInstanceOf(RuntimeException::class, $thrown);
        self::assertStringContainsString('TELEGRAM_API_ID', $thrown->getMessage());
        self::assertStringNotContainsString(self::CANARY, $thrown->getMessage());
    }

    /** A placeholder session must fail here, not at a production handshake. */
    #[Test]
    public function a_placeholder_session_is_refused_before_any_socket(): void
    {
        putenv('TELEFRAME_LIVE_TESTS=1');
        putenv('TELEGRAM_API_ID=12345');
        putenv('TELEGRAM_API_HASH='.self::CANARY);

        // base64("2:not-a-real-auth-key:7:0:0") — well-formed, and useless.
        putenv('TELEGRAM_USER_SESSION='.base64_encode('2:not-a-real-auth-key:7:0:0'));

        $thrown = null;
        try {
            self::liveUserClient();
        } catch (RuntimeException $e) {
            $thrown = $e;
        }

        self::assertInstanceOf(RuntimeException::class, $thrown);
        self::assertStringContainsString('256-byte auth key', $thrown->getMessage());
        self::assertStringNotContainsString(
            self::CANARY,
            $thrown->getMessage(),
            'the api_hash must not appear because it was read successfully — a successful read is returned, not reported',
        );
        self::assertStringNotContainsString('not-a-real-auth-key', $thrown->getMessage());
    }

    // ── 5. THE FLOW CARVE-OUT STAYS A CARVE-OUT ───────────────────────────

    /**
     * THE POLICY ASSERTIONS, all in one place because they are one question:
     * may this lane dial anything the warm lane forbids?
     *
     * The answer is yes, for a named and small set — and this test is what stops
     * "a named and small set" from becoming "everything, by accretion".
     */
    #[Test]
    public function the_live_flow_carve_out_is_a_carve_out_and_not_a_widening(): void
    {
        $flow = self::liveFlowMethods();

        self::assertNotSame([], $flow, 'the carve-out exists for the operator-named flows');

        foreach ($flow as $method => $meta) {
            self::assertArrayHasKey('why', $meta, "{$method} has no reason — an allowlist without a "
                .'reason becomes an allowlist without a review');
            self::assertNotSame('', trim((string) $meta['why']), "{$method} carries an empty reason");
            self::assertContains(
                $meta['risk'],
                ['quota', 'human', 'authorization'],
                "{$method} has risk \"{$meta['risk']}\", which is outside the declared vocabulary. "
                .'A new risk level is a deliberate act and must be spelled out here.',
            );

            // Disjointness: these are the methods the WARM lane refuses. If one
            // ever appears in both lists, the carve-out has quietly become the
            // allowlist and the warm fence is no longer the blast-radius boundary.
            self::assertFalse(
                LiveEndpointPolicy::isLiveSafe($method),
                "{$method} is in LiveEndpointPolicy::LIVE_SAFE as well as the carve-out — the two lists "
                .'must stay disjoint so the warm fence remains the only thing a re-run can widen.',
            );

            self::assertNotNull(
                LiveEndpointPolicy::refusalReason($method),
                "{$method} must be refused by the warm lane, otherwise it does not need a carve-out",
            );

            // Not a typo: the method must really exist in the shipped catalogue.
            try {
                TLRegistry::signatureOf($method);
            } catch (\InvalidArgumentException $e) {
                self::fail("{$method} is in the live carve-out but has no TL signature: ".$e->getMessage());
            }
        }

        self::assertCount(
            14,
            $flow,
            'the carve-out is 14 methods: the five the operator named, plus the nine the `backup_vault` '
            .'step dials. A different number means the live surface was widened or narrowed without '
            .'saying so — read the diff on liveFlowMethods() and give each new entry a reason.',
        );

        // Nothing that can end an authorization, ever.
        foreach (['account.deleteAccount', 'auth.resetAuthorizations', 'auth.logOut'] as $fatal) {
            self::assertArrayNotHasKey(
                $fatal,
                $flow,
                "{$fatal} must never be live-eligible, in either list",
            );
        }
    }

    /**
     * A method on neither list is refused at the gate, by name and by reason.
     *
     * The exemplar is `messages.deleteHistory` and it is deliberately NOT
     * `messages.sendMessage`. That one used to stand here, and it stopped being a
     * valid example the moment `backup_vault` was registered: it posts the vault
     * manifest, so it is now on the carve-out with a reason. Swapping the
     * exemplar rather than deleting the assertion is the point — the method the
     * gate stopped refusing is visible HERE, in the diff that widened the
     * carve-out, instead of quietly becoming untestable.
     */
    #[Test]
    public function a_method_on_neither_list_is_refused_at_the_gate(): void
    {
        self::assertLivePolicyAllows('help.getConfig');
        self::assertLivePolicyAllows('auth.sendCode');

        $thrown = null;
        try {
            self::assertLivePolicyAllows('messages.deleteHistory');
        } catch (RuntimeException $e) {
            $thrown = $e;
        }

        self::assertInstanceOf(RuntimeException::class, $thrown);
        self::assertStringContainsString('messages.deleteHistory', $thrown->getMessage());
        self::assertStringContainsString('DENIED', $thrown->getMessage());
    }

    // ── 6. THE RULES ARE ENFORCED ON THE SOURCE, NOT TRUSTED ──────────────

    /**
     * THE SKIP BAN, MACHINE-CHECKED.
     *
     * Scanned over TOKENS, not over text. A docblock that explains the ban must
     * not be mistaken for a violation of it, and neither must a string that
     * names the call. `codeCalls()` drops comments and literals before counting,
     * so what is counted is what PHP would actually execute.
     *
     * Closed in both directions: exactly one call, in the trait, and it must sit
     * inside `skipUnlessArmed()` — after the arm check, so an armed run cannot
     * reach it.
     */
    #[Test]
    public function the_skip_call_appears_only_in_the_sanctioned_helper(): void
    {
        $needle = 'markTestSkipped';
        $trait = $this->traitSource();
        $traitSource = (string) file_get_contents($trait);

        $calls = $this->codeCalls($traitSource, $needle);
        self::assertCount(
            1,
            $calls,
            'the trait must contain exactly one call to the skip API. Zero means the lane cannot '
            .'report a disarmed run at all; more than one means a second route to a green-looking skip.',
        );

        $helperAt = $this->codeCalls($traitSource, 'skipUnlessArmed')[0] ?? 0;
        self::assertGreaterThan(0, $helperAt, 'the sanctioned helper must exist in the trait');
        self::assertGreaterThan(
            $helperAt,
            $calls[0],
            'the skip must sit AFTER skipUnlessArmed() is declared — a call earlier in the file '
            .'could be reached by an armed run.',
        );

        $offenders = [];
        foreach ($this->liveLaneSources() as $label => $source) {
            if ($label === $trait) {
                continue;
            }

            foreach ($this->codeCalls($source, $needle) as $line) {
                $offenders[] = basename($label).':'.$line;
            }
        }

        self::assertSame(
            [],
            $offenders,
            'these live-lane files execute the skip API, and a skipped live test reads as green in a '
            .'CI rollup: '.($offenders === [] ? '' : implode(', ', $offenders)).'. The only sanctioned '
            .'skip is RunsLiveTelegram::skipUnlessArmed(), reachable only when the lane is DISARMED.',
        );
    }

    /**
     * NO RAW ENV READS OUTSIDE THE TRAIT.
     *
     * A live test that consulted the environment itself could not be reasoned
     * about: it could read a credential and format it however it liked. All
     * reads go through the trait, which is where the redaction lives.
     */
    #[Test]
    public function no_live_test_reads_the_environment_outside_the_trait(): void
    {
        $offenders = [];

        foreach ($this->liveLaneSources() as $label => $source) {
            if ($label === $this->traitSource()) {
                continue; // the trait is where the reads belong
            }

            foreach ($this->codeCalls($source, 'getenv') as $line) {
                $offenders[] = basename($label).':'.$line;
            }
        }

        self::assertSame(
            [],
            $offenders,
            'these lines read the environment directly instead of through the live gate, which is '
            .'the only place the redaction is enforced: '
            .($offenders === [] ? '' : implode(', ', $offenders)),
        );
    }

    /**
     * EVERY LIVE TEST THAT OPTS INTO THE GATE MUST PASS THROUGH IT.
     *
     * Scoped to files that import `RunsLiveTelegram`, because importing it is
     * what grants access to `liveEnv()`: a file that imports it without calling
     * `requireLiveStep()` has a credential reader and no refuse-to-skip
     * guarantee. A file that does not import it cannot read a credential at all
     * — which is the position `LiveWarmFenceTest` (the pre-existing
     * warm-ledger fence) is already in, and why it is not this rule's business.
     *
     * Two exemptions, and the list is closed: a rename cannot quietly widen it.
     */
    #[Test]
    public function every_live_test_that_opts_into_the_gate_passes_through_the_step_gate(): void
    {
        $exempt = ['LiveGateContractTest.php', 'LiveLayerGapReportTest.php'];

        foreach ($exempt as $file) {
            self::assertFileExists(
                __DIR__.'/'.$file,
                "exempt file {$file} does not exist — the exemption list must name files that are "
                .'really there, so a rename cannot turn a gated file into an exempt one.',
            );
        }

        $offenders = [];

        foreach ($this->liveLaneSources() as $label => $source) {
            $base = basename($label);

            if (in_array($base, $exempt, true) || $base === 'RunsLiveTelegram.php') {
                continue;
            }

            if ($this->codeCalls($source, 'RunsLiveTelegram') === []) {
                continue; // no gate access, therefore no credential reader either
            }

            if ($this->codeCalls($source, 'requireLiveStep') === []) {
                $offenders[] = $base;
            }
        }

        self::assertSame(
            [],
            $offenders,
            'these files import the live gate but never call requireLiveStep(), so they have no '
            .'opt-in and no refuse-to-skip guarantee: '.($offenders === [] ? '' : implode(', ', $offenders)),
        );
    }

    /**
     * THE CARVE-OUT MUST NAME METHODS THE PACKAGE CAN ACTUALLY DIAL.
     *
     * A carve-out entry for a method the client refuses for a THIRD reason — a
     * layer gap, an envelope, a service request — would send an operator to run
     * a live step that cannot succeed, and the failure would look like an
     * account problem. Each entry is asserted callable, so the carve-out is
     * always a subset of what the wire accepts.
     */
    #[Test]
    public function every_carve_out_method_is_dialable_and_refused_only_by_the_warm_policy(): void
    {
        foreach (self::liveFlowMethods() as $method => $meta) {
            self::assertTrue(
                MethodRegistry::isCallable($method),
                "{$method} is in the live carve-out but the method registry will not dial it by name — "
                .'running it live would fail before a socket opens, for a reason that has nothing to do with the account',
            );
            // Throws when absent, which is the assertion: a carve-out entry with
            // no TL signature could never be encoded, let alone sent.
            self::assertInstanceOf(
                ParsedSignature::class,
                TLRegistry::signatureOf($method),
                "{$method} must carry a TL signature or it can never reach the wire",
            );
            self::assertNotNull(
                LiveEndpointPolicy::refusalReason($method),
                "{$method} must be refused by the warm lane, otherwise it does not belong in a carve-out",
            );
            self::assertArrayHasKey('risk', $meta);
        }
    }

    // ── internals ─────────────────────────────────────────────────────────

    private function traitSource(): string
    {
        $trait = dirname(__DIR__).'/Concerns/RunsLiveTelegram.php';
        self::assertFileExists($trait, 'the live gate trait must exist — this file is its own gate');

        return $trait;
    }

    /**
     * Every PHP file in the live lane: the trait plus `tests/Live/**`.
     *
     * @return array<string, string> absolute path => source
     */
    private function liveLaneSources(): array
    {
        $trait = $this->traitSource();

        $files = [$trait];
        foreach ((array) glob(__DIR__.'/*.php') as $path) {
            $files[] = (string) $path;
        }

        $sources = [];
        foreach ($files as $path) {
            $source = (string) file_get_contents($path);
            self::assertNotSame('', $source, "{$path} must be readable");
            $sources[$path] = $source;
        }

        return $sources;
    }

    /**
     * Lines at which `$identifier` appears as CODE — called, or declared.
     *
     * Comments, docblocks, string literals and inline HTML are dropped, because
     * this file has to be able to talk about the rules it enforces without
     * tripping them, and a docblock that says "no skipping here" must not read
     * as a skip.
     *
     * Both token shapes are matched, and the difference matters: an import is
     * one `T_NAME_QUALIFIED` token, while a bare call is `T_STRING`. Matching
     * only `T_STRING` would make every `use RunsLiveTelegram;` invisible, and
     * the "opted into the gate" check would then pass vacuously.
     *
     * @return list<int> 1-based line numbers, in source order
     */
    private function codeCalls(string $source, string $identifier): array
    {
        $tokens = token_get_all($source);
        $count = count($tokens);

        $lines = [];
        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];

            if (! is_array($token)) {
                continue;
            }

            $bare = $token[0] === T_STRING && $token[1] === $identifier;
            $qualified = $token[0] === T_NAME_QUALIFIED
                && str_ends_with($token[1], '\\'.$identifier);

            if (! $bare && ! $qualified) {
                continue;
            }

            $lines[] = $token[2];
        }

        return $lines;
    }

    /**
     * The STRING ARGUMENTS a source passes to `requireLiveStep()`.
     *
     * Token-scanned for the same reason `codeCalls()` is: a docblock that says
     * `requireLiveStep('wire_read')` while explaining why must not count as a
     * test declaring that step, or the reachability check above would pass on a
     * comment. Matching the T_STRING call, skipping to its opening parenthesis,
     * and reading the following string literal is what makes "this test really
     * declares this step" a fact about the code.
     *
     * @return list<string> the lower-cased step names, in source order
     */
    private function stepArgumentsIn(string $source): array
    {
        $tokens = token_get_all($source);
        $count = count($tokens);

        $steps = [];
        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];

            if (! is_array($token) || $token[0] !== T_STRING || $token[1] !== 'requireLiveStep') {
                continue;
            }

            for ($j = $i + 1; $j < $count; $j++) {
                $next = $tokens[$j];

                if (is_array($next) && in_array($next[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                    continue;
                }

                if ($next !== '(') {
                    break;
                }

                for ($k = $j + 1; $k < $count; $k++) {
                    $argument = $tokens[$k];

                    if (is_array($argument) && in_array($argument[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                        continue;
                    }

                    if (is_array($argument) && $argument[0] === T_CONSTANT_ENCAPSED_STRING) {
                        $steps[] = strtolower(trim($argument[1], "'\""));
                    }

                    break;
                }

                break;
            }
        }

        return $steps;
    }
}
