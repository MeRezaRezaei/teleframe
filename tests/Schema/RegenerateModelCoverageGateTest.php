<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use MeRezaRezaei\Teleframe\Schema\Generator\SchemaRegenerator;
use MeRezaRezaei\Teleframe\Schema\Generator\TlRegenerateException;
use PHPUnit\Framework\TestCase;

/**
 * `teleframe:regenerate` must be a NO-OP on a clean tree, and it must
 * REFUSE rather than truncate when it cannot reproduce what it wipes.
 *
 * THE DEFECT THIS EXISTS TO KEEP FIXED
 *
 * `SchemaRegenerator::wipe()` deleted the whole of
 * `src/Schema/Generated/Models` and rewrote it from a Models pass that was
 * constructed with no MirrorShape, so it emitted the scheme-only anchor
 * projection: 12 files, 0 relations, over a tree holding one model per
 * mirror table. The run exited 0, printed a normal line, and left 253
 * models deleted — a loss nothing in the output mentioned.
 *
 * The pre-existing ±30% constructor-drift gate could not catch it, and the
 * reason is structural rather than a matter of tuning: the constructor count
 * comes from the TL SCHEME, while the model layer comes from the EXECUTED
 * DDL. Truncating the mirror layer moved that number by exactly zero, so
 * the gate was always satisfied. Hence the coverage gate below, which
 * compares populations that actually describe the layer being deleted.
 *
 * Four properties are pinned, one per test:
 *
 *  1. the committed Models tree is what regeneration emits, byte for byte;
 *  2. regeneration is a no-op on a populated tree (idempotence, in the
 *     tree rather than only in the manifest hash);
 *  3. an unavailable shape REFUSES and leaves the tree byte-for-byte intact;
 *  4. a shape that cannot cover the tree REFUSES and leaves it intact.
 *
 * 3 and 4 assert the file COUNT *and* a hash over every file's bytes. A
 * refusal that still wiped would satisfy a count check only if it wiped
 * nothing — which is the point, but the hash is what makes the assertion
 * about content rather than existence.
 */
final class RegenerateModelCoverageGateTest extends TestCase
{
    private const PACKAGE_ROOT = __DIR__.'/../..';

    private const SOURCES = self::PACKAGE_ROOT.'/schema/sources';

    private const MODELS_DIR = self::PACKAGE_ROOT.'/src/Schema/Generated/Models';

    private const MANIFEST = self::PACKAGE_ROOT.'/src/Schema/Generated/schema-manifest.json';

    /** @var list<string> scratch dirs to remove in tearDown */
    private array $scratch = [];

    protected function tearDown(): void
    {
        foreach ($this->scratch as $dir) {
            self::removeTree($dir);
        }
        $this->scratch = [];

        parent::tearDown();
    }

    private function scratchDir(string $tag): string
    {
        $dir = sys_get_temp_dir().'/tl-coverage-'.$tag.'-'.uniqid();
        mkdir($dir, 0777, true);
        $this->scratch[] = $dir;

        return $dir;
    }

    /** @return array{files:int,hash:string} count + content hash over a whole Generated tree */
    private static function fingerprint(string $generatedDir): array
    {
        $paths = [];
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($generatedDir, \FilesystemIterator::SKIP_DOTS),
        );
        foreach ($it as $file) {
            if ($file->isFile()) {
                // Sorted, so the hash is order-independent the way a tree is.
                $paths[substr($file->getPathname(), strlen($generatedDir))] = hash_file('sha256', $file->getPathname());
            }
        }
        ksort($paths);

        return ['files' => count($paths), 'hash' => hash('sha256', (string) json_encode($paths))];
    }

    private static function removeTree(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($it as $item) {
            $item->isDir() && ! $item->isLink() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($dir);
    }

    /**
     * THE HEADLINE PROPERTY: what regeneration emits IS what is committed.
     *
     * Byte comparison, not a file count — a run that emitted the right
     * NUMBER of files with the wrong content would pass a count check and
     * still be a drift gate that proves nothing.
     */
    public function test_regeneration_emits_the_committed_model_layer_byte_for_byte(): void
    {
        $out = $this->scratchDir('emit');
        (new SchemaRegenerator)->regenerate(self::SOURCES, $out);

        $emittedDir = $out.'/src/Schema/Generated/Models';
        $committed = glob(self::MODELS_DIR.'/*.php') ?: [];
        $emitted = glob($emittedDir.'/*.php') ?: [];
        sort($committed);
        sort($emitted);

        self::assertNotSame([], $committed, 'the committed Models tree must not be empty');
        self::assertSame(
            array_map('basename', $committed),
            array_map('basename', $emitted),
            'regeneration must emit exactly the committed model file set'
        );
        foreach ($committed as $path) {
            $name = basename($path);
            self::assertSame(
                (string) file_get_contents($path),
                (string) file_get_contents($emittedDir.'/'.$name),
                "regenerated model differs from the committed one: {$name}"
            );
        }
    }

    /**
     * The layer is the mirror's, not the scheme's: one model per mirror
     * table, and relations from declared foreign keys. Both numbers are
     * counted here rather than transcribed, and both are cross-checked
     * against the committed manifest so the manifest cannot quietly become
     * the only place the truth lives.
     */
    public function test_regenerated_layer_carries_the_mirror_model_and_relation_counts(): void
    {
        $out = $this->scratchDir('counts');
        $result = (new SchemaRegenerator)->regenerate(self::SOURCES, $out);
        $counts = $result['counts'];

        $shape = json_decode(
            (string) file_get_contents(self::PACKAGE_ROOT.'/src/Schema/schema/mirror-shape.json'),
            true,
        );
        $mirrorTables = count($shape['tables']);

        // Measured from the artifact, never hand-written: the Models pass
        // emits one class per reflected table.
        self::assertSame($mirrorTables, $counts['mirror_tables'], 'mirror_tables must be the shape table count');
        self::assertSame($mirrorTables, $counts['models'], 'one model per mirror table');
        self::assertSame(count(glob(self::MODELS_DIR.'/*.php') ?: []), $counts['models'], 'counts must match the committed tree');

        // The anchor projection the unwired pass produced declared 0 relations
        // while the shape declares hundreds of in-layer constraints, so a
        // positive relation count is the assertion that has teeth.
        self::assertGreaterThan(0, $counts['model_relations'], 'the mirror layer must carry relations');

        $committed = json_decode((string) file_get_contents(self::MANIFEST), true)['counts'];
        self::assertSame($counts, $committed, 'a fresh run must reproduce the committed manifest counts');
    }

    /**
     * Idempotence over the WHOLE tree, not just the manifest hash: two
     * consecutive runs must leave identical bytes. The historical manifest
     * pin could not see a model layer that changed underneath a stable hash.
     */
    public function test_regeneration_is_a_no_op_on_a_populated_tree(): void
    {
        $out = $this->scratchDir('idem');
        $engine = new SchemaRegenerator;

        $engine->regenerate(self::SOURCES, $out);
        $first = self::fingerprint($out.'/src/Schema/Generated');

        // Re-run over the tree the first run just populated — the case the
        // coverage gate's `onDisk > emitted` arm is written for.
        $engine->regenerate(self::SOURCES, $out);
        $second = self::fingerprint($out.'/src/Schema/Generated');

        self::assertSame($first, $second, 'a second regeneration must change nothing');
        self::assertGreaterThan(1000, $first['files'], 'fingerprint must cover the whole tree, not a sample');
    }

    /**
     * REFUSAL PROOF, arm 1 — the shape cannot be produced.
     *
     * The refusal must name what is missing and leave the tree untouched.
     * Both are asserted: the message names the artifact and the command that
     * produces it, and the tree's file count AND content hash are unchanged
     * afterwards. A refusal that still wiped would be worse than the original
     * bug, because it would look like the fix working.
     */
    public function test_missing_shape_refuses_and_leaves_the_tree_intact(): void
    {
        $out = $this->scratchDir('noshape');
        (new SchemaRegenerator)->regenerate(self::SOURCES, $out);
        $before = self::fingerprint($out.'/src/Schema/Generated');

        $engine = (new SchemaRegenerator)->mirrorShapePath($out.'/no-such-mirror-shape.json');

        try {
            $engine->regenerate(self::SOURCES, $out);
            self::fail('a run whose shape cannot be produced must refuse, not truncate');
        } catch (TlRegenerateException $e) {
            self::assertStringContainsString('refusing to regenerate', $e->getMessage());
            self::assertStringContainsString('mirror shape artifact is missing', $e->getMessage());
            self::assertStringContainsString('bin/generate-mirror-shape.php', $e->getMessage());
        }

        self::assertSame($before, self::fingerprint($out.'/src/Schema/Generated'), 'a refusal must not touch the tree');
    }

    /**
     * REFUSAL PROOF, arm 2 — the shape cannot COVER the tree.
     *
     * A shape narrowed to a few tables is the shape of the original defect
     * reached a different way: this run genuinely emits fewer models than
     * are on disk, so the wipe would delete files nothing recreates. The
     * tree must survive, which is the property the old code lacked.
     *
     * `--force` must NOT bypass it. Force means "I accept the
     * constructor-count drift"; it does not mean "delete model files whose
     * producer did not run", so the narrowing is refused under force too.
     */
    public function test_a_shape_that_cannot_cover_the_tree_refuses_and_leaves_it_intact(): void
    {
        $out = $this->scratchDir('narrow');
        (new SchemaRegenerator)->regenerate(self::SOURCES, $out);
        $before = self::fingerprint($out.'/src/Schema/Generated');

        // Narrow the real artifact rather than transcribing tables: read it,
        // keep the first few, write it out. The tables kept are whatever the
        // measurement held, so nothing here is hand-listed.
        $shape = json_decode(
            (string) file_get_contents(self::PACKAGE_ROOT.'/src/Schema/schema/mirror-shape.json'),
            true,
        );
        $kept = array_slice(array_keys($shape['tables']), 0, 3, true);
        $shape['tables'] = array_intersect_key($shape['tables'], array_flip($kept));
        $narrowPath = $out.'/narrow-shape.json';
        file_put_contents($narrowPath, (string) json_encode($shape));

        self::assertGreaterThan(0, $before['files'], 'the populated tree must have content to protect');

        foreach ([false, true] as $force) {
            $engine = (new SchemaRegenerator)->mirrorShapePath($narrowPath)->force($force);
            try {
                $engine->regenerate(self::SOURCES, $out);
                self::fail('a narrowed shape must refuse'.($force ? ' even under --force' : ''));
            } catch (TlRegenerateException $e) {
                self::assertStringContainsString('refusing to wipe', $e->getMessage());
                // The message must quantify the loss it is preventing, or an
                // operator cannot tell a routine run from a 253-file one.
                self::assertMatchesRegularExpression(
                    '/holds \d+ model file\(s\) but this run emits only \d+/',
                    $e->getMessage(),
                    'the refusal must state the two populations it compared'
                );
            }
            self::assertSame(
                $before,
                self::fingerprint($out.'/src/Schema/Generated'),
                'a refusal must not touch the tree'.($force ? ' (--force)' : '')
            );
        }
    }

    /**
     * A shape that parses but measures nothing is the trap a naive equality
     * check walks into: `emitted == mirrorTables` would be `0 == 0`, the wipe
     * would proceed, and the model layer would be deleted wholesale. An
     * empty measurement of a 265-table dial is never legitimate.
     */
    public function test_a_degenerate_shape_refuses_rather_than_wiping_an_empty_layer(): void
    {
        $out = $this->scratchDir('empty');
        (new SchemaRegenerator)->regenerate(self::SOURCES, $out);
        $before = self::fingerprint($out.'/src/Schema/Generated');

        $emptyPath = $out.'/empty-shape.json';
        file_put_contents($emptyPath, (string) json_encode(['tables' => new \stdClass]));

        $engine = (new SchemaRegenerator)->mirrorShapePath($emptyPath);
        try {
            $engine->regenerate(self::SOURCES, $out);
            self::fail('a shape measuring 0 tables must refuse');
        } catch (TlRegenerateException $e) {
            self::assertStringContainsString('refusing to regenerate', $e->getMessage());
            self::assertStringContainsString('0 tables', $e->getMessage());
        }

        self::assertSame($before, self::fingerprint($out.'/src/Schema/Generated'), 'a refusal must not touch the tree');
    }
}
