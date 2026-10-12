<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Credentials;

use PHPUnit\Framework\TestCase;

/**
 * The guard must be able to tell a TRUNCATED repository from a complete one.
 *
 * Pipeline #206 (2026-09-27): the `credential-guard` job was GREEN on a
 * `--depth 20` clone while a receipted Telegram session sat 84 commits back in
 * the real history. Nothing in the guard's logic was wrong — the OBJECT SET it
 * was handed was. It then printed "history scanned (every commit reachable
 * from all)" and "history depth all reachable from all": a false coverage
 * claim, produced by the guard itself, over a repository that did not contain
 * the history being claimed. That is the same class of silent omission the
 * guard exists to prevent, so it is the one omission the guard is now
 * structurally unable to commit:
 *
 *   complete → the unbounded claim is true, and is stated with the commit count
 *              that makes it checkable;
 *   shallow  → detected, counted, boundary named, exit 3 (INCOMPLETE), and the
 *              reassuring words are NOT printed;
 *   unknown  → git could not be asked and no marker could be located, so no
 *              unbounded claim is made, and exit 3 as well.
 *
 * Every direction is asserted. A detection that only ever fires on the
 * truncated repository is a broken gate with extra noise, and one that fires
 * on a complete repository is a gate nobody can keep switched on — so the
 * complete-clone direction is asserted just as hard as the shallow one.
 *
 * Run just this file:  vendor/bin/phpunit tests/Credentials/TruncatedObjectSetTest.php
 */
final class TruncatedObjectSetTest extends TestCase
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

    /** A clean repository with $n commits, nothing sensitive in it. */
    private function cleanRepo(int $n = 5): ScanRunner
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
     * A `git` shim on PATH that hides one subcommand, so the guard has to reach
     * for its other evidence. A pre-2.5 git has no `--git-path`; a pre-2.15 one
     * has no `--is-shallow-repository`. Both are real environments and both
     * used to be a way for the guard to be unable to answer its own question.
     *
     * @return array{0: string, 1: array<string, string>} the temp dir and the
     *                                                    environment to scan with
     */
    private function gitShimHiding(ScanRunner $repo, string $subcommand): array
    {
        $dir = sys_get_temp_dir().'/credscan-shim-'.bin2hex(random_bytes(6));
        mkdir($dir, 0o777, true);
        $this->made[] = new ScanRunner(self::script(), $dir);
        $real = trim((string) shell_exec('command -v git')) ?: '/usr/bin/git';
        file_put_contents(
            $dir.'/git',
            "#!/bin/sh\nfor a in \"\$@\"; do\n  if [ \"\$a\" = '{$subcommand}' ]; then exit 1; fi\ndone\nexec {$real} \"\$@\"\n",
        );
        chmod($dir.'/git', 0o755);

        return [$dir, [
            'PATH' => $dir.':'.(getenv('PATH') ?: '/usr/bin:/bin'),
            'HOME' => $repo->sandbox(),
        ]];
    }

    // -----------------------------------------------------------------------
    // The truncated direction.
    // -----------------------------------------------------------------------

    public function test_a_shallow_clone_exits_three_instead_of_zero(): void
    {
        $shallow = $this->cleanRepo(5)->shallowClone(2);

        $r = $shallow->scan();

        self::assertSame(3, $r['code'], "a truncated object set is not a pass:\n".$r['all']);
        self::assertStringContainsString('INCOMPLETE', $r['all']);
        self::assertStringContainsString('SHALLOW', $r['all']);
    }

    public function test_a_shallow_clone_never_prints_the_unbounded_coverage_claim(): void
    {
        $shallow = $this->cleanRepo(5)->shallowClone(2);

        $r = $shallow->scan();

        // The two sentences that were false in #206. Their absence is the fix.
        self::assertStringNotContainsString('every commit reachable', $r['all']);
        self::assertStringNotContainsString('history depth all reachable', $r['all']);
        // …and no "clean", which is a claim about the whole surface.
        self::assertStringNotContainsString('credential-guard: clean', $r['all']);
        self::assertStringNotContainsString('PASS with', $r['all']);
        self::assertStringContainsString('history depth TRUNCATED', $r['all']);
    }

    public function test_the_truncated_run_states_how_many_commits_it_actually_scanned(): void
    {
        $shallow = $this->cleanRepo(5)->shallowClone(2);

        $r = $shallow->scan();

        // A number a reader can check against `git rev-list --all --count`,
        // not a reassurance. The true total of a truncated clone is not knowable
        // from inside it, and the run says UNKNOWN rather than inventing one.
        $present = (int) trim($shallow->git(['rev-list', '--all', '--count']));
        self::assertSame(2, $present, 'the sandbox clone really is depth-2');
        self::assertStringContainsString("scanned ({$present} of {$present} commits present", $r['all']);
        self::assertStringContainsString('true total is UNKNOWN', $r['all']);
    }

    public function test_the_truncated_run_names_the_boundary_it_stops_at(): void
    {
        $shallow = $this->cleanRepo(5)->shallowClone(2);

        $r = $shallow->scan();
        $boundary = substr(trim($shallow->read('.git/shallow')), 0, 12);

        self::assertNotSame('', $boundary, 'git recorded a cut point');
        self::assertStringContainsString($boundary, $r['all'], 'the cut point is named, so the claim is checkable');
        self::assertStringContainsString('NOT covered', $r['all'], 'and what is lost past it is named');
    }

    /**
     * The incident, in miniature: the same secret, in the same repository, seen
     * through a depth-2 window and through the whole object set.
     */
    public function test_a_secret_older_than_the_shallow_boundary_is_invisible_in_the_window_and_found_outside_it(): void
    {
        $repo = $this->keep(ScanRunner::repo(self::script()));
        $secret = ScanRunner::fakeRun();
        $repo->write('leak.txt', $secret);
        $repo->commit('the mistake');
        $repo->write('leak.txt', "[REDACTED]\n");
        $repo->commit('redact it');
        for ($i = 1; $i <= 3; $i++) {
            $repo->write("note{$i}.md", "note {$i}\n");
            $repo->commit("later {$i}");
        }

        // The window: the blob is not in this object set at all.
        $windowed = $repo->shallowClone(2);
        $inside = $windowed->scan();
        self::assertStringNotContainsString('history@leak.txt', $inside['all'], 'the blob is outside the window');
        // …and the guard says so instead of reporting a pass.
        self::assertSame(3, $inside['code'], 'not reaching it is not the same as it being absent');
        self::assertStringContainsString('NOT covered', $inside['all']);

        // The whole object set: the same secret is found, and its value is
        // still withheld.
        $full = $repo->scan();
        self::assertSame(1, $full['code'], 'the complete scan fails where the truncated one could not');
        self::assertStringContainsString('history@leak.txt', $full['all']);
        self::assertStringNotContainsString($secret, $full['all']);
        self::assertStringNotContainsString(substr($secret, 0, 64), $full['all']);
    }

    public function test_an_incomplete_run_still_reports_a_finding_as_a_finding(): void
    {
        // A caller must never be handed "incomplete" in place of "there is a
        // problem". Exit 3 is a statement about coverage, not about the tree.
        $repo = $this->keep(ScanRunner::repo(self::script()));
        $repo->write('README.md', "# scratch\n");
        $repo->commit('init');
        $repo->write('note1.md', "note\n");
        $repo->commit('note');
        $repo->write('leak.txt', ScanRunner::fakeRun());
        $repo->commit('the mistake');
        $repo->write('note2.md', "note\n");
        $repo->commit('later');

        // A depth-3 window holds the leaking commit, so the finding is inside
        // a run that is also incomplete.
        $r = $repo->shallowClone(3)->scan();

        self::assertSame(1, $r['code'], "a finding inside a partial scan is still a finding:\n".$r['all']);
        self::assertStringContainsString('leak.txt', $r['all']);
        self::assertStringContainsString('INCOMPLETE', $r['all'], 'and the truncation is still disclosed');
    }

    // -----------------------------------------------------------------------
    // The complete direction — the check must not over-trigger. A guard that
    // called every repository truncated would be switched off within a day.
    // -----------------------------------------------------------------------

    public function test_a_complete_repository_still_claims_its_full_coverage(): void
    {
        $repo = $this->cleanRepo(5);

        $r = $repo->scan();

        self::assertSame(0, $r['code'], "a complete object set is a normal pass:\n".$r['all']);
        self::assertStringContainsString('credential-guard: clean', $r['out']);
        self::assertStringContainsString('every commit reachable from all', $r['out']);
        self::assertStringContainsString('history depth all reachable from all', $r['out']);
        self::assertStringNotContainsString('TRUNCATED', $r['all']);
        self::assertStringNotContainsString('UNKNOWN', $r['all']);
        self::assertStringNotContainsString('INCOMPLETE', $r['all']);
    }

    public function test_the_complete_claim_carries_the_count_that_makes_it_checkable(): void
    {
        $repo = $this->cleanRepo(5);

        $r = $repo->scan();
        $total = (int) trim($repo->git(['rev-list', '--all', '--count']));

        self::assertStringContainsString("{$total} of {$total} commits present", $r['all']);
        self::assertStringContainsString('object set COMPLETE', $r['all']);
    }

    public function test_a_full_clone_of_a_real_repository_is_not_called_shallow(): void
    {
        // The repository the gate actually runs on, with its real history.
        $r = (new ScanRunner(self::script(), dirname(__DIR__, 2)))->scan();

        self::assertSame(0, $r['code'], $r['all']);
        self::assertStringContainsString('object set COMPLETE — not shallow', $r['out']);
    }

    // -----------------------------------------------------------------------
    // The unknown direction: silence is not a "yes".
    // -----------------------------------------------------------------------

    public function test_with_no_git_at_all_the_run_refuses_to_claim_any_coverage(): void
    {
        $repo = $this->cleanRepo(3);
        $empty = sys_get_temp_dir().'/credscan-nogit-'.bin2hex(random_bytes(6));
        mkdir($empty, 0o777, true);
        $this->made[] = new ScanRunner(self::script(), $empty);

        $r = $repo->scan([], ['PATH' => $empty, 'HOME' => $repo->sandbox()]);

        self::assertSame(3, $r['code'], "no git means no answer, not a pass:\n".$r['all']);
        self::assertStringContainsString('UNKNOWN', $r['all']);
        self::assertStringNotContainsString('every commit reachable', $r['all']);
        self::assertStringNotContainsString('credential-guard: clean', $r['all']);
    }

    public function test_a_shallow_marker_alone_convicts_when_git_is_too_old_to_ask(): void
    {
        $shallow = $this->cleanRepo(5)->shallowClone(2);
        [, $env] = $this->gitShimHiding($shallow, '--is-shallow-repository');

        $r = $shallow->scan([], $env);

        self::assertSame(3, $r['code'], "a pre-2.15 git must not buy a pass:\n".$r['all']);
        self::assertStringContainsString('SHALLOW', $r['all'], 'the marker on disk is still evidence');
        self::assertStringNotContainsString('every commit reachable', $r['all']);
    }

    public function test_a_missing_marker_acquits_when_git_is_too_old_to_ask(): void
    {
        $repo = $this->cleanRepo(3);
        [, $env] = $this->gitShimHiding($repo, '--is-shallow-repository');

        $r = $repo->scan([], $env);

        self::assertSame(0, $r['code'], "an old git on a full clone is still a full clone:\n".$r['all']);
        self::assertStringContainsString('object set COMPLETE', $r['all']);
    }

    public function test_an_empty_shallow_marker_is_not_proof_of_completeness(): void
    {
        $repo = $this->cleanRepo(3);
        [, $env] = $this->gitShimHiding($repo, '--is-shallow-repository');
        file_put_contents($repo->sandbox().'/.git/shallow', '');

        $r = $repo->scan([], $env);

        self::assertSame(3, $r['code'], "a degenerate marker proves nothing:\n".$r['all']);
        self::assertStringContainsString('UNKNOWN', $r['all']);
    }

    // -----------------------------------------------------------------------
    // The coverage lines themselves.
    // -----------------------------------------------------------------------

    public function test_the_depth_line_and_the_summary_line_agree_in_a_truncated_run(): void
    {
        $r = $this->cleanRepo(5)->shallowClone(3)->scan();

        // One run, two statements of coverage, and they must not contradict:
        // the depth line is what a reader skims, so it has to carry the
        // truncation itself rather than deferring to the summary line.
        self::assertStringContainsString('history depth TRUNCATED', $r['all']);
        self::assertStringContainsString('TRUNCATED', $r['all']);
        $depth = array_values(array_filter(explode("\n", $r['all']), static fn (string $l): bool => str_contains($l, 'coverage:')));
        self::assertCount(1, $depth);
        self::assertStringContainsString('true total UNKNOWN', $depth[0]);
    }

    public function test_a_bounded_history_scan_inside_a_shallow_clone_still_says_truncated(): void
    {
        $shallow = $this->cleanRepo(5)->shallowClone(2);

        $r = $shallow->scan(['--history=5']);

        // --history=5 is a WINDOW the operator chose; the shallow boundary is
        // a DIFFERENT and tighter one. Reporting only the chosen window would
        // be a second false coverage claim on top of the first.
        self::assertSame(3, $r['code']);
        self::assertStringContainsString('TRUNCATED', $r['all']);
        self::assertStringContainsString('SHALLOW', $r['all']);
    }

    public function test_turning_history_off_in_a_shallow_clone_makes_no_history_claim_to_contradict(): void
    {
        $r = $this->cleanRepo(5)->shallowClone(2)->scan(['--history=0']);

        // Nothing was reached, so nothing is claimed — and there is nothing
        // incomplete about a scan that never claimed to look.
        self::assertSame(0, $r['code'], $r['all']);
        self::assertStringContainsString('history NOT SCANNED', $r['out']);
        self::assertStringContainsString('history depth none', $r['out']);
        self::assertStringNotContainsString('TRUNCATED', $r['all']);
    }
}
