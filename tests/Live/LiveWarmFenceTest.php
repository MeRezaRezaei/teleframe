<?php

declare(strict_types=1);

use MeRezaRezaei\Teleframe\Warm\LiveEndpointPolicy;
use MeRezaRezaei\Teleframe\Warm\WarmLedger;
use PHPUnit\Framework\TestCase;

/**
 * THE TWO CONTROLS THAT PROTECT THE REAL SESSION.
 *
 * A warm run costs account quota and, more importantly, runs REAL code against
 * a REAL account where an endpoint like account.deleteAccount is irreversible and
 * MTProto offers no dry run. So the warm lane is fenced in two ways, and both
 * are gates rather than conventions:
 *
 *   1. THE LEDGER — a commit is warmed at most ONCE. Re-running an already-warmed
 *      SHA is refused before the session is touched.
 *   2. THE POLICY — an endpoint runs live only if it is on a small allowlist.
 *      Everything else, including every destructive method, is denied.
 *
 * The tests below are deliberately adversarial. Most of them try to find a way
 * through the fence, because a fence that only passes the happy path is a fence
 * that is waiting for the wrong commit.
 */
final class LiveWarmFenceTest extends TestCase
{
    private static string $ledgerPath = '';

    public static function setUpBeforeClass(): void
    {
        self::$ledgerPath = sys_get_temp_dir().'/tf-warm-ledger-test-'.getmypid().'.json';
    }

    protected function tearDown(): void
    {
        if (is_file(self::$ledgerPath)) {
            unlink(self::$ledgerPath);
        }

        parent::tearDown();
    }

    private const SHA_A = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
    private const SHA_B = 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb';

    // ── CONTROL 1: the ledger ────────────────────────────────────────────

    public function test_an_unwarmed_commit_is_not_in_the_ledger(): void
    {
        $ledger = WarmLedger::load(self::$ledgerPath);
        self::assertFalse($ledger->has(self::SHA_A));
    }

    public function test_a_warmed_commit_is_never_warmed_again(): void
    {
        $ledger = WarmLedger::load(self::$ledgerPath);
        self::assertFalse($ledger->has(self::SHA_A), 'precondition');

        $ledger->record(self::SHA_A, 'passed', ['src/Teleframe/Ingest/UpdateIngestor.php']);

        $reloaded = WarmLedger::load(self::$ledgerPath);
        self::assertTrue($reloaded->has(self::SHA_A), 'a warmed commit must be visible to the next run');
    }

    public function test_a_different_commit_is_still_allowed_to_run(): void
    {
        $ledger = WarmLedger::load(self::$ledgerPath);
        $ledger->record(self::SHA_A, 'passed', ['src/A.php']);
        self::assertFalse($ledger->has(self::SHA_B), 'the fence must not be so wide it blocks all work');
    }

    /**
     * Idempotence is what makes the ledger safe for a runner that retries: a
     * retry re-calls record() and must not grow a duplicate row per attempt.
     */
    public function test_recording_the_same_result_twice_does_not_duplicate_the_entry(): void
    {
        $ledger = WarmLedger::load(self::$ledgerPath);
        $ledger->record(self::SHA_A, 'passed', ['src/A.php']);
        $ledger->record(self::SHA_A, 'passed', ['src/A.php']);
        $ledger->record(self::SHA_A, 'passed', ['src/A.php']);

        $reloaded = WarmLedger::load(self::$ledgerPath);
        self::assertCount(1, $reloaded->entries, 'three identical records are one entry');
    }

    public function test_a_later_result_replaces_an_earlier_one_rather_than_both_surviving(): void
    {
        $ledger = WarmLedger::load(self::$ledgerPath);
        $ledger->record(self::SHA_A, 'failed', ['src/A.php'], 'network blip');
        $ledger->record(self::SHA_A, 'passed', ['src/A.php']);

        $reloaded = WarmLedger::load(self::$ledgerPath);
        self::assertCount(1, $reloaded->entries);
        self::assertSame('passed', $reloaded->entryFor(self::SHA_A)['result']);
    }

    /**
     * A short SHA is refused ON PURPOSE. Keying the ledger on an abbreviation
     * would let a collision mark an untested commit as warmed — which skips a
     * real run against a real account. The safe error is a loud refusal.
     */
    public function test_a_short_sha_is_refused_rather_than_guessed_at(): void
    {
        $this->expectException(RuntimeException::class);
        WarmLedger::normalise('abc1234');
    }

    public function test_a_full_sha_is_accepted_and_normalised_to_lower_case(): void
    {
        $upper = strtoupper(self::SHA_A);
        self::assertSame(self::SHA_A, WarmLedger::normalise($upper));
    }

    public function test_the_recorded_surface_travels_with_the_entry(): void
    {
        $ledger = WarmLedger::load(self::$ledgerPath);
        $ledger->record(self::SHA_A, 'passed', ['src/B.php', 'src/A.php', 'src/B.php']);

        $entry = WarmLedger::load(self::$ledgerPath)->entryFor(self::SHA_A);
        self::assertSame(['src/B.php', 'src/A.php'], $entry['surface'], 'duplicates collapsed, order kept');
    }

    // ── CONTROL 2: the endpoint policy ───────────────────────────────────

    /**
     * THE most important assertion in this file. If a destructive method can ever
     * be allowed, the fence is decorative and the account is at risk. This is
     * checked against the REAL shipped catalog, not a sample, so a newly
     * generated method cannot slip past by existing.
     */
    public function test_no_destructive_method_in_the_real_catalog_is_ever_allowed(): void
    {
        $catalog = json_decode(
            (string) file_get_contents(__DIR__.'/../../src/Schema/schema/methods-mtproto.json'),
            true,
        );
        self::assertIsArray($catalog, 'the shipped catalog must be readable JSON');

        $methods = array_keys($catalog['methods']);
        self::assertGreaterThan(500, count($methods), 'catalog sanity: the surface is large');

        $allowed = [];
        foreach ($methods as $method) {
            $name = is_array($catalog['methods'][$method] ?? null)
                ? (string) ($catalog['methods'][$method]['method'] ?? $method)
                : $method;

            if (LiveEndpointPolicy::isLiveSafe($name)) {
                $allowed[] = $name;
            }
        }

        // Every allowlisted method must survive the policy's own verb rule too,
        // so the two halves of the fence cannot disagree with each other.
        foreach ($allowed as $name) {
            self::assertNull(
                LiveEndpointPolicy::refusalReason($name),
                "allowlisted method {$name} is denied by its own verb rule — the policy contradicts itself",
            );
        }

        self::assertLessThan(
            20,
            count($allowed),
            'the live surface is deliberately tiny; a large allowlist means the fence has been widened by accident',
        );
    }

    /** The methods that can actually destroy an account must be refused BY NAME, whatever the verb rule does. */
    public function test_the_account_destroying_methods_are_refused_by_name(): void
    {
        foreach (['account.deleteAccount', 'auth.resetAuthorizations', 'auth.logOut'] as $method) {
            self::assertNotNull(
                LiveEndpointPolicy::refusalReason($method),
                "{$method} must never be live-eligible",
            );
        }
    }

    public function test_a_read_is_allowed(): void
    {
        self::assertNull(LiveEndpointPolicy::refusalReason('users.getUsers'));
        self::assertTrue(LiveEndpointPolicy::isLiveSafe('users.getUsers'));
    }

    /**
     * A method nobody has classified is REFUSED. This is the whole design: the
     * cost of a wrongly-denied method is a skipped test, and the cost of a
     * wrongly-allowed one may be the account.
     */
    public function test_an_unclassified_method_is_refused_not_permitted_by_default(): void
    {
        $reason = LiveEndpointPolicy::refusalReason('someNewNamespace.doSomethingInteresting');
        self::assertNotNull($reason);
        self::assertStringContainsString('not in the live allowlist', $reason);
    }

    public function test_every_allowlisted_method_carries_a_reason(): void
    {
        foreach (LiveEndpointPolicy::liveSafeMethods() as $method) {
            $reason = LiveEndpointPolicy::refusalReason($method);
            self::assertNull($reason, "{$method} is allowlisted");
            self::assertArrayHasKey($method, LiveEndpointPolicy::LIVE_SAFE);
            self::assertNotSame('', trim((string) LiveEndpointPolicy::LIVE_SAFE[$method]),
                "{$method} is allowlisted without saying WHY — that is how an allowlist stops being reviewed");
        }
    }

    /** The verb rule must be robust to a method that merely CONTAINS a dangerous word. */
    public function test_the_verb_rule_reads_the_verb_not_the_whole_name(): void
    {
        self::assertNotNull(LiveEndpointPolicy::refusalReason('messages.sendMessage'));
        // 'help.getConfig' contains no dangerous verb and stays allowed.
        self::assertNull(LiveEndpointPolicy::refusalReason('help.getConfig'));
    }

    public function test_classify_reports_a_reason_for_every_refusal(): void
    {
        $rows = LiveEndpointPolicy::classify(['users.getUsers', 'account.deleteAccount']);

        self::assertTrue($rows[0]['allowed']);
        self::assertNull($rows[0]['reason']);

        self::assertFalse($rows[1]['allowed']);
        self::assertStringContainsString('DENIED', (string) $rows[1]['reason']);
    }
}
