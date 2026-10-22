<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Credentials;

use PHPUnit\Framework\TestCase;

/**
 * bin/scan-credentials.php — a LINKED worktree is a checkout, and its history
 * axis must not disappear because of it.
 *
 * Before 2026-10-22 the checkout test was exactly `is_dir($root.'/.git')`. A
 * linked worktree (`git worktree add`) carries `.git` as a one-line POINTER
 * FILE — `gitdir: <path>` — so `is_dir` answered false and the guard silently
 * switched into "recursive walk (no git checkout)": the tracked+untracked
 * sweep still ran, but the history axis printed "history NOT SCANNED (no git
 * checkout at --root)" and the run exited 0. Measured on this repository the
 * same minutes in which its own main worktree scan reported history findings:
 * one repo, two scanners, the quieter one green, the difference sealed under
 * exit 0 — the exact shape of pipeline #206's "all reachable" over the
 * shallow clone, arrived at by directory shape instead of depth.
 *
 * Three directions are pinned, because a fix asserted on one side only is a
 * gate that fires where it must not:
 *
 *   1. findings in history ARE found when the scan runs in a worktree, with
 *      the full coverage claim that makes the run checkable;
 *   2. a CLEAN worktree claims its full coverage and never prints the
 *      no-checkout omission (the fix does not merely stop failing);
 *   3. a `.git` that is neither a directory nor a well-formed pointer stays
 *      out of git scope AND SAYS SO — a garbage pointer must not read like
 *      evidence of anything.
 *
 * Run just this file:  vendor/bin/phpunit tests/Credentials/LinkedWorktreeHistoryScanTest.php
 */
final class LinkedWorktreeHistoryScanTest extends TestCase
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

    /** The worktree must be a sibling of the main checkout, never inside it. */
    private function linkedWorktreeOf(ScanRunner $repo): string
    {
        $wt = dirname($repo->sandbox()).'/'.basename($repo->sandbox()).'-linked-wt';
        $repo->git(['worktree', 'add', '--quiet', $wt]);

        // Pin the fixTURE that the whole test stands on: the worktree root
        // carries `.git` as a pointer file, not a directory — the exact shape
        // the old `is_dir` test could not see.
        self::assertFileExists($wt.'/.git');
        self::assertTrue(is_file($wt.'/.git'), 'the worktree root must carry .git as a FILE (pointer), or the fixture is not a worktree');
        self::assertStringStartsWith('gitdir: ', (string) file_get_contents($wt.'/.git'));

        return $wt;
    }

    public function test_a_finding_in_history_is_found_when_scanned_from_a_linked_worktree(): void
    {
        $repo = $this->keep(ScanRunner::repo(self::script()));
        $secret = ScanRunner::fakeRun();
        $repo->write('leak.txt', $secret);
        $repo->commit('the mistake');
        $repo->write('leak.txt', "[REDACTED]\n");
        $repo->commit('redact it');
        $repo->write('note.md', "note\n");
        $repo->commit('later');

        $wt = $this->linkedWorktreeOf($repo);

        $r = $this->keep(new ScanRunner(self::script(), $wt))->scan();

        self::assertSame(1, $r['code'], "the history finding must survive the worktree shape:\n".$r['all']);
        self::assertStringContainsString('history@leak.txt', $r['all']);
        self::assertStringContainsString('commits present', $r['all'], 'the scan still states its coverage with the checkable count');
        self::assertStringNotContainsString('no git checkout at --root', $r['all'], 'the walk-mode omission line must not print over a real checkout');
        self::assertStringNotContainsString($secret, $r['all'], 'the guard never prints what it found');
        self::assertStringNotContainsString(substr($secret, 0, 64), $r['all']);
    }

    public function test_a_clean_linked_worktree_scans_history_and_claims_full_coverage(): void
    {
        $repo = $this->keep(ScanRunner::repo(self::script()));
        $repo->write('README.md', "# scratch\n\nnothing sensitive here\n");
        $repo->commit('c1');
        for ($i = 2; $i <= 3; $i++) {
            $repo->write("note{$i}.md", "note {$i}\n");
            $repo->commit("c{$i}");
        }

        $wt = $this->linkedWorktreeOf($repo);

        $r = $this->keep(new ScanRunner(self::script(), $wt))->scan();

        self::assertSame(0, $r['code'], "a clean worktree is a normal pass:\n".$r['all']);
        self::assertStringContainsString('every commit reachable from all', $r['all']);
        self::assertStringNotContainsString('history NOT SCANNED', $r['all'], 'no branch of a real checkout prints the walk-mode omission');
        self::assertStringNotContainsString('TRUNCATED', $r['all']);
        self::assertStringNotContainsString('UNKNOWN', $r['all']);
    }

    public function test_a_garbage_git_pointer_is_stated_as_out_of_scope_not_treated_as_a_repo(): void
    {
        $dir = sys_get_temp_dir().'/credscan-badgit-'.bin2hex(random_bytes(6));
        mkdir($dir, 0o777, true);
        file_put_contents($dir.'/.git', 'deliberately not a gitdir pointer');
        file_put_contents($dir.'/README.md', "scratch\n");
        $this->made[] = new ScanRunner(self::script(), $dir);

        $r = $this->made[0]->scan();

        self::assertSame(0, $r['code'], $r['all']);
        self::assertStringContainsString('history NOT SCANNED (no git checkout at --root)', $r['all'], 'the omission is stated, never silent');
    }
}
