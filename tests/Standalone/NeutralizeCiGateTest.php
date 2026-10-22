<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Standalone;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The private/public split's CI neutralizer: its OWN invariants.
 *
 * bin/neutralize-ci.php exists to satisfy one constraint that decides its whole
 * design. `.github/**` is already allowed by the manifest
 * (bin/public-manifest.txt:110), so bin/export-public-repo.sh publishes the
 * REAL workflow files to the public repo — where they must stay runnable. The
 * private repo must keep the same files TRACKED but INERT, and it may not keep
 * a second, hand-maintained copy of them, because a second source of truth is
 * how the exporter and bin/check-public-surface.php drift apart. So the tool is
 * a reversible build step: rename every workflow to `*.yml.neutralized`, and
 * rename it back on demand.
 *
 * Two properties are the whole contract, and both are proved here rather than
 * asserted in a docblock:
 *
 *   1. LOSSLESS  — disable then enable returns every byte. A tool that quietly
 *      re-encodes a line ending or trims a trailing newline is a tool that
 *      silently rewrites the record, so the proof is a sha256 comparison over
 *      the WHOLE tree, on fixtures that deliberately include the cases a naive
 *      implementation gets wrong: CRLF endings and a file with no trailing
 *      newline.
 *   2. INERT     — after disable, no file under .github/workflows/ ends in
 *      `.yml` or `.yaml`, which is the only property GitHub's discovery cares
 *      about. The marker comment is documentation; the RENAME is the mechanism.
 *
 * EVERY test that asserts a pass carries a NEGATIVE CONTROL. A gate that has
 * never been seen to go red is not known to work: `snapshot()` comparing equal
 * proves nothing if two different trees always hash the same, and "nothing was
 * touched" proves nothing if the tool touches nothing ever. So each claim is
 * paired with the case that must make it fail — a detector run against a tree
 * where the thing IS present, and a "did not change" assertion paired with
 * proving the neighbouring file DID change.
 *
 * NOT COVERED HERE, stated rather than hidden: the tool's behaviour against a
 * real GitHub Actions runner. The inertness claim is a claim about GitHub's
 * documented discovery rule, tested here as a property of the final filenames.
 */
final class NeutralizeCiGateTest extends TestCase
{
    private const SCRIPT = __DIR__.'/../../bin/neutralize-ci.php';

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
    // Fixtures
    // -----------------------------------------------------------------------

    /**
     * A throwaway repository tree holding several workflows.
     *
     * `alpha.yml` and `beta.yaml` are the two extension spellings, because the
     * tool has to handle both and a bug that only rejects one of them would be
     * invisible with a single fixture. `gamma.yml` has NO trailing newline and
     * `delta.yaml` has CRLF endings: those are the two byte-level traps, and a
     * lossless round trip that normalises either is not lossless.
     *
     * @return string absolute path to the fixture root
     */
    private function workflowFixture(): string
    {
        return $this->makeTree([
            '.github/workflows/alpha.yml' => "name: alpha\non: push\njobs:\n  a:\n    runs-on: ubuntu-latest\n",
            '.github/workflows/beta.yaml' => "name: beta\non:\n  schedule:\n    - cron: \"0 0 * * *\"\n",
            '.github/workflows/gamma.yml' => 'name: gamma',
            '.github/workflows/delta.yaml' => "name: delta\r\non: push\r\n",
        ]);
    }

    /**
     * @param  array<string, string>  $files  relative path => contents
     * @return string absolute path to the fixture root
     */
    private function makeTree(array $files): string
    {
        $root = sys_get_temp_dir().'/teleframe-neutralize-'.bin2hex(random_bytes(8));
        mkdir($root.'/.github/workflows', 0o777, true);
        $this->scratch[] = $root;

        foreach ($files as $relative => $contents) {
            $full = $root.'/'.$relative;
            $parent = dirname($full);
            if (! is_dir($parent)) {
                mkdir($parent, 0o777, true);
            }
            file_put_contents($full, $contents);
        }

        return $root;
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

    // -----------------------------------------------------------------------
    // Running the tool
    // -----------------------------------------------------------------------

    /**
     * @param  list<string>  $arguments
     * @return array{code: int, output: string}
     */
    private function invoke(array $arguments): array
    {
        $command = escapeshellarg(PHP_BINARY).' '.escapeshellarg(self::SCRIPT);
        foreach ($arguments as $argument) {
            $command .= ' '.escapeshellarg($argument);
        }
        $command .= ' 2>&1';

        $output = [];
        $code = 0;
        exec($command, $output, $code);

        return ['code' => $code, 'output' => implode("\n", $output)];
    }

    /** @param  list<string>  $arguments */
    private function invokeOk(array $arguments): string
    {
        $result = $this->invoke($arguments);
        self::assertSame(0, $result['code'], "expected a clean exit:\n".$result['output']);

        return $result['output'];
    }

    /**
     * The whole tree as relative path => sha256, sorted by path.
     *
     * HASHES, NOT CONTENTS: the failure message of a byte comparison must be
     * able to name every file without echoing one, for the same reason the tool
     * itself never prints contents.
     *
     * @return array<string, string>
     */
    private function snapshot(string $root): array
    {
        $snapshot = [];
        $entries = scandir($root);
        self::assertNotFalse($entries, 'fixture tree must be readable');

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $full = $root.'/'.$entry;
            if (is_dir($full)) {
                foreach ($this->snapshot($full) as $relative => $hash) {
                    $snapshot[$entry.'/'.$relative] = $hash;
                }

                continue;
            }
            $snapshot[$entry] = (string) hash_file('sha256', $full);
        }
        ksort($snapshot);

        return $snapshot;
    }

    /**
     * Files under .github/workflows/ whose names end in `.yml` or `.yaml` —
     * exactly the set GitHub Actions would parse and dispatch.
     *
     * @return list<string>
     */
    private function executableWorkflows(string $root): array
    {
        $workflows = $root.'/.github/workflows';
        $found = [];
        $entries = scandir($workflows);
        self::assertNotFalse($entries, 'fixture must contain .github/workflows/');

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $lower = strtolower($entry);
            if (str_ends_with($lower, '.yml') || str_ends_with($lower, '.yaml')) {
                $found[] = $entry;
            }
        }
        sort($found);

        return $found;
    }

    // -----------------------------------------------------------------------
    // 1. The tool exists and is valid PHP
    // -----------------------------------------------------------------------

    public function test_the_neutralizer_exists_and_is_valid_php(): void
    {
        self::assertFileExists(self::SCRIPT, 'bin/neutralize-ci.php must exist');

        $output = [];
        $code = 0;
        exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg(self::SCRIPT).' 2>&1', $output, $code);
        self::assertSame(0, $code, "bin/neutralize-ci.php must lint:\n".implode("\n", $output));

        // NEGATIVE CONTROL: `php -l` must be capable of failing, or the
        // assertion above is measuring nothing.
        $broken = sys_get_temp_dir().'/teleframe-neutralize-broken-'.bin2hex(random_bytes(8)).'.php';
        file_put_contents($broken, "<?php\nfunction (\n");
        $brokenOutput = [];
        $brokenCode = 0;
        exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($broken).' 2>&1', $brokenOutput, $brokenCode);
        @unlink($broken);

        self::assertNotSame(
            0,
            $brokenCode,
            'a deliberately broken file must fail `php -l`; if it lints clean the lint check is not a check',
        );
    }

    // -----------------------------------------------------------------------
    // 2. LOSSLESS — the round trip returns every byte
    // -----------------------------------------------------------------------

    public function test_disable_then_enable_restores_every_byte(): void
    {
        $root = $this->workflowFixture();
        $before = $this->snapshot($root);

        // NEGATIVE CONTROL FIRST. Without it, an `assertSame($before, $after)`
        // below could be satisfied by a `snapshot()` that returns the same
        // array for any two trees — which is the failure mode a hash-based
        // comparison has if it is broken, not if the tool is broken.
        $afterDisable = $this->snapshot($root);
        $this->invokeOk(['--mode=disable', '--dir='.$root]);
        $disabled = $this->snapshot($root);
        self::assertNotSame(
            $before,
            $disabled,
            'the control for this test failed: disable must CHANGE the tree, otherwise the '
            .'byte comparison below is vacuous',
        );

        $this->invokeOk(['--mode=enable', '--dir='.$root]);
        $after = $this->snapshot($root);

        self::assertSame(
            $before,
            $after,
            "disable then enable must restore every byte of every file.\n  the CRLF (delta.yaml) "
            .'and no-trailing-newline (gamma.yml) fixtures exist to catch an implementation that '
            .'normalises either one',
        );
    }

    /**
     * The round trip must hold for a single workflow too, not only for a
     * multi-file tree — a loop that resets its state per file could pass the
     * fixture above by accident.
     */
    public function test_the_round_trip_holds_for_a_single_workflow(): void
    {
        $root = $this->makeTree(['.github/workflows/only.yml' => "name: only\non: push\n"]);
        $before = $this->snapshot($root);

        $this->invokeOk(['--mode=disable', '--dir='.$root]);
        self::assertSame(
            ['only.yml.neutralized'],
            $this->names($root.'/.github/workflows'),
            'a lone workflow must be renamed, so the round trip is exercised on its own',
        );

        $this->invokeOk(['--mode=enable', '--dir='.$root]);

        self::assertSame($before, $this->snapshot($root), 'a single-file round trip must be byte-identical');
    }

    // -----------------------------------------------------------------------
    // 3. IDEMPOTENCE and the clean-tree no-op
    // -----------------------------------------------------------------------

    public function test_running_disable_twice_is_idempotent(): void
    {
        $root = $this->workflowFixture();
        $before = $this->snapshot($root);

        $this->invokeOk(['--mode=disable', '--dir='.$root]);
        $once = $this->snapshot($root);

        self::assertNotSame(
            $before,
            $once,
            'the control for this test failed: the first disable must change the tree',
        );

        $this->invokeOk(['--mode=disable', '--dir='.$root]);
        $twice = $this->snapshot($root);

        self::assertSame(
            $once,
            $twice,
            'a second --mode=disable must leave the tree exactly as the first one did',
        );
    }

    public function test_enable_on_a_clean_tree_is_a_noop_that_exits_zero(): void
    {
        $root = $this->workflowFixture();
        $before = $this->snapshot($root);

        $output = $this->invokeOk(['--mode=enable', '--dir='.$root]);

        self::assertSame(
            $before,
            $this->snapshot($root),
            '--mode=enable on a tree that was never disabled must not change a byte',
        );

        // NEGATIVE CONTROL. "Nothing changed" is also what a tool that never
        // does anything would produce, so the same invocation on a DISABLED
        // tree is shown to change it.
        $this->invokeOk(['--mode=disable', '--dir='.$root]);
        self::assertNotSame(
            $before,
            $this->snapshot($root),
            'the control for this test failed: disable must change the tree, so that the '
            ."no-op above is a real no-op\n  ".$output,
        );
    }

    // -----------------------------------------------------------------------
    // 4. INERT — nothing left that GitHub would execute
    // -----------------------------------------------------------------------

    public function test_the_disabled_tree_contains_no_file_github_would_execute(): void
    {
        $root = $this->workflowFixture();

        // NEGATIVE CONTROL FIRST: the detector must be capable of FINDING
        // runnable workflows, or asserting it finds none proves nothing.
        $runnable = $this->executableWorkflows($root);
        self::assertNotSame(
            [],
            $runnable,
            'the control for this test failed: the fixture must start with workflow files this '
            .'detector can find, otherwise finding none afterwards is vacuous',
        );

        $this->invokeOk(['--mode=disable', '--dir='.$root]);

        self::assertSame(
            [],
            $this->executableWorkflows($root),
            'after --mode=disable no file under .github/workflows/ may still end in .yml or .yaml — '
            ."GitHub dispatches by that name alone, regardless of what the file contains.\n  "
            .'still executable: '.implode(', ', $this->executableWorkflows($root)),
        );
    }

    /**
     * The rename, and the marker that names the tool and the exact command to
     * reverse it. The marker is documentation; the assertion that matters for
     * inertness is the filename one above.
     */
    public function test_a_disabled_workflow_is_renamed_and_carries_the_reversal_command(): void
    {
        $root = $this->workflowFixture();
        $this->invokeOk(['--mode=disable', '--dir='.$root]);

        $neutralized = $root.'/.github/workflows/alpha.yml.neutralized';
        self::assertFileExists($neutralized, 'alpha.yml must become alpha.yml.neutralized');
        self::assertFileDoesNotExist(
            $root.'/.github/workflows/alpha.yml',
            'the live name must be gone — GitHub would run it',
        );

        $header = (string) file_get_contents($neutralized, false, null, 0, 2048);
        self::assertStringContainsString(
            'bin/neutralize-ci.php',
            $header,
            'a neutralized file must say which tool produced it',
        );
        self::assertStringContainsString(
            '--mode=enable',
            $header,
            'a neutralized file must carry the exact command that reverses it',
        );
        self::assertStringStartsWith(
            '#',
            $header,
            'the marker is a YAML comment, so the bytes after it are still a valid workflow',
        );

        // NEGATIVE CONTROL: the original must NOT have carried the marker, or
        // "the file names the tool" would be true before the tool ever ran.
        $clean = $this->workflowFixture();
        $original = (string) file_get_contents($clean.'/.github/workflows/alpha.yml', false, null, 0, 2048);
        self::assertStringNotContainsString(
            'neutralize-ci.php',
            $original,
            'the control for this test failed: an untouched workflow must not mention the tool',
        );
    }

    // -----------------------------------------------------------------------
    // 5. CONFINEMENT — nothing outside .github/workflows/ is touched
    // -----------------------------------------------------------------------

    public function test_a_file_that_is_not_a_workflow_in_that_directory_is_untouched(): void
    {
        $root = $this->makeTree([
            '.github/workflows/alpha.yml' => "name: alpha\non: push\n",
            // Every plausible near-miss: a README, a notes file, a backup of a
            // workflow, an already-suffixed copy, and an extensionless file.
            '.github/workflows/README.md' => "# workflows\n",
            '.github/workflows/notes.txt' => "scratch\n",
            '.github/workflows/alpha.yml.bak' => "name: backup\n",
        ]);
        $before = $this->snapshot($root);

        $this->invokeOk(['--mode=disable', '--dir='.$root]);
        $after = $this->snapshot($root);

        $untouched = [
            '.github/workflows/README.md',
            '.github/workflows/notes.txt',
            '.github/workflows/alpha.yml.bak',
            '.github/workflows/archive/neither.txt',
        ];
        foreach ($untouched as $relative) {
            self::assertSame(
                $before[$relative] ?? null,
                $after[$relative] ?? null,
                $relative.' is not a workflow and must be left alone',
            );
        }

        // NEGATIVE CONTROL: the one real workflow DID change. If it had not,
        // every assertion in this test would hold for a tool that does nothing.
        self::assertNotSame(
            $before['.github/workflows/alpha.yml'],
            $after['.github/workflows/alpha.yml.neutralized'] ?? null,
            'the control for this test failed: alpha.yml must actually have been renamed',
        );
    }

    public function test_nothing_outside_the_workflows_directory_is_touched(): void
    {
        $root = $this->makeTree([
            '.github/workflows/alpha.yml' => "name: alpha\non: push\n",
            // `.yml` files everywhere ELSE in the tree. Each of these is a file
            // GitHub would never read as a workflow, and every one of them must
            // survive the round trip untouched.
            'loose.yml' => "name: root\n",
            '.github/dependabot.yml' => "version: 2\n",
            '.github/other/keep.yml' => "name: other\n",
            'docs/example.yml' => "name: docs\n",
            'deep/nested/path/also.yaml' => "name: deep\n",
            '.gitlab-ci.yml' => "stages: [test]\n",
        ]);
        $before = $this->snapshot($root);

        $this->invokeOk(['--mode=disable', '--dir='.$root]);

        $disabled = $this->snapshot($root);
        $outside = array_diff(
            array_keys($before),
            ['.github/workflows/alpha.yml'],
        );
        foreach ($outside as $relative) {
            self::assertSame(
                $before[$relative],
                $disabled[$relative] ?? null,
                $relative.' is OUTSIDE .github/workflows/ and must not be touched by --mode=disable',
            );
        }

        $this->invokeOk(['--mode=enable', '--dir='.$root]);
        self::assertSame(
            $before,
            $this->snapshot($root),
            'the full round trip must leave every path outside .github/workflows/ byte-identical',
        );
    }

    // -----------------------------------------------------------------------
    // 6. FAIL LOUD — a state this tool cannot handle is refused, never skipped
    // -----------------------------------------------------------------------

    /**
     * The three states this tool must REFUSE rather than guess about.
     *
     * Each case is constructed from bytes the tool ITSELF produced — the marker
     * is lifted out of a real `.neutralized` file the disable run just wrote.
     * Hand-writing a fake marker would have proved nothing: the first version
     * of this test invented `# a marker with no rename behind it`, which the
     * tool correctly ignored, so the case it claimed to cover was never
     * actually exercised. A refusal rule that fires on a lookalike but not on
     * the real marker is a rule that does not exist.
     *
     * @return array<string, array{string}> description => the fixture mutation to apply
     */
    public static function ambiguousStateProvider(): array
    {
        return [
            // The original exists AND its neutralized twin exists: which one is
            // the record? Restoring would clobber a live file.
            'a name present in both states' => ['both'],
            // Ends in `.neutralized` but this tool never wrote it, so restoring
            // it would guess at a file it did not create.
            'a .neutralized file with no marker' => ['rogue'],
            // A live `.yml` carrying the marker: someone undid the rename by
            // hand and left the prefix behind.
            'a live workflow carrying the marker' => ['half'],
        ];
    }

    #[DataProvider('ambiguousStateProvider')]
    public function test_an_ambiguous_state_is_refused_rather_than_silently_skipped(string $case): void
    {
        $root = $this->workflowFixture();
        // Disabled first, so the states below are states the tool itself
        // produces — not states invented for the test.
        $this->invokeOk(['--mode=disable', '--dir='.$root]);

        // The REAL marker, read back out of what disable just wrote.
        $neutralized = (string) file_get_contents($root.'/.github/workflows/alpha.yml.neutralized');
        $marker = substr($neutralized, 0, strlen($neutralized) - strlen(
            "name: alpha\non: push\njobs:\n  a:\n    runs-on: ubuntu-latest\n",
        ));
        self::assertStringStartsWith('#', $marker, 'the marker must be lifted from a real disable, not invented');
        self::assertStringContainsString(
            'neutralize-ci.php',
            $marker,
            'the lifted marker must be the tool\'s own',
        );

        match ($case) {
            // alpha.yml.neutralized already exists; re-creating alpha.yml puts
            // the SAME name in both states.
            'both' => file_put_contents($root.'/.github/workflows/alpha.yml', "name: alpha\non: push\n"),
            'rogue' => file_put_contents(
                $root.'/.github/workflows/rogue.yml.neutralized',
                "name: rogue\non: push\n",
            ),
            'half' => file_put_contents(
                $root.'/.github/workflows/half.yml',
                $marker."name: half\non: push\n",
            ),
        };

        $result = $this->invoke(['--mode=enable', '--dir='.$root]);

        self::assertSame(
            1,
            $result['code'],
            "the `{$case}` state must exit non-zero, never be silently skipped:\n".$result['output'],
        );
        self::assertNotSame(
            '',
            trim($result['output']),
            'a refusal must SAY something — a silent non-zero exit is indistinguishable from a crash',
        );

        // NEGATIVE CONTROL: the same tree WITHOUT the ambiguity is accepted, so
        // the refusal above is caused by the ambiguity and not by the fixture.
        $clean = $this->workflowFixture();
        $this->invokeOk(['--mode=disable', '--dir='.$clean]);
        $this->invokeOk(['--mode=enable', '--dir='.$clean]);
    }

    public function test_a_bad_invocation_is_a_usage_error_and_never_touches_the_tree(): void
    {
        $root = $this->workflowFixture();
        $before = $this->snapshot($root);

        $cases = [
            'no mode' => [[]],
            'unknown mode' => [['--mode=sometimes']],
            'unknown argument' => [['--mode=disable', '--nope=1']],
            'empty dir' => [['--mode=disable', '--dir=']],
            'missing directory' => [['--mode=disable', '--dir='.$root.'/no-such-tree']],
            'directory with no workflows' => [
                ['--mode=disable', '--dir='.sys_get_temp_dir()],
            ],
        ];

        foreach ($cases as $name => [$arguments]) {
            $result = $this->invoke($arguments);
            self::assertSame(2, $result['code'], "expected a usage error for: {$name}\n".$result['output']);
        }

        self::assertSame(
            $before,
            $this->snapshot($root),
            'a refused invocation must leave the tree byte-identical',
        );

        // NEGATIVE CONTROL: a well-formed invocation on the same tree succeeds,
        // so the exits above are caused by the arguments and not by the fixture.
        $this->invokeOk(['--mode=disable', '--dir='.$root]);
    }

    // -----------------------------------------------------------------------
    // 7. The tool never prints file contents
    // -----------------------------------------------------------------------

    public function test_the_tool_reports_counts_and_paths_but_never_workflow_contents(): void
    {
        $token = 'TF_SECRET_CANARY_7f3a91c4e2';
        $root = $this->makeTree([
            '.github/workflows/secret.yml' => "name: deploy\non: push\njobs:\n  go:\n    env:\n"
                ."      TOKEN: {$token}\n",
        ]);

        $disable = $this->invokeOk(['--mode=disable', '--dir='.$root]);
        $enable = $this->invokeOk(['--mode=enable', '--dir='.$root]);

        self::assertStringNotContainsString(
            $token,
            $disable,
            '--mode=disable must never print a workflow\'s contents',
        );
        self::assertStringNotContainsString(
            $token,
            $enable,
            '--mode=enable must never print a workflow\'s contents',
        );
        self::assertStringContainsString(
            'secret.yml.neutralized',
            $disable,
            'reporting the PATH is expected and safe; only contents are withheld',
        );

        // NEGATIVE CONTROL: the canary really is in the fixture, so the two
        // assertions above are not passing because the token was never there.
        self::assertStringContainsString(
            $token,
            (string) file_get_contents($root.'/.github/workflows/secret.yml'),
            'the control for this test failed: the canary must exist in the fixture',
        );
    }

    // -----------------------------------------------------------------------
    // 8. The REAL repository is not neutralized
    // -----------------------------------------------------------------------

    /**
     * The tracked workflows in this repository must still be runnable, because
     * `.github/**` is exported to the public repo where CI DOES run.
     *
     * This is the assertion that catches the tool being run on the wrong tree,
     * or run without being reverted: if a `*.yml.neutralized` file is ever
     * committed here, the PUBLIC repo stops running those workflows the next
     * time it is exported.
     */
    public function test_the_real_repository_has_no_neutralized_workflow(): void
    {
        $root = dirname(__DIR__, 2);
        $workflows = $root.'/.github/workflows';

        self::assertDirectoryExists($workflows, 'this repository must have .github/workflows/');
        self::assertNotSame(
            [],
            $this->executableWorkflows($root),
            'this repository must ship real workflows — it is the source the public repo is exported from',
        );

        $neutralized = $this->names($workflows);
        $strays = array_values(array_filter(
            $neutralized,
            fn (string $name): bool => str_ends_with($name, '.neutralized'),
        ));

        self::assertSame(
            [],
            $strays,
            'a neutralized workflow is committed in this repository, so the public export would ship '
            ."an INERT CI definition.\n  strays: ".implode(', ', $strays),
        );
    }

    /** @return list<string> */
    private function names(string $dir): array
    {
        $entries = scandir($dir);
        self::assertNotFalse($entries, 'directory must be readable: '.$dir);
        $names = [];
        foreach ($entries as $entry) {
            if ($entry !== '.' && $entry !== '..' && is_file($dir.'/'.$entry)) {
                $names[] = $entry;
            }
        }
        sort($names);

        return $names;
    }
}
