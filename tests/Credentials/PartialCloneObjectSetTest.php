<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Credentials;

use PHPUnit\Framework\TestCase;

/**
 * A PARTIAL (promisor) clone is a truncation the shallow check cannot see.
 *
 * `d8afe6cd` taught the guard to tell a SHALLOW clone from a complete one.
 * It named PARTIAL clones as a known gap rather than shipping them untested,
 * and the previous agent could not construct one under the suite's
 * GIT_CONFIG_GLOBAL=/dev/null isolation. They are constructible — the missing
 * ingredient was `uploadpack.allowFilter` on the SOURCE repository, without
 * which git prints "filtering not recognized by server, ignoring" and hands
 * back a complete clone wearing a promisor config.
 *
 * A partial clone is a WORSE case than a shallow one, not a milder one:
 *
 *   shallow  — `git rev-list --objects` FAILS (exit 128) when an object is
 *              absent. The failure is available to be noticed.
 *   partial  — the enumeration SUCCEEDS. Missing entries are quietly dropped,
 *              `rev-parse --is-shallow-repository` says "false", and reading
 *              an absent object either silently FETCHES it (reachable remote)
 *              or reports nothing at all (unreachable remote). Both come out
 *              of the guard looking exactly like "nothing there".
 *
 * That is the failure mode this file exists to close: **a missing object
 * reported as an absence of a finding.** Proven on a real repository before
 * the fix — same source, same secret, three object sets:
 *
 *   full clone                          → exit 1, history@leak.txt
 *   partial, promisor remote reachable  → exit 1 (git fetched the blob)
 *   partial, promisor remote BROKEN     → exit 0, "credential-guard: clean",
 *                                          while printing "object set COMPLETE
 *                                          — not shallow" and "history depth
 *                                          all reachable from all"
 *
 * The last line is the incident: a green gate over a repository holding a live
 * credential, produced by the guard itself.
 *
 * Every direction is asserted. Detection that only fires on a partial clone is
 * a broken gate with extra noise, and one that fires on a complete repository
 * is a gate nobody keeps switched on — so the complete direction is asserted
 * as hard as the partial one, INCLUDING a partial clone whose blobs have all
 * been fetched, which must be allowed to pass.
 *
 * Run just this file:  vendor/bin/phpunit tests/Credentials/PartialCloneObjectSetTest.php
 */
final class PartialCloneObjectSetTest extends TestCase
{
    /** @var list<ScanRunner> */
    private array $made = [];

    protected function tearDown(): void
    {
        foreach ($this->made as $repo) {
            $repo->destroy();
        }
        $this->made = [];
    }

    private function keep(ScanRunner $repo): ScanRunner
    {
        $this->made[] = $repo;

        return $repo;
    }

    private static function script(): string
    {
        return dirname(__DIR__, 2).'/bin/scan-credentials.php';
    }

    /**
     * A clean repository with $n commits, nothing sensitive in it.
     */
    private function cleanRepo(int $n = 4): ScanRunner
    {
        $repo = $this->keep(ScanRunner::repo(self::script()));
        $repo->write('README.md', "# scratch\n\nnothing sensitive here\n");
        $repo->commit('c1');
        for ($i = 2; $i <= $n; $i++) {
            $repo->write("note{$i}.md", "note {$i}\n");
            $repo->commit("c{$i}");
        }

        return $repo;
    }

    /**
     * The incident, in miniature: a credential committed once and redacted in
     * the tree afterwards, so only a HISTORY scan can find it. That is the
     * SEC-1 shape and the only reason the history pass exists.
     */
    private function repoWithRedactedLeak(): ScanRunner
    {
        $repo = $this->keep(ScanRunner::repo(self::script()));
        $repo->write('pad1.md', "filler\n");
        $repo->commit('init');
        $repo->write('leak.txt', ScanRunner::fakeRun());
        $repo->commit('the mistake');
        $repo->write('leak.txt', "[REDACTED]\n");
        $repo->write('pad2.md', "filler\n");
        $repo->commit('redact it');

        return $repo;
    }

    // -----------------------------------------------------------------------
    // The incident itself.
    // -----------------------------------------------------------------------

    /**
     * THE TEST. The promisor remote is broken, so the missing blob cannot be
     * fetched, so the guard cannot read it — and before the fix it answered
     * "clean" and exited 0 while claiming complete coverage.
     */
    public function test_a_partial_clone_whose_promisor_is_unreachable_does_not_report_clean(): void
    {
        $partial = $this->repoWithRedactedLeak()->partialClone();
        $partial->breakPromisorRemote();

        $r = $partial->scan(['--history-baseline=none']);

        self::assertSame(3, $r['code'], "a missing object is not an absence of a finding:\n".$r['all']);
        self::assertStringNotContainsString('credential-guard: clean', $r['all']);
        self::assertStringNotContainsString('PASS with', $r['all']);
        self::assertStringContainsString('INCOMPLETE', $r['all']);
        self::assertStringContainsString('PARTIAL', $r['all']);
    }

    /**
     * The full control, in the same test file and the same shape: the same
     * source, the same secret, a complete object set. This is what the partial
     * run is being compared against, and it is asserted so that a change which
     * "fixes" the partial case by breaking the complete case cannot pass.
     */
    public function test_the_same_repository_as_a_full_clone_still_fails_on_the_finding(): void
    {
        $repo = $this->repoWithRedactedLeak();
        $dir = dirname($repo->sandbox()).'/'.basename($repo->sandbox()).'-full-'.bin2hex(random_bytes(4));
        $repo->git(['clone', '--quiet', '--no-tags', 'file://'.$repo->sandbox(), $dir]);
        $full = $this->keep(new ScanRunner(self::script(), $dir));

        $r = $full->scan(['--history-baseline=none']);

        self::assertSame(1, $r['code'], "the complete object set finds what the partial one could not:\n".$r['all']);
        self::assertStringContainsString('history@leak.txt', $r['all']);
        self::assertStringNotContainsString('PARTIAL', $r['all']);
    }

    // -----------------------------------------------------------------------
    // The measurement: a number, not a reassurance.
    // -----------------------------------------------------------------------

    public function test_the_partial_run_states_how_many_objects_are_actually_absent(): void
    {
        $partial = $this->cleanRepo(4)->partialClone();
        $partial->breakPromisorRemote();

        $r = $partial->scan();
        $absent = $partial->missingObjects();
        $total = $partial->enumeratedObjects();

        self::assertGreaterThan(0, $absent, 'the sandbox clone really is missing objects');
        // Checkable against `git rev-list --all --objects --missing=print`
        // piped through grep -c '^?' — not a reassurance. The counts are
        // "absent of total", in that order.
        self::assertStringContainsString("{$absent} of {$total} reachable object(s) are ABSENT", $r['all']);
        self::assertStringContainsString('were not read', $r['all'], 'and what is lost is named');
    }

    public function test_the_partial_run_names_the_promisor_remote_and_its_filter(): void
    {
        $partial = $this->cleanRepo(4)->partialClone();
        $partial->breakPromisorRemote();

        $r = $partial->scan();

        // The operator needs to know WHY it is partial in order to fix it.
        self::assertStringContainsString('promisor remote origin', $r['all']);
        self::assertStringContainsString('filter blob:none', $r['all']);
    }

    // -----------------------------------------------------------------------
    // The coverage lines must never contradict the run they belong to.
    // -----------------------------------------------------------------------

    public function test_a_partial_run_never_prints_the_unbounded_coverage_claim(): void
    {
        $partial = $this->cleanRepo(4)->partialClone();
        $partial->breakPromisorRemote();

        $r = $partial->scan();

        // The three sentences that were false in the incident. Their absence
        // is the fix; each one is asserted because each one was printed.
        self::assertStringNotContainsString('history depth all reachable', $r['all']);
        self::assertStringNotContainsString('object set COMPLETE', $r['all']);
        // "PARTIAL — not shallow" is the honest summary, but the trailing
        // "PARTIAL as well" clause is a self-contradiction next to it: the run
        // says the set is partial, then hedges as if the verdict were optional.
        self::assertStringNotContainsString('object set PARTIAL — not shallow); PARTIAL as well', $r['all']);
    }

    public function test_the_depth_line_carries_the_truncation_not_merely_a_pointer_to_it(): void
    {
        $partial = $this->cleanRepo(4)->partialClone();
        $partial->breakPromisorRemote();

        $r = $partial->scan();

        // The depth line is what a reader skims. If it only said "see the
        // summary above", the skimmer keeps the false belief.
        $depth = array_values(array_filter(
            explode("\n", $r['all']),
            static fn (string $l): bool => str_contains($l, 'coverage:'),
        ));
        self::assertCount(1, $depth);
        self::assertStringContainsString('TRUNCATED', $depth[0]);
        self::assertStringContainsString('PARTIAL', $depth[0]);
        self::assertStringContainsString('ABSENT', $depth[0]);
    }

    // -----------------------------------------------------------------------
    // A finding must still be a finding. This is the ordering rule: FAIL beats
    // INCOMPLETE, and a caller is never handed "incomplete" in place of "there
    // is a problem".
    // -----------------------------------------------------------------------

    public function test_a_finding_in_a_partial_clone_still_exits_one(): void
    {
        // A partial clone whose blobs have been fetched still HAS the finding.
        // The incompleteness must not swallow it.
        $partial = $this->repoWithRedactedLeak()->partialClone();
        foreach ($this->presentableShas($partial) as $sha) {
            $partial->git(['cat-file', 'blob', $sha]);
        }

        $r = $partial->scan(['--history-baseline=none']);

        self::assertSame(1, $r['code'], "a finding is a finding even in a partial scan:\n".$r['all']);
        self::assertStringContainsString('history@leak.txt', $r['all']);
    }

    public function test_a_readable_finding_in_a_truncated_run_is_reported_before_the_verdict(): void
    {
        // The ordering rule, in the shape that actually arises: a partial clone
        // whose working tree HOLDS a live credential while the history beside it
        // is unreadable. The finding is found — and the run is also incomplete.
        // FAIL must outrank INCOMPLETE, or a caller is handed "incomplete" in
        // place of "there is a problem", which is the inversion d8afe6cd fixed
        // for the shallow axis and which must not reopen on this one.
        $repo = $this->keep(ScanRunner::repo(self::script()));
        $repo->write('leak.txt', ScanRunner::fakeRun());
        $repo->commit('the mistake');
        foreach (['a', 'b', 'c', 'd'] as $i => $letter) {
            $repo->write("note{$letter}.md", "version {$i} of {$letter}\n");
            $repo->commit("revise {$letter}");
        }
        $partial = $repo->partialCloneWith('blob:none', ['leak.txt']);

        $r = $partial->scan(['--history-baseline=none']);

        self::assertSame(1, $r['code'], "a finding outranks an incomplete scan:\n".$r['all']);
        self::assertStringContainsString('leak.txt', $r['all']);
        self::assertStringContainsString('INCOMPLETE', $r['all'], 'and the truncation is still disclosed');
        self::assertStringNotContainsString('credential-guard: clean', $r['all']);
    }

    /** @return list<string> */
    private function presentableShas(ScanRunner $repo): array
    {
        $env = 'GIT_NO_LAZY_FETCH=1';
        $shas = [];
        foreach (explode("\n", (string) shell_exec($env.' git -C '.escapeshellarg($repo->sandbox()).' rev-list --all --objects --missing=print')) as $line) {
            if (str_starts_with($line, '?')) {
                $shas[] = substr(trim(substr($line, 1)), 0, 40);
            }
        }

        return $shas;
    }

    // -----------------------------------------------------------------------
    // The complete direction. A guard that called every repository partial
    // would be switched off within a day, so this is asserted as hard.
    // -----------------------------------------------------------------------

    public function test_a_complete_repository_is_not_called_partial(): void
    {
        $r = $this->cleanRepo(4)->scan();

        self::assertSame(0, $r['code'], "a complete object set is a normal pass:\n".$r['all']);
        self::assertStringContainsString('credential-guard: clean', $r['out']);
        self::assertStringContainsString('object set COMPLETE — not shallow', $r['out']);
        self::assertStringContainsString('history depth all reachable from all', $r['out']);
        self::assertStringNotContainsString('PARTIAL', $r['all']);
        self::assertStringNotContainsString('promisor', $r['all']);
    }

    public function test_a_full_clone_of_the_real_repository_is_not_called_partial(): void
    {
        $r = (new ScanRunner(self::script(), dirname(__DIR__, 2)))->scan();

        self::assertSame(0, $r['code'], $r['all']);
        self::assertStringNotContainsString('PARTIAL', $r['all']);
        self::assertStringContainsString('no object absent', $r['out']);
    }

    /**
     * The over-trigger guard that matters most: a partial clone whose objects
     * have ALL been fetched is, right now, complete. The verdict keys on the
     * MEASUREMENT, not on the config — a gate that cried wolf on a
     * fully-materialised partial clone would be disabled by anyone who used
     * `--filter` for speed.
     */
    public function test_a_partial_clone_whose_objects_are_all_present_is_not_called_truncated(): void
    {
        $source = $this->cleanRepo(4);
        $partial = $source->partialClone();
        $partial->breakPromisorRemote();
        self::assertGreaterThan(0, $partial->missingObjects(), 'it starts partial');

        // Put the promisor remote back and fetch: the objects arrive, and the
        // clone becomes, for now, complete. This is the over-trigger guard
        // that matters most — the verdict keys on the MEASUREMENT, not on the
        // config, because a gate that cried wolf on a fully-materialised
        // partial clone would be switched off by anyone using --filter for
        // speed.
        $partial->git(['remote', 'set-url', 'origin', 'source://']);
        $partial->git(['fetch', '--quiet', '--refetch', 'file://'.$source->sandbox()]);
        self::assertSame(0, $partial->missingObjects(), 'every object is present now');

        $r = $partial->scan();

        self::assertSame(0, $r['code'], "nothing is missing, so nothing is incomplete:\n".$r['all']);
        self::assertStringContainsString('object set COMPLETE — not shallow', $r['out']);
        self::assertStringNotContainsString('TRUNCATED', $r['all']);
        // It is still DISCLOSED as a promisor clone: the completeness is a
        // property of the current network, not of the clone, and the next gc
        // can take it away. Disclosed, not fatal.
        self::assertStringContainsString('promisor/partial clone', $r['all']);
    }

    private function sourceOf(ScanRunner $partial): string
    {
        $dir = dirname($partial->sandbox());
        $name = preg_replace('/-(blobnone|tree0)-[0-9a-f]{8}$/', '', basename($partial->sandbox()));
        self::assertNotNull($name);

        return $dir.'/'.$name;
    }

    // -----------------------------------------------------------------------
    // Both truncations at once. `git clone --depth 20 --filter=blob:none` is
    // an entirely ordinary thing to write in CI, and reporting only one of the
    // two would name the truncation the operator can see and hide the one they
    // cannot.
    // -----------------------------------------------------------------------

    public function test_a_shallow_and_partial_clone_reports_both_truncations(): void
    {
        $repo = $this->keep(ScanRunner::repo(self::script()));
        $repo->write('pad1.md', "filler\n");
        $repo->commit('c1');
        $repo->write('pad2.md', "filler\n");
        $repo->commit('c2');
        $repo->write('pad3.md', "filler\n");
        $repo->commit('c3');
        $repo->config('uploadpack.allowFilter', 'true');
        $dir = dirname($repo->sandbox()).'/'.basename($repo->sandbox()).'-both-'.bin2hex(random_bytes(4));
        $repo->git(['clone', '--quiet', '--depth', '1', '--filter', 'blob:none', '--no-checkout', '--no-tags', 'file://'.$repo->sandbox(), $dir]);
        $both = $this->keep(new ScanRunner(self::script(), $dir));

        $r = $both->scan();

        self::assertSame(3, $r['code'], $r['all']);
        self::assertStringContainsString('SHALLOW', $r['all'], 'the shallow boundary is named');
        self::assertStringContainsString('PARTIAL', $r['all'], 'and so is the absent-object count');
        self::assertStringContainsString('ABSENT', $r['all']);
        // Both reasons are on the INCOMPLETE line, not just one of them.
        self::assertStringContainsString('SHALLOW clone', $r['err']);
        self::assertStringContainsString('PARTIAL', $r['err']);
    }

    // -----------------------------------------------------------------------
    // The measurement must not be defeatable by the network being up.
    // -----------------------------------------------------------------------

    /**
     * With a REACHABLE promisor remote, merely reading the object set repairs
     * it: the guard's own history pass lazily fetches every missing blob. So a
     * scan can find the credential (good) while having measured the object set
     * BEFORE the repair. The verdict must reflect the state the scan STARTED
     * from — a gate whose coverage depends on whether the network was up
     * during the run is not a gate.
     */
    public function test_a_reachable_promisor_remote_does_not_hide_the_truncation(): void
    {
        $partial = $this->cleanRepo(4)->partialClone();

        $r = $partial->scan();

        self::assertSame(3, $r['code'], "the verdict is the state the scan started from:\n".$r['all']);
        self::assertStringContainsString('PARTIAL', $r['all']);
        self::assertStringNotContainsString('credential-guard: clean', $r['all']);
    }

    public function test_the_measurement_survives_a_promisor_remote_that_answers(): void
    {
        // Same as above, but asserted on the number: the scan may go on to
        // fetch the blobs, yet the count printed is the one measured first.
        $partial = $this->cleanRepo(4)->partialClone();
        $absentBefore = $partial->missingObjects();

        $r = $partial->scan();

        self::assertGreaterThan(0, $absentBefore);
        self::assertStringContainsString("{$absentBefore} of ", $r['all']);
        self::assertStringContainsString('reachable object(s) are ABSENT', $r['all']);
    }

    // -----------------------------------------------------------------------
    // Other filters, and the shapes that are NOT truncations.
    // -----------------------------------------------------------------------

    public function test_a_tree_filtered_clone_is_detected_too(): void
    {
        // tree:0 drops the TREES, so rev-list cannot even enumerate the blobs
        // beneath them. A stricter truncation, and the same verdict.
        $partial = $this->cleanRepo(4)->partialClone('tree:0');
        $partial->breakPromisorRemote();

        $r = $partial->scan();

        self::assertSame(3, $r['code'], $r['all']);
        self::assertStringContainsString('PARTIAL', $r['all']);
        self::assertStringContainsString('filter tree:0', $r['all']);
    }

    public function test_a_sparse_checkout_is_not_a_truncation(): void
    {
        // --sparse limits the WORKING TREE, not the object store: every object
        // is still present. Treating it as a truncation would be the over-trigger
        // this suite exists to prevent.
        $repo = $this->cleanRepo(4);
        $repo->git(['config', 'core.sparseCheckout', 'true']);
        $repo->write('.git/info/sparse-checkout', "/note2.md\n");
        $repo->git(['read-tree', '-mu', 'HEAD']);

        $r = $repo->scan();

        self::assertSame(0, $r['code'], "a sparse working tree is not a truncated object set:\n".$r['all']);
        self::assertStringNotContainsString('PARTIAL', $r['all']);
    }

    public function test_history_off_makes_no_history_claim_to_contradict(): void
    {
        // Nothing is reached, so nothing is claimed — the same reasoning the
        // shallow path already uses, applied to the partial axis.
        $partial = $this->cleanRepo(4)->partialClone();
        $partial->breakPromisorRemote();

        $r = $partial->scan(['--history=0']);

        self::assertSame(0, $r['code'], $r['all']);
        self::assertStringContainsString('history depth none', $r['out']);
        self::assertStringNotContainsString('TRUNCATED', $r['all']);
    }
}
