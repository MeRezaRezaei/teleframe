<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Standalone;

use PHPUnit\Framework\TestCase;

/**
 * `bin/check-claim-drift.php` answers ONE question per existence claim: does
 * the tree have this thing? This file pins that the answer does not depend on
 * whether the sentence happened to type a trailing slash.
 *
 * THE DEFECT THIS PINS. The resolver read:
 *
 *     if (isset($idx->files[$name]) || is_file($idx->root().'/'.$name)) { … }
 *
 * `$idx->files` holds FILES and `is_file()` is false for a directory, so a
 * claim naming a directory fell through to `contradicted` — while the SAME
 * directory written with a trailing slash was already `skip`ped three lines
 * earlier as "names a DIRECTORY, not a file". Two spellings of one path, two
 * opposite verdicts, and the sentence had no say in which.
 *
 * MEASURED, not asserted. On this tree the gate reported exactly one review
 * candidate:
 *
 *     .brain/verbatim-goals.md:1719
 *       `tests/Concerns` is NOT in the tree — this sentence says it exists
 *
 * The sentence was `tests/Concerns` existed on disk, inside a section already
 * headed "(fixed `bf5db48c`)". Meanwhile `git ls-tree HEAD tests/` lists the
 * tree and `git ls-files tests/Concerns/` returns six files. The ledger was
 * right and the gate was wrong — which is the one failure mode this gate's own
 * header calls worse than the bug it hunts: "a false positive trains the
 * operator to ignore the gate".
 *
 * WHY THE FIX IS SAFE. An existence claim is satisfied by a directory exactly
 * as by a file; the question asked is never "is it a FILE". This therefore
 * removes no coverage — the trailing-slash spelling was already skipped, not
 * contradicted — it only stops the verdict depending on punctuation.
 *
 * WHAT IS OBSERVABLE. A claim the gate answers `consistent` is SILENT: it
 * prints nothing under the findings heading. Only a `contradicted` claim is
 * reported, with a `checked : <subject> => <verdict>` line. So the assertion
 * shape here is "this subject does NOT appear among the contradicted", and the
 * test that carries the whole file is the one asserting an absent subject DOES.
 */
final class ClaimDriftPathClaimTest extends TestCase
{
    private const REPO_ROOT = __DIR__.'/../..';

    /** @var list<string> */
    private array $scratch = [];

    protected function tearDown(): void
    {
        foreach ($this->scratch as $dir) {
            $this->removeTree($dir);
        }
        $this->scratch = [];

        parent::tearDown();
    }

    // -----------------------------------------------------------------------
    // Present paths must NOT be reported contradicted
    // -----------------------------------------------------------------------

    /**
     * A directory that exists, named WITHOUT a trailing slash.
     *
     * This is the exact shape that produced the false positive.
     */
    public function test_an_existing_directory_named_bare_is_not_reported_contradicted(): void
    {
        $report = $this->scan([
            'The directory `tests/Concerns` existed on disk in this repository.',
        ]);

        self::assertNotContains(
            'path:tests/Concerns',
            $report['contradicted'],
            'an existing DIRECTORY satisfies an existence claim; calling it absent is a false positive'
        );
        self::assertSame([], $report['contradicted'], 'a wholly true document yields no finding at all');
    }

    /**
     * The SAME directory, written with a trailing slash.
     *
     * Pins that the two spellings now agree — which is the whole defect. A
     * future edit that restores the slash-dependent behaviour fails here even
     * if the bare-spelling test still passes for the wrong reason.
     */
    public function test_a_trailing_slash_agrees_with_the_bare_spelling(): void
    {
        $report = $this->scan([
            'The directory `tests/Concerns/` existed on disk in this repository.',
        ]);

        self::assertNotContains(
            'path:tests/Concerns',
            $report['contradicted'],
            'the slashed spelling must not be contradicted either'
        );
        self::assertSame([], $report['contradicted'], 'a wholly true document yields no finding at all');
    }

    /** An ordinary file still resolves — the fix did not narrow the resolver. */
    public function test_an_existing_file_is_still_consistent(): void
    {
        $report = $this->scan([
            'The file `bin/check-claim-drift.php` existed on disk in this repository.',
        ]);

        self::assertNotContains(
            'path:bin/check-claim-drift.php',
            $report['contradicted'],
            'a present file must never be contradicted'
        );
        self::assertSame([], $report['contradicted'], 'a wholly true document yields no finding at all');
    }

    // -----------------------------------------------------------------------
    // THE NEGATIVE CONTROL — the gate must still be able to fail
    // -----------------------------------------------------------------------

    public function test_a_genuinely_absent_path_is_still_contradicted(): void
    {
        $report = $this->scan([
            'The directory `tests/NoSuchDirectoryAtAll` existed on disk in this repository.',
        ]);

        self::assertContains(
            'path:tests/NoSuchDirectoryAtAll',
            $report['contradicted'],
            'the gate must still report an absent path — this is what makes every other assertion meaningful'
        );
    }

    /**
     * And it must reach the VERDICT, not merely be counted.
     *
     * A claim silently suppressed is indistinguishable from a claim the gate
     * cannot see, which is why the audit block is checked for its absence.
     */
    public function test_a_present_path_is_silently_consistent_not_merely_unreported(): void
    {
        [$code, $output] = $this->gate([
            'The directory `tests/Concerns` existed on disk in this repository.',
        ]);

        self::assertStringNotContainsString(
            'CLAIMS THAT SOMETHING EXISTS WHEN THE TREE DOES NOT HAVE IT',
            $output,
            'a document whose only claim is true must not open a findings section'
        );
        self::assertStringContainsString(
            'VERDICT: CLEAN',
            $output,
            'the run must report itself clean, not merely print nothing'
        );
        self::assertSame(0, $code, 'no finding means a clean exit');
    }

    /**
     * The REPORT-not-GATE exit policy, which is DELIBERATE and documented in
     * the tool's own header. Pinned because it is exactly the kind of contract
     * that gets "fixed" by someone who assumes every gate must go red: a real
     * finding exits 0 by default, and `--strict` is what opts into gating.
     */
    public function test_the_exit_policy_reports_by_default_and_gates_only_under_strict(): void
    {
        $sentence = 'The directory `tests/NoSuchDirectoryAtAll` existed on disk in this repository.';

        [$reportCode, $reportOutput] = $this->gate([$sentence]);
        self::assertSame(0, $reportCode, 'the default mode is a REPORT: a candidate does not fail the run');
        self::assertStringContainsString('VERDICT: CANDIDATES', $reportOutput, 'but it is named in the verdict');

        [$strictCode] = $this->gate([$sentence], strict: true);
        self::assertNotSame(0, $strictCode, '--strict is what opts into gating, so a real finding must exit non-zero');
    }

    // -----------------------------------------------------------------------
    // Running the gate
    // -----------------------------------------------------------------------

    /**
     * Run the gate over a synthetic document and bucket each verdict.
     *
     * Bucketing on the verdict word is what makes this a test of the DECISION
     * rather than of prose.
     *
     * @param  list<string>  $sentences
     * @return array{contradicted: list<string>, suppressed: int}
     */
    private function scan(array $sentences): array
    {
        [, $output] = $this->gate($sentences);

        preg_match_all('/^\s*checked\s+:\s+(.+?)\s+=>\s+(\w+)/m', $output, $checked);

        $contradicted = [];
        foreach ($checked[2] as $i => $verdict) {
            if ($verdict === 'contradicted') {
                $contradicted[] = trim($checked[1][$i], '` ');
            }
        }

        // Suppressions are a counted block; counting them lets a test tell
        // "suppressed" from "answered", which are not the same thing.
        $suppressed = preg_match('/SILENCE IS AUDITABLE.*?\n\s*(\d+)\s+claim is/s', $output, $m) === 1
            ? (int) $m[1]
            : 0;

        return ['contradicted' => $contradicted, 'suppressed' => $suppressed];
    }

    /**
     * @param  list<string>  $sentences
     * @return array{0: int, 1: string}
     */
    private function gate(array $sentences, bool $strict = false): array
    {
        $dir = sys_get_temp_dir().'/teleframe-claim-drift-'.bin2hex(random_bytes(8));
        mkdir($dir, 0o777, true);
        $this->scratch[] = $dir;

        $fixture = $dir.'/fixture.md';
        file_put_contents($fixture, implode("\n\n", $sentences)."\n");

        $command = escapeshellarg(PHP_BINARY).' '.escapeshellarg(self::REPO_ROOT.'/bin/check-claim-drift.php')
            .' --path='.escapeshellarg($fixture)
            .($strict ? ' --strict' : '');

        $output = [];
        $code = 0;
        exec($command.' 2>&1', $output, $code);

        return [$code, implode("\n", $output)];
    }

    private function removeTree(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $entries = scandir($dir);
        if ($entries === false) {
            return;
        }
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $full = $dir.'/'.$entry;
            is_dir($full) ? $this->removeTree($full) : @unlink($full);
        }
        @rmdir($dir);
    }
}
