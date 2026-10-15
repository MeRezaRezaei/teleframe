<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Concerns;

use InvalidArgumentException;
use MeRezaRezaei\Teleframe\Core\MTProto\Client;
use MeRezaRezaei\Teleframe\Core\MTProto\SessionData;
use MeRezaRezaei\Teleframe\Warm\LiveEndpointPolicy;
use RuntimeException;

/**
 * THE LIVE GATE — the opt-in seam that turns 972 resolved-but-never-dialled
 * methods into evidence somebody can take.
 *
 * ## THE GAP THIS EXISTS TO CLOSE
 *
 * Measured 2026-10-13 (`.brain/plans/2026-10-13-v2-end-to-end.md`, friction F5):
 * 972 methods resolve against the committed catalogue and the TL mirror, and
 * **not one has ever been dialed on a live connection**. A correct-looking
 * CRC32 in a checked-in file is not proof Telegram accepts it. Before this
 * trait there was no `TELEFRAME_LIVE*` opt-in variable in the whole test tree
 * — every `TELEFRAME_*` gate was a PG or MySQL container — so "capped" was not
 * merely unmet, it was **unenforceable**.
 *
 * `tests/Live/LiveWarmFenceTest.php` is NOT that gate: it is a warm-ledger and
 * endpoint-policy test, it constructs nothing and dials nothing.
 *
 * ## WHY ONE FLAG, AND WHY IT IS NOT `TELEFRAME_LIVE`
 *
 * `TELEFRAME_LIVE` already exists and already means something else: it is
 * `config('teleframe.live_mode')` (`src/Laravel/config/teleframe.php:88`),
 * the flag a HOST sets to make its own application dial Telegram. Reusing it
 * for the test gate would arm a live test lane as a side effect of configuring
 * an app — the operator exports it once to run production and a later
 * `composer verify` would silently start spending account quota. Two knobs
 * that mean "live" must not share a name, so this one is
 * `TELEFRAME_LIVE_TESTS`, and it reads nothing else to decide.
 *
 * ## THE RULE THIS TRAIT EXISTS TO ENFORCE: A FORCED RUN FAILS, NEVER SKIPS
 *
 * `liveGateArmed()` returns `true` on the strength of the force variable
 * ALONE — before any socket is opened, before any credential is read, before
 * any other variable is consulted. That ordering is the whole design and it is
 * copied from `RunsMysqlMigrations::mysqlTrackEnabled()` (`tests/Concerns/
 * RunsMysqlMigrations.php:147`), which returns on `TELEFRAME_MYSQL=1` for the
 * same reason: **an opt-in run that silently passes proves nothing, and a
 * missing credential must surface as the real error rather than as a skip.**
 *
 * So the failure modes are:
 *
 *   - armed + a required variable missing  → `RuntimeException` naming the
 *     VARIABLE. Never a skip. Never a value.
 *   - armed + a step not declared          → `RuntimeException` naming the STEP.
 *   - DISARMED                              → the single `markTestSkipped()`
 *     in this trait, and nothing else.
 *
 * `markTestSkipped` is BANNED everywhere else, and the ban is machine-checked
 * rather than trusted: `tests/Live/LiveGateContractTest.php` scans this file
 * and every sibling in `tests/Live/` and fails if the skip call appears
 * anywhere except in `skipUnlessArmed()` below, or if any live test reads the
 * environment by any route other than `liveEnv()` here.
 *
 * ## WHY A STEP DECLARATION EXISTS ON TOP OF THE FORCE FLAG
 *
 * A live lane is not one indivisible act: `help.getConfig` costs one read,
 * `auth.sendCode` sends a real SMS and `auth.signIn` consumes a real code.
 * Requiring all-or-nothing would make the cheap proof unobtainable. So a forced
 * run must ALSO say which steps it is running, in `TELEFRAME_LIVE_STEPS`.
 * A step that is neither declared nor... — there is no third case: under the
 * force flag an undeclared step **fails loudly by name**. A partial run is
 * therefore always a *declared* partial run, never an accidental one, and the
 * count of skipped tests can never stand in for a passed live test.
 *
 * ## THE POLICY CARVE-OUT, AND WHY IT IS NOT A WIDENING
 *
 * `LiveEndpointPolicy::LIVE_SAFE` is deny-by-default and tiny, which is right:
 * most of the surface is wire plumbing the offline suite already covers without
 * a session. But the three flows the operator named as load-bearing are all
 * denied by it — `auth.sendCode` and `auth.sendMessage`-shaped verbs are in
 * `DESTRUCTIVE_VERBS`, and the policy deliberately refuses anything needing an
 * interactive code. That refusal is correct for the WARM lane, which re-runs a
 * commit against an already-working session, and wrong for a one-off evidence
 * run a human is standing next to.
 *
 * So this trait declares `liveFlowMethods()` — the second, narrow, separately
 * auditable list — and `assertLivePolicyAllows()` accepts a method only if it is
 * in EITHER list. The two are proven DISJOINT by
 * `LiveGateContractTest::test_the_live_flow_carve_out_is_a_carve_out_and_not_a_widening()`,
 * which also pins: every entry carries a reason and a risk, the account-
 * destroying methods are absent by name, and the list is small enough that
 * widening it is a visible diff rather than an accident.
 *
 * ## CREDENTIALS ARE READ, NEVER ECHOED
 *
 * `liveEnvRequired()` returns a value to its caller and NEVER interpolates one
 * into a message, an exception, an assertion or a report. Its refusal names the
 * VARIABLE and the PURPOSE and says nothing else. `liveRedactionAudit()` exists
 * so this is checkable rather than promised: it plants a canary in every live
 * variable and asserts no code path emits it.
 *
 * This trait never reads `.env` and never loads Laravel: it is `getenv()` only,
 * so an unset variable means unset, not "whatever the app last wrote to disk".
 */
trait RunsLiveTelegram
{
    /** The force variable. Armed means TRUE, checked before anything else. */
    protected static function liveForceVariable(): string
    {
        return 'TELEFRAME_LIVE_TESTS';
    }

    /** The explicit escape hatch, mirroring `TELEFRAME_MYSQL_SKIP`. */
    protected static function liveSkipVariable(): string
    {
        return 'TELEFRAME_LIVE_TESTS_SKIP';
    }

    /** Comma-separated list of the steps a forced run is actually attempting. */
    protected static function liveStepsVariable(): string
    {
        return 'TELEFRAME_LIVE_STEPS';
    }

    // ── ENVIRONMENT ──────────────────────────────────────────────────────

    /**
     * The ONE way a live test reads a variable.
     *
     * UNSET and SET-EMPTY collapse to `null` on purpose: for this trait an empty
     * API hash and an absent one are the same operational fact — there is no
     * credential — and `RunsMysqlMigrations` already made this exact argument
     * for its password (`tests/Concerns/RunsMysqlMigrations.php:77-102`).
     * The value is trimmed because an accidental trailing newline in a shell
     * export is the most common way a real credential stops working.
     */
    protected static function liveEnv(string $name): ?string
    {
        $raw = getenv($name);

        if ($raw === false) {
            return null;
        }

        $trimmed = trim($raw);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * A credential the gate cannot run without.
     *
     * The refusal NAMES THE VARIABLE AND ITS PURPOSE and never, under any
     * circumstance, contains the variable's value — because on the failing path
     * there is no value, and on the succeeding path this function returns rather
     * than reports. There is deliberately no "here is what I got" branch.
     *
     * @throws RuntimeException naming $name, never its value
     */
    protected static function liveEnvRequired(string $name, string $purpose): string
    {
        $value = self::liveEnv($name);

        if ($value === null) {
            throw new RuntimeException(sprintf(
                'live gate: environment variable %s is required — %s. '
                .'Export it in the shell that runs this test. The gate never reads .env, '
                .'never guesses a default, and never prints a value: the refusal can name '
                .'the variable because the value does not exist, and if it did exist it '
                .'would be returned to the caller rather than reported.',
                $name,
                rtrim($purpose, " \t\n\r."),
            ));
        }

        return $value;
    }

    /** `'SET'` / `'UNSET'` — presence only, never a value and never a length. */
    protected static function liveEnvPresence(string $name): string
    {
        return self::liveEnv($name) === null ? 'UNSET' : 'SET';
    }

    /**
     * Remember the current state of the named variables so a test that
     * rearranges the environment can put it back exactly.
     *
     * This exists so that NO live test needs its own environment read. A test
     * that called the platform call itself would be a place where a value
     * could be formatted, printed or logged, and
     * `LiveGateContractTest::no_live_test_reads_the_environment_outside_the_trait()`
     * fails the build if one appears. Reads belong here, where the redaction is.
     *
     * @param  list<string>  $names
     * @return array<string, string|null> name => value, or null when it was unset
     */
    protected static function liveEnvSnapshot(array $names): array
    {
        $snapshot = [];
        foreach ($names as $name) {
            $raw = getenv($name);
            $snapshot[$name] = $raw === false ? null : $raw;
        }

        return $snapshot;
    }

    /**
     * Restore a snapshot, distinguishing UNSET from SET-EMPTY.
     *
     * `getenv()` returns `false` when a variable is absent and `''` when it is
     * set to empty; collapsing the two is what produced the 56-error CI run
     * recorded at `tests/Concerns/RunsMysqlMigrations.php:86-102`, so it is not
     * collapsed here either.
     *
     * @param  array<string, string|null>  $snapshot
     */
    protected static function liveEnvRestore(array $snapshot): void
    {
        foreach ($snapshot as $name => $value) {
            if ($value === null) {
                putenv($name);

                continue;
            }

            putenv($name.'='.$value);
        }
    }

    /**
     * Clear the named variables for the duration of a test.
     *
     * @param  list<string>  $names
     */
    protected static function liveEnvUnset(array $names): void
    {
        foreach ($names as $name) {
            putenv($name);
        }
    }

    // ── THE GATE ──────────────────────────────────────────────────────────

    /**
     * TRUE when the live lane must run.
     *
     * ORDER IS THE CONTRACT. The force variable is honoured FIRST and on its
     * own, before any credential is read and before any socket could be opened:
     * a forced run must raise the real missing-credential error instead of
     * quietly deciding it is not configured. If this method ever consulted a
     * credential, an operator who armed the lane without one would get a skip.
     *
     * `LiveGateContractTest` pins the ordering by arming the lane with every
     * other live variable UNSET and asserting this still returns true.
     */
    protected static function liveGateArmed(): bool
    {
        if (self::liveEnv(self::liveSkipVariable()) === '1') {
            return false;
        }

        if (self::liveEnv(self::liveForceVariable()) === '1') {
            return true;
        }

        return false;
    }

    /** `'FORCED'` or `'DISARMED'` — for messages only, never for control flow. */
    protected static function liveGateLabel(): string
    {
        return self::liveGateArmed() ? 'FORCED' : 'DISARMED';
    }

    /**
     * THE ONLY `markTestSkipped()` IN THE LIVE TREE.
     *
     * Reachable only when the lane is DISARMED, and only for that reason. Under
     * the force flag this returns without skipping, and the caller then hits
     * `requireLiveStep()` / `liveEnvRequired()` and fails. The skip count in a
     * CI rollup therefore means exactly "this lane was not armed" and never
     * "this lane tried and could not".
     */
    protected static function skipUnlessArmed(string $step): void
    {
        if (self::liveGateArmed()) {
            return;
        }

        self::markTestSkipped(sprintf(
            'live lane DISARMED (step "%s" not attempted). Set %s=1 to arm it, and %s to '
            .'declare the steps you are running. This skip means "not armed" — never '
            .'"attempted and passed".',
            $step,
            self::liveForceVariable(),
            self::liveStepsVariable(),
        ));
    }

    /** @return list<string> lower-cased, de-duplicated step names */
    protected static function liveDeclaredSteps(): array
    {
        $raw = self::liveEnv(self::liveStepsVariable());

        if ($raw === null) {
            return [];
        }

        $steps = [];
        foreach (explode(',', $raw) as $piece) {
            $step = strtolower(trim($piece));

            if ($step !== '' && ! in_array($step, $steps, true)) {
                $steps[] = $step;
            }
        }

        return $steps;
    }

    /**
     * The gate a live test calls instead of skipping.
     *
     * Disarmed → skip (via the one sanctioned call). Armed but undeclared →
     * FAIL by name, because a forced run that quietly drops the step you did
     * not name is exactly the silent-skip failure this lane exists to prevent.
     *
     * @throws RuntimeException when armed and the step was not declared
     */
    protected static function requireLiveStep(string $step): void
    {
        self::skipUnlessArmed($step);

        if (! in_array($step, self::liveDeclaredSteps(), true)) {
            $declared = self::liveDeclaredSteps();
            $known = self::liveKnownSteps();

            throw new RuntimeException(sprintf(
                'live gate is FORCED (%s=1) but step "%s" is not declared in %s. '
                .'A forced run must say out loud which live steps it is attempting, so that a '
                .'partial run can never be reported as a whole one. Declare it: %s=%s '
                .'(currently declared: %s). Known steps: %s.',
                self::liveForceVariable(),
                $step,
                self::liveStepsVariable(),
                self::liveStepsVariable(),
                implode(',', $known),
                $declared === [] ? 'NOTHING' : implode(',', $declared),
                implode(',', $known),
            ));
        }
    }

    /** @return list<string> every step this tree understands */
    protected static function liveKnownSteps(): array
    {
        return [
            'loopback_dial',
            'wire_read',
            'dc_dial',
            'auth_send_code',
            'auth_sign_in',
            'auth_export_login_token',
            'auth_import_login_token',
            'auth_import_bot_authorization',
            'passport_decrypt',
        ];
    }

    // ── THE POLICY ────────────────────────────────────────────────────────

    /**
     * THE SECOND LIST. Methods the warm lane refuses, that a one-off evidence
     * run must still be able to exercise.
     *
     * Every entry carries a reason and a risk, because an allowlist without a
     * reason becomes an allowlist without a review — the same rule
     * `LiveEndpointPolicy` holds itself to. Risk vocabulary is deliberately
     * narrow and load-bearing:
     *
     *   `quota`          — spends a real rate-limit budget; repeatable.
     *   `human`          — cannot complete without a person (an SMS code, a
     *                      second phone) and therefore has no unattended value.
     *   `authorization`  — creates, mints or installs a session authorization.
     *                      These can invalidate or duplicate another device's
     *                      login, which is why each one is named here rather
     *                      than allowed by a verb rule.
     *
     * `destroy` is NOT a member of that vocabulary, and nothing that can end an
     * authorization belongs in this list at all.
     *
     * @return array<string, array{why: string, risk: string}>
     */
    protected static function liveFlowMethods(): array
    {
        return [
            'auth.sendCode' => [
                'why' => 'Sends a real login code to a real phone. It creates no authorization and '
                    .'can be abandoned; the cost is one SMS and one flood-wait budget.',
                'risk' => 'quota',
            ],
            'auth.signIn' => [
                'why' => 'Exchanges the code for an auth.authorization. Needs a human at the phone, '
                    .'so it has no unattended value and must be driven deliberately.',
                'risk' => 'human',
            ],
            'auth.exportLoginToken' => [
                'why' => 'Mints the QR login token that a SECOND device imports. This is the half of '
                    .'the device-transfer proof that only the source account can produce.',
                'risk' => 'authorization',
            ],
            'auth.importLoginToken' => [
                'why' => 'The other half: installs an authorization on a second device. Proves the '
                    .'exported token is genuinely transferable and not a self-referential fake.',
                'risk' => 'authorization',
            ],
            'auth.importBotAuthorization' => [
                'why' => 'The entire bot-over-MTProto story rests on this one call and it has NO live '
                    .'evidence of any kind. A token-minted session on a real DC is the only thing '
                    .'that can prove the bot transport is not a local fabrication.',
                'risk' => 'authorization',
            ],
        ];
    }

    /**
     * The method is in the warm allowlist OR in the flow carve-out. Nothing else
     * may be dialled from a live test, so a typo or an un-reviewed addition
     * fails at the gate rather than at Telegram.
     *
     * @throws RuntimeException naming $method and the reason it is refused
     */
    protected static function assertLivePolicyAllows(string $method): void
    {
        if (LiveEndpointPolicy::isLiveSafe($method)) {
            return;
        }

        if (isset(self::liveFlowMethods()[$method])) {
            return;
        }

        throw new RuntimeException(sprintf(
            'live gate refuses to dial "%s": %s It is not in LiveEndpointPolicy::LIVE_SAFE and not in '
            .'the RunsLiveTelegram flow carve-out. A live test may only dial a method that is on one of '
            .'those two lists, so widening what this lane can touch is a deliberate, reviewable diff.',
            $method,
            (string) LiveEndpointPolicy::refusalReason($method),
        ));
    }

    // ── THE CLIENTS ───────────────────────────────────────────────────────

    /**
     * A LIVE client for a user session.
     *
     * Built from explicit environment only: no `.env`, no config helper, no
     * Laravel container, so the values the gate asserts on are provably the
     * ones the operator exported. `live: true` is passed to the CONSTRUCTOR
     * rather than reached through `->live()`, because the tri-state default
     * would otherwise defer to `config('teleframe.live_mode')` — and a live
     * gate that silently inherited an app's setting is not a gate.
     *
     * The session string is validated for real shape without ever being
     * printed: a 256-byte auth key and a DC in range. A truncated or
     * placeholder session fails here, by name, rather than halfway through a
     * handshake against a production DC.
     *
     * @throws RuntimeException naming the missing or malformed variable
     */
    protected static function liveUserClient(): Client
    {
        $apiId = self::liveEnvRequired('TELEGRAM_API_ID', 'the api_id every MTProto connection is keyed on');

        if (! ctype_digit($apiId) || (int) $apiId <= 0) {
            throw new RuntimeException(
                'live gate: environment variable TELEGRAM_API_ID is present but is not a positive '
                .'integer. Its value is not printed by this gate. Export the numeric id only.'
            );
        }

        $apiHash = self::liveEnvRequired('TELEGRAM_API_HASH', 'the api_hash paired with TELEGRAM_API_ID');

        $sessionString = self::liveEnvRequired(
            'TELEGRAM_USER_SESSION',
            'the exported session string (base64 dcId:authKey:userId:delta:salt) for the funded test account',
        );

        try {
            $session = SessionData::importString($sessionString);
        } catch (InvalidArgumentException $e) {
            throw new RuntimeException(sprintf(
                'live gate: TELEGRAM_USER_SESSION is set but is not a valid session string (%s). '
                .'Its value is never printed. Re-export it from the funded account and try again.',
                $e->getMessage(),
            ), 0, $e);
        }

        self::assertLiveSessionIsReal($session);

        return new Client(
            apiId: (int) $apiId,
            apiHash: $apiHash,
            session: $session,
            live: true,
        );
    }

    /**
     * A LIVE bot client for `auth.importBotAuthorization`.
     *
     * Built with an EMPTY auth key on purpose: that is the shape
     * `TeleframeAuthService::loginBot()` uses (`src/Laravel/Services/
     * TeleframeAuthService.php:449-456`), and it makes the client run the real
     * DH handshake instead of inheriting a key from somewhere else.
     */
    protected static function liveBotClient(): Client
    {
        $apiId = self::liveEnvRequired('TELEGRAM_API_ID', 'the api_id every MTProto connection is keyed on');

        if (! ctype_digit($apiId) || (int) $apiId <= 0) {
            throw new RuntimeException(
                'live gate: environment variable TELEGRAM_API_ID is present but is not a positive '
                .'integer. Its value is not printed by this gate. Export the numeric id only.'
            );
        }

        $apiHash = self::liveEnvRequired('TELEGRAM_API_HASH', 'the api_hash paired with TELEGRAM_API_ID');
        $botToken = self::liveEnvRequired(
            'TELEGRAM_BOT_TOKEN',
            'the bot token auth.importBotAuthorization will present to Telegram',
        );
        $dcId = self::liveDcId();

        return new Client(
            apiId: (int) $apiId,
            apiHash: $apiHash,
            session: new SessionData(dcId: $dcId, authKey: ''),
            live: true,
        );
    }

    /** The bot token, for the ONE method that needs it. Never printed. */
    protected static function liveBotToken(): string
    {
        return self::liveEnvRequired(
            'TELEGRAM_BOT_TOKEN',
            'the bot token auth.importBotAuthorization will present to Telegram',
        );
    }

    /** `TELEFRAME_LIVE_DC_ID`, defaulting to DC 2 the way the package does. */
    protected static function liveDcId(): int
    {
        $raw = self::liveEnv('TELEFRAME_LIVE_DC_ID');

        if ($raw === null) {
            return 2;
        }

        if (! ctype_digit($raw) || ! isset(Client::DC_IPS[(int) $raw])) {
            throw new RuntimeException(sprintf(
                'live gate: TELEFRAME_LIVE_DC_ID is set but is not a DC this client knows. '
                .'Its value is not printed. Valid DCs are %s.',
                implode(', ', array_keys(Client::DC_IPS)),
            ));
        }

        return (int) $raw;
    }

    /**
     * Prove the sentinel is a real credential, without revealing it.
     *
     * A placeholder session (`authKey: ''`, or 32 hex characters someone pasted
     * from a doc) would let a "live" run sail through setup and fail much later
     * against a production DC, which is the worst place to find out. The two
     * assertions are the shape of an authorized session and nothing more: a
     * 256-byte auth key and a DC in range. No length, no prefix, no value.
     */
    protected static function assertLiveSessionIsReal(SessionData $session): void
    {
        if (strlen($session->authKey) !== 256) {
            throw new RuntimeException(
                'live gate: TELEGRAM_USER_SESSION does not carry a 256-byte auth key, so it is not an '
                .'authorized session — it would force a fresh DH handshake and never authenticate. '
                .'Its contents are never printed. Export the session from the funded account.'
            );
        }

        if (! isset(Client::DC_IPS[$session->dcId])) {
            throw new RuntimeException(
                'live gate: TELEGRAM_USER_SESSION names a DC this client does not know. '
                .'Its contents are never printed.'
            );
        }
    }

    // ── REPORTING ─────────────────────────────────────────────────────────

    /**
     * Where a report lands. Never inside the repo tree — a live report is full
     * of method names and error strings and has no business in git.
     */
    protected static function liveReportPath(): string
    {
        return self::liveEnv('TELEFRAME_LIVE_REPORT')
            ?? sys_get_temp_dir().'/teleframe-live-report-'.getmypid().'.md';
    }

    /**
     * Append a named section to the report and announce the path.
     *
     * The announcement is the path and nothing else. Report BODIES are written
     * by the callers, which is why `LivePassportDecryptTest` and
     * `LiveAuthFlowTest` put no credential in them — and why the redaction
     * audit walks the finished file looking for exactly that.
     */
    protected static function liveAppendReport(string $section, string $body): void
    {
        $path = self::liveReportPath();

        $header = sprintf(
            "\n## %s\n\n_generated %s · gate %s · steps declared: %s_\n\n",
            $section,
            gmdate('c'),
            self::liveGateLabel(),
            self::liveDeclaredSteps() === [] ? 'NONE' : implode(',', self::liveDeclaredSteps()),
        );

        if (is_file($path)) {
            file_put_contents($path, $header.$body."\n", FILE_APPEND);
        } else {
            file_put_contents($path, "# teleframe live gate report\n".$header.$body."\n");
        }

        fwrite(STDOUT, sprintf(
            "\n[live-gate] report section \"%s\" appended to %s\n",
            $section,
            $path,
        ));
    }

    /**
     * A loopback port with nothing listening on it.
     *
     * Used by the `loopback_dial` step to prove the dial path RUNS and REFUSES
     * without touching Telegram: a real `stream_socket_client` attempt, a real
     * connection refusal, zero traffic off the machine. 9 is the discard port,
     * which is conventionally closed; the assertion does not depend on that,
     * it only requires that nothing is there.
     */
    protected static function liveLoopbackDialTarget(): array
    {
        return ['127.0.0.1', 9];
    }
}
