<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Standalone;

use PHPUnit\Framework\TestCase;

/**
 * The private/public split's PUBLISH half: the exported tree must carry LIVE
 * workflows, whatever state the private repository's HEAD is in.
 *
 * THE DEFECT UNDER TEST. `bin/neutralize-ci.php --mode=disable` renames every
 * workflow in the private repository to `*.yml.neutralized` so the private
 * repo's Actions never fire, and `bin/public-manifest.txt:110` allows
 * `.github/**` broadly — so the exporter publishes whatever workflow names
 * HEAD carries. When private HEAD carries the neutralized names, a naive export
 * copies the INERT files into the public tree: GitHub Actions reads only names
 * ending `.yml`/`.yaml`, so public CI is dead, while the gate is green, the
 * completeness post-condition is green, and the script exits 0. Nothing fails.
 * A green publish that deletes CI.
 *
 * Measured against the exporter as it stood before this contract existed
 * (poisoned synthetic HEAD, 8 workflows): exported 5147 files, "public surface
 * OK: 5147 tracked files, 0 outside the manifest", EXIT 0 — and ZERO live
 * `.yml` files in the exported tree.
 *
 * THE FIX UNDER TEST. bin/export-public-repo.sh runs the neutralizer's own
 * `--mode=enable` against the TARGET tree after copying and before gating,
 * committing and printing the push command, and then refuses to publish if any
 * `*.neutralized` file survives. It delegates rather than reimplementing,
 * because the marker block lives in exactly one place (`MARKER_LINES` in
 * neutralize-ci.php) and a second copy of it is precisely the drift this pair
 * of scripts exists to prevent.
 *
 * EVERY test that asserts a pass carries a NEGATIVE CONTROL. The central
 * assertion here is "the exported tree contains zero `*.neutralized` files" —
 * and a check that always passes proves nothing, so the detector is itself run
 * against a tree where the file IS present. The other negative control is the
 * source tree: it must still hold `*.neutralized` AFTER a successful export, or
 * the export proved nothing (it might simply have copied a live file all along).
 */
final class ExportWorkflowNeutralizeTest extends TestCase
{
    private const REPO_ROOT = __DIR__.'/../..';

    /** Files the exporter and the gate actually require inside bin/. */
    private const REQUIRED_BIN = [
        'check-public-surface.php',
        'export-public-repo.sh',
        'neutralize-ci.php',
        'public-manifest.txt',
    ];

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
     * A throwaway git repository that is a REAL, minimal version of this one:
     * the four bin/ files the exporter needs, a workflow, and a public path.
     *
     * It is a git repository on purpose. The exporter reads its content with
     * `git show HEAD:<path>` and refuses to copy anything uncommitted, so the
     * only way to reproduce the defect is to commit the neutralized names to a
     * HEAD and export from there.
     *
     * @param  array<string, string>  $files  repo-relative path => contents
     */
    private function makeSourceRepo(array $files, bool $neutralizeAndCommit): string
    {
        $root = $this->scratchDir();
        mkdir($root.'/bin', 0o777, true);
        mkdir($root.'/.github/workflows', 0o777, true);

        foreach (self::REQUIRED_BIN as $name) {
            $source = self::REPO_ROOT.'/bin/'.$name;
            self::assertFileExists($source, "the exporter requires bin/{$name} to exist");
            copy($source, $root.'/bin/'.$name);
        }

        foreach ($files as $relative => $contents) {
            $full = $root.'/'.$relative;
            if (! is_dir(dirname($full))) {
                mkdir(dirname($full), 0o777, true);
            }
            file_put_contents($full, $contents);
        }

        $this->git($root, ['init', '-q', '-b', 'main', '.']);
        $this->git($root, ['add', '-A']);
        $this->commit($root, 'base');

        if ($neutralizeAndCommit) {
            $disabled = $this->neutralize($root, 'disable');
            self::assertSame(
                0,
                $disabled['code'],
                "the fixture must neutralize cleanly:\n".$disabled['output']
            );
            $this->git($root, ['add', '-A']);
            $this->commit($root, 'neutralize');
        }

        return $root;
    }

    private function scratchDir(): string
    {
        $root = sys_get_temp_dir().'/teleframe-export-'.bin2hex(random_bytes(8));
        mkdir($root, 0o777, true);
        $this->scratch[] = $root;

        return $root;
    }

    // -----------------------------------------------------------------------
    // Running things
    // -----------------------------------------------------------------------

    /**
     * @param  list<string>  $arguments
     * @return array{code: int, output: string}
     */
    private function execCommand(string $command): array
    {
        $output = [];
        $code = 0;
        exec($command.' 2>&1', $output, $code);

        return ['code' => $code, 'output' => implode("\n", $output)];
    }

    /** @param list<string> $arguments */
    private function git(string $cwd, array $arguments): string
    {
        $command = 'git -C '.escapeshellarg($cwd);
        foreach ($arguments as $argument) {
            $command .= ' '.escapeshellarg($argument);
        }
        $result = $this->execCommand($command);
        self::assertSame(0, $result['code'], "git failed:\n".$result['output']);

        return $result['output'];
    }

    private function commit(string $cwd, string $message): void
    {
        $this->git($cwd, ['-c', 'user.name=test', '-c', 'user.email=test@example.invalid',
            'commit', '-q', '-m', $message]);
    }

    /** @return array{code: int, output: string} */
    private function neutralize(string $root, string $mode): array
    {
        return $this->execCommand(escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/bin/neutralize-ci.php')
            .' --mode='.$mode.' --dir='.escapeshellarg($root));
    }

    /** @return array{code: int, output: string} */
    private function export(string $sourceRoot, string $target): array
    {
        return $this->execCommand('bash '.escapeshellarg($sourceRoot.'/bin/export-public-repo.sh')
            .' '.escapeshellarg($target));
    }

    // -----------------------------------------------------------------------
    // Fixtures of the byte-level traps
    // -----------------------------------------------------------------------

    /**
     * A workflow carrying ALL THREE byte-level traps at once: a UTF-8 BOM,
     * CRLF line endings, and NO trailing newline.
     *
     * Each one independently breaks a naive round trip — a `tail -n +N` strip
     * of the marker mangles the BOM and CRLF, and anything that re-encodes or
     * trims loses the missing final newline. A single file carrying all three
     * is therefore the smallest fixture that proves the export path is lossless
     * rather than merely reversible for well-behaved input.
     */
    private function trappedWorkflow(string $name = 'probe.yml'): string
    {
        return "\xEF\xBB\xBF"
            ."name: probe\r\n"
            ."on: push\r\n"
            ."jobs:\r\n"
            ."  a:\r\n"
            .'    runs-on: ubuntu-latest';
    }

    // -----------------------------------------------------------------------
    // Helpers over an exported tree
    // -----------------------------------------------------------------------

    /**
     * Every `*.neutralized` file under $root, repo-relative and sorted.
     *
     * This is the detector. It is the same shape the shell step uses
     * (`find … -name '*.neutralized' -type f`), expressed in PHP so the test
     * and the gate agree on what "inert file" means.
     *
     * @return list<string>
     */
    private function neutralizedFiles(string $root): array
    {
        $found = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $info) {
            /** @var \SplFileInfo $info */
            if ($info->isFile() && str_ends_with($info->getFilename(), '.neutralized')) {
                $found[] = substr($info->getPathname(), strlen($root) + 1);
            }
        }
        sort($found);

        return $found;
    }

    /** @return list<string> live `.yml`/`.yaml` names under .github/workflows */
    private function liveWorkflows(string $root): array
    {
        $directory = $root.'/.github/workflows';
        if (! is_dir($directory)) {
            return [];
        }
        $found = [];
        foreach ((array) scandir($directory) as $entry) {
            if (str_ends_with((string) $entry, '.yml') || str_ends_with((string) $entry, '.yaml')) {
                $found[] = (string) $entry;
            }
        }
        sort($found);

        return $found;
    }

    // -----------------------------------------------------------------------
    // 1. The defect: a neutralized private HEAD must still publish LIVE
    //    workflows
    // -----------------------------------------------------------------------

    public function test_a_neutralized_private_head_still_exports_live_workflows(): void
    {
        $source = $this->makeSourceRepo([
            '.github/workflows/probe.yml' => $this->trappedWorkflow(),
            'README.md' => "public readme\n",
        ], neutralizeAndCommit: true);

        // NEGATIVE CONTROL, part 1 — establish that the fixture really is the
        // broken state. Without this the test below could pass because the
        // export copied a live file, which proves nothing about re-enabling.
        self::assertSame(
            ['.github/workflows/probe.yml.neutralized'],
            $this->neutralizedFiles($source),
            'fixture must present a neutralized private HEAD, or this test proves nothing'
        );
        self::assertSame([], $this->liveWorkflows($source), 'the private HEAD must hold no live workflow');

        $target = $this->scratchDir().'/export';
        $result = $this->export($source, $target);

        self::assertSame(0, $result['code'], "export must succeed on a neutralized HEAD:\n".$result['output']);

        self::assertSame(
            [],
            $this->neutralizedFiles($target),
            'the EXPORTED tree must contain zero inert files — any one of them is public CI silently dead'
        );
        self::assertSame(
            ['probe.yml'],
            $this->liveWorkflows($target),
            'the exported tree must hold the live workflow GitHub actually dispatches'
        );

        // NEGATIVE CONTROL, part 2 — the private tree is untouched. The export
        // re-enabled its own COPY; it must never have mutated the source.
        self::assertSame(
            ['.github/workflows/probe.yml.neutralized'],
            $this->neutralizedFiles($source),
            'the export must not re-enable the PRIVATE tree; only the exported copy'
        );
    }

    // -----------------------------------------------------------------------
    // 2. LOSSLESS: BOM + CRLF + missing trailing newline survive the pipeline
    // -----------------------------------------------------------------------

    public function test_the_exported_workflow_is_byte_identical_to_the_original(): void
    {
        $original = $this->trappedWorkflow();
        $source = $this->makeSourceRepo([
            '.github/workflows/probe.yml' => $original,
            'README.md' => "public readme\n",
        ], neutralizeAndCommit: true);

        $target = $this->scratchDir().'/export';
        $result = $this->export($source, $target);
        self::assertSame(0, $result['code'], $result['output']);

        $exported = $target.'/.github/workflows/probe.yml';
        self::assertFileExists($exported);

        // Asserted on the three traps individually as well as on the hash, so a
        // failure says WHICH property broke rather than only that "bytes differ".
        $bytes = (string) file_get_contents($exported);
        self::assertStringStartsWith("\xEF\xBB\xBF", $bytes, 'the BOM was consumed');
        self::assertStringContainsString("\r\n", $bytes, 'CRLF endings were rewritten to LF');
        self::assertStringNotContainsString("\n\n", $bytes, 'a stray newline was introduced');
        self::assertStringEndsWith('ubuntu-latest', $bytes, 'the missing trailing newline was added');
        self::assertSame(
            $original,
            $bytes,
            'the exported workflow must be byte-identical to the pre-neutralize original'
        );

        // The commit is what actually gets pushed, so identity is checked there
        // too — not only on disk. The comparison is on git BLOB ids rather than
        // on text: exec() returns its output split on line endings, which would
        // silently rewrite this fixture's CRLF and supply the missing trailing
        // newline, making a byte comparison meaningless.
        $probe = $this->scratchDir().'/probe.yml';
        file_put_contents($probe, $original);

        self::assertSame(
            trim($this->git($target, ['hash-object', $probe])),
            trim($this->git($target, ['rev-parse', 'HEAD:.github/workflows/probe.yml'])),
            'the workflow in the export COMMIT must be the original bytes, not just the file on disk'
        );
    }

    // -----------------------------------------------------------------------
    // 3. FAIL LOUD: an ambiguous state refuses to publish
    // -----------------------------------------------------------------------

    public function test_an_unmarked_neutralized_file_refuses_to_publish(): void
    {
        // A `.neutralized` file carrying NO marker is not one neutralize-ci
        // created: it may be an unrelated tracked file that merely ends in that
        // string, and restoring it would be guessing. The neutralizer refuses it
        // by design, and the exporter must surface that refusal as a non-zero
        // exit rather than publishing around it.
        // The rogue file is added AFTER the neutralized commit, deliberately.
        // `neutralize-ci --mode=disable` refuses to touch an unmarked
        // `.neutralized` file and exits 1, so a fixture that carried this file
        // from the start could never reach its neutralized state at all — the
        // disable pass would abort before renaming `probe.yml`, leaving the tree
        // unchanged. Adding it afterwards builds the state the export actually
        // has to survive: a HEAD that is neutralized AND carries an inert file
        // the tool never created.
        $source = $this->makeSourceRepo([
            '.github/workflows/probe.yml' => $this->trappedWorkflow(),
            'README.md' => "public readme\n",
        ], neutralizeAndCommit: true);

        file_put_contents($source.'/.github/workflows/rogue.yml.neutralized', "name: rogue\non: push\n");
        $this->git($source, ['add', '-A']);
        $this->commit($source, 'rogue');

        self::assertSame(
            ['.github/workflows/probe.yml.neutralized', '.github/workflows/rogue.yml.neutralized'],
            $this->neutralizedFiles($source),
            'the fixture must hold BOTH our own neutralized file and the unmarked one'
        );

        $target = $this->scratchDir().'/export';
        $result = $this->export($source, $target);

        self::assertNotSame(0, $result['code'], "an ambiguous workflow state must NOT publish:\n".$result['output']);
        self::assertStringContainsString(
            'NOTHING has been',
            $result['output'],
            'the refusal must say plainly that nothing was published'
        );
        self::assertDirectoryDoesNotExist(
            $target.'/.git',
            'a refused export must not leave a committable tree that could still be pushed by hand'
        );
    }

    // -----------------------------------------------------------------------
    // 4. Backward compatibility: the old invocation on a CLEAN tree
    // -----------------------------------------------------------------------

    public function test_a_clean_private_tree_still_exports_exactly_as_before(): void
    {
        $original = $this->trappedWorkflow();
        $source = $this->makeSourceRepo([
            '.github/workflows/probe.yml' => $original,
            'README.md' => "public readme\n",
        ], neutralizeAndCommit: false);

        $target = $this->scratchDir().'/export';
        $result = $this->export($source, $target);

        self::assertSame(0, $result['code'], "the classic clean-tree export must still succeed:\n".$result['output']);
        self::assertSame([], $this->neutralizedFiles($target));
        self::assertSame(['probe.yml'], $this->liveWorkflows($target));
        self::assertSame($original, (string) file_get_contents($target.'/.github/workflows/probe.yml'));

        // The re-enable pass must be a genuine NO-OP here, not a rewrite: the
        // neutralizer reports "restored 0". If it ever starts rewriting clean
        // trees, this fails.
        self::assertStringContainsString(
            'restored 0 workflow(s)',
            $result['output'],
            'on a never-neutralized tree the re-enable pass must change nothing'
        );
    }

    // -----------------------------------------------------------------------
    // 5. NEGATIVE CONTROL FOR THE DETECTOR ITSELF
    // -----------------------------------------------------------------------

    public function test_the_inert_file_detector_actually_fires(): void
    {
        // Every other test asserts the exported tree holds ZERO `*.neutralized`
        // files. A detector that can only ever return empty would satisfy all of
        // them. So it is run here against trees where the file IS present — one
        // inside .github/workflows/ and one nested deeper — and must find both.
        $root = $this->scratchDir();
        mkdir($root.'/.github/workflows', 0o777, true);
        mkdir($root.'/.github/ISSUE_TEMPLATE', 0o777, true);
        file_put_contents($root.'/.github/workflows/a.yml.neutralized', "x\n");
        file_put_contents($root.'/.github/ISSUE_TEMPLATE/b.yml.neutralized', "x\n");

        self::assertSame(
            ['.github/ISSUE_TEMPLATE/b.yml.neutralized', '.github/workflows/a.yml.neutralized'],
            $this->neutralizedFiles($root),
            'the detector must find an inert file at either depth'
        );
    }

    // -----------------------------------------------------------------------
    // 6. The exporter declares the invariant it now enforces
    // -----------------------------------------------------------------------

    public function test_the_exporter_documents_and_reports_the_live_workflow_invariant(): void
    {
        $script = (string) file_get_contents(self::REPO_ROOT.'/bin/export-public-repo.sh');

        self::assertStringContainsString(
            '--mode=enable',
            $script,
            'the exporter must delegate to the neutralizer instead of stripping the marker itself'
        );
        self::assertStringContainsString(
            '*.neutralized',
            $script,
            'the exporter must assert that no inert file survives into the published tree'
        );

        $source = $this->makeSourceRepo([
            '.github/workflows/probe.yml' => $this->trappedWorkflow(),
            'README.md' => "public readme\n",
        ], neutralizeAndCommit: true);
        $target = $this->scratchDir().'/export';
        $result = $this->export($source, $target);
        self::assertSame(0, $result['code'], $result['output']);
        self::assertStringContainsString(
            'live .yml/.yaml',
            $result['output'],
            'the summary must report how many live workflows are about to be published'
        );
    }

    // -----------------------------------------------------------------------
    // Teardown
    // -----------------------------------------------------------------------

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
