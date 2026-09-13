<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use PHPUnit\Framework\TestCase;

/**
 * Task 3 golden gate: the committed generated/ artifacts are exactly what
 * `php bin/regenerate` produces from the committed v227 mirror, and the
 * run is deterministic — two consecutive regenerations to temp out-dirs
 * must produce byte-identical manifests (and trees), matching the
 * committed manifest hash pin.
 *
 * Count bands (verified 2026-09-13, layer 227):
 * 1685 constructors / 799 methods / migrations = legacy (13) + mirror (25)
 * = 38 / models = legacy Tl* (12) + mirror (400) = 412 / 3116 dtos /
 * factories = legacy (12) + mirror (400) = 412 / 763 tables. The mirror
 * pipeline (catalog, 37 parents, Models/Mirror, Factories/Mirror,
 * migrations/mirror) runs additively inside SchemaRegenerator.
 * Full regeneration takes well under a second, so the double-run runs
 * the FULL schema set (no subset flag needed).
 */
final class RegenerationGoldenTest extends TestCase
{
    private const PACKAGE_ROOT = __DIR__.'/../..';

    private const MANIFEST = self::PACKAGE_ROOT.'/src/Schema/Generated/schema-manifest.json';

    /** sha256 of the committed generated/schema-manifest.json (full-run pin). */
    private const COMMITTED_MANIFEST_SHA256 = '0c0d81bd4522d81a78691aa76113d767319ff2dbcc3f9b0dfab47fda0ebc6ffd';

    public function test_committed_manifest_exists_and_pins_layer_227(): void
    {
        self::assertFileExists(self::MANIFEST);
        $manifest = json_decode((string) file_get_contents(self::MANIFEST), true);
        self::assertIsArray($manifest);
        self::assertSame(227, $manifest['layer']);
        self::assertSame(
            ['TL_mtproto_v1.tl', 'TL_secret.tl', 'TL_telegram_v227.tl'],
            $manifest['sources'],
        );
    }

    public function test_committed_manifest_counts_within_bands(): void
    {
        $counts = json_decode((string) file_get_contents(self::MANIFEST), true)['counts'];
        self::assertGreaterThan(1500, $counts['constructors']);
        self::assertLessThan(2500, $counts['constructors']);
        self::assertGreaterThan(750, $counts['methods']);
        self::assertLessThan(950, $counts['methods']);
        self::assertGreaterThan(700, $counts['tables']);
        self::assertLessThan(850, $counts['tables']);
    }

    public function test_committed_artifact_files_within_bands_and_marked_generated(): void
    {
        $migrations = self::phpFiles(self::PACKAGE_ROOT.'/src/Schema/Generated/migrations');
        $models = self::phpFiles(self::PACKAGE_ROOT.'/src/Schema/Generated/Models');
        $dtos = self::phpFiles(self::PACKAGE_ROOT.'/src/Schema/Generated/Data');
        $factories = self::phpFiles(self::PACKAGE_ROOT.'/src/Schema/Generated/Factories');

        self::assertGreaterThan(30, count($migrations), 'migrations lower bound');
        self::assertLessThan(50, count($migrations), 'migrations upper bound');
        self::assertGreaterThan(380, count($models), 'models lower bound');
        self::assertLessThan(460, count($models), 'models upper bound');
        self::assertGreaterThan(2800, count($dtos), 'dtos lower bound');
        self::assertLessThan(3400, count($dtos), 'dtos upper bound');
        self::assertGreaterThan(380, count($factories), 'factories lower bound');
        self::assertLessThan(460, count($factories), 'factories upper bound');

        foreach ([$migrations, $models, $dtos, $factories] as $files) {
            foreach ($files as $path) {
                self::assertStringContainsString(
                    'GENERATED',
                    (string) file_get_contents($path),
                    "missing @generated marker: {$path}",
                );
            }
        }
    }

    public function test_anchor_model_and_migration_present(): void
    {
        self::assertFileExists(self::PACKAGE_ROOT.'/src/Schema/Generated/Models/TlUser.php');
        self::assertStringContainsString(
            "protected \$table = 'tf_users';",
            (string) file_get_contents(self::PACKAGE_ROOT.'/src/Schema/Generated/Models/TlUser.php'),
        );
        $manifest = json_decode((string) file_get_contents(self::MANIFEST), true);
        self::assertArrayHasKey('tf_users', $manifest['tables']);
    }

    public function test_committed_mirror_section_within_bands(): void
    {
        $manifest = json_decode((string) file_get_contents(self::MANIFEST), true);
        self::assertArrayHasKey('mirror', $manifest, 'manifest must carry the mirror section');
        $mirror = $manifest['mirror'];
        self::assertSame(37, $mirror['parents']);
        self::assertGreaterThan(300, count($mirror['tables']), 'mirror table map lower bound');
        self::assertLessThan(500, count($mirror['tables']), 'mirror table map upper bound');
        self::assertSame(25, $mirror['migrations']);
        self::assertSame(400, $mirror['models']);
        self::assertSame(400, $mirror['factories']);
        self::assertSame(
            227,
            $manifest['layer'],
            'legacy counts must stay pinned to the committed layer',
        );
    }

    public function test_committed_mirror_artifacts_are_generated_and_present(): void
    {
        $mirrorMigrations = self::phpFiles(self::PACKAGE_ROOT.'/src/Schema/Generated/migrations/mirror');
        $mirrorModels = self::phpFiles(self::PACKAGE_ROOT.'/src/Schema/Generated/Models/Mirror');
        $mirrorFactories = self::phpFiles(self::PACKAGE_ROOT.'/src/Schema/Generated/Factories/Mirror');
        self::assertSame(25, count($mirrorMigrations));
        self::assertSame(400, count($mirrorModels));
        self::assertSame(400, count($mirrorFactories));

        foreach ([$mirrorMigrations, $mirrorModels, $mirrorFactories] as $files) {
            foreach ($files as $path) {
                self::assertStringContainsString(
                    'GENERATED',
                    (string) file_get_contents($path),
                    "missing @generated marker: {$path}",
                );
            }
        }
    }

    /**
     * Determinism gate (plan Task 3 Step 1): double-run bin/regenerate via
     * proc_open into temp out-dirs — manifests must be sha256-identical to
     * each other AND to the committed manifest pin.
     */
    public function test_regeneration_is_deterministic_and_matches_committed_pin(): void
    {
        $bin = self::PACKAGE_ROOT.'/bin/regenerate';
        self::assertFileExists($bin);

        $outA = sys_get_temp_dir().'/tl-golden-a-'.getmypid();
        $outB = sys_get_temp_dir().'/tl-golden-b-'.getmypid();

        $run = static function (string $out) use ($bin): void {
            $cmd = sprintf('%s %s %s 2>&1', escapeshellarg(PHP_BINARY), escapeshellarg($bin), escapeshellarg('--out='.$out));
            $proc = proc_open($cmd, [1 => ['pipe', 'w']], $pipes);
            self::assertIsResource($proc);
            fclose($pipes[1]);
            self::assertSame(0, proc_close($proc), "bin/regenerate failed for {$out}");
        };

        try {
            $run($outA);
            $run($outB);

            $hashA = hash_file('sha256', $outA.'/src/Schema/Generated/schema-manifest.json');
            $hashB = hash_file('sha256', $outB.'/src/Schema/Generated/schema-manifest.json');
            self::assertSame($hashA, $hashB, 'two consecutive runs differ');

            $committed = hash_file('sha256', self::MANIFEST);
            self::assertSame($hashA, $committed, 'fresh run does not reproduce committed manifest');
            self::assertSame(self::COMMITTED_MANIFEST_SHA256, $committed, 'committed manifest pin drifted');

            // Whole-tree byte equality between the two runs.
            $filesA = self::relativeHashes($outA.'/src/Schema/Generated');
            $filesB = self::relativeHashes($outB.'/src/Schema/Generated');
            self::assertSame($filesA, $filesB);
            self::assertGreaterThan(3000, count($filesA));
        } finally {
            exec('rm -rf '.escapeshellarg($outA).' '.escapeshellarg($outB));
        }
    }

    /** @return list<string> absolute paths of *.php files under dir (sorted). */
    private static function phpFiles(string $dir): array
    {
        self::assertDirectoryExists($dir);
        $all = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if ($f->getExtension() === 'php') {
                $all[] = $f->getPathname();
            }
        }
        sort($all);

        return $all;
    }

    /** @return array<string, string|false> relative path => sha256, sorted by path. */
    private static function relativeHashes(string $generatedDir): array
    {
        $out = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($generatedDir, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if ($f->isFile()) {
                $out[substr($f->getPathname(), strlen($generatedDir) + 1)] = hash_file('sha256', $f->getPathname());
            }
        }
        ksort($out);

        return $out;
    }
}
