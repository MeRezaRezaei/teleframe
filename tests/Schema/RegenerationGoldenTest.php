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
 * Count bands (owner's real fork run, verified 2026-08-28, layer 227):
 * 1685 constructors / 799 methods / 635 migrations / 2928 models /
 * 3116 dtos / 3678 tables — bands bracket with drift headroom.
 * Full regeneration takes well under a second, so the double-run runs
 * the FULL schema set (no subset flag needed).
 */
final class RegenerationGoldenTest extends TestCase
{
    private const PACKAGE_ROOT = __DIR__ . '/../..';
    private const MANIFEST = self::PACKAGE_ROOT . '/generated/schema-manifest.json';

    /** sha256 of the committed generated/schema-manifest.json (full-run pin). */
    private const COMMITTED_MANIFEST_SHA256 = 'b833e98935caed278ccf373a36ecdac508d1d855546550ba422c1778751f83e2';

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
        self::assertGreaterThan(3000, $counts['tables']);
        self::assertLessThan(4200, $counts['tables']);
    }

    public function test_committed_artifact_files_within_bands_and_marked_generated(): void
    {
        $migrations = self::phpFiles(self::PACKAGE_ROOT . '/generated/migrations');
        $models = self::phpFiles(self::PACKAGE_ROOT . '/generated/Models');
        $dtos = self::phpFiles(self::PACKAGE_ROOT . '/generated/Data');
        $factories = self::phpFiles(self::PACKAGE_ROOT . '/generated/Factories');

        self::assertGreaterThan(500, count($migrations), 'migrations lower bound');
        self::assertLessThan(800, count($migrations), 'migrations upper bound');
        self::assertGreaterThan(2500, count($models), 'models lower bound');
        self::assertLessThan(3500, count($models), 'models upper bound');
        self::assertGreaterThan(2800, count($dtos), 'dtos lower bound');
        self::assertLessThan(3600, count($dtos), 'dtos upper bound');
        self::assertGreaterThan(1500, count($factories), 'factories lower bound');

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
        self::assertFileExists(self::PACKAGE_ROOT . '/generated/Models/TlUser.php');
        self::assertStringContainsString(
            "protected \$table = 'tl_user';",
            (string) file_get_contents(self::PACKAGE_ROOT . '/generated/Models/TlUser.php'),
        );
        $manifest = json_decode((string) file_get_contents(self::MANIFEST), true);
        self::assertArrayHasKey('tl_user', $manifest['tables']);
    }

    /**
     * Determinism gate (plan Task 3 Step 1): double-run bin/regenerate via
     * proc_open into temp out-dirs — manifests must be sha256-identical to
     * each other AND to the committed manifest pin.
     */
    public function test_regeneration_is_deterministic_and_matches_committed_pin(): void
    {
        $bin = self::PACKAGE_ROOT . '/bin/regenerate';
        self::assertFileExists($bin);

        $outA = sys_get_temp_dir() . '/tl-golden-a-' . getmypid();
        $outB = sys_get_temp_dir() . '/tl-golden-b-' . getmypid();

        $run = static function (string $out) use ($bin): void {
            $cmd = sprintf('%s %s %s 2>&1', escapeshellarg(PHP_BINARY), escapeshellarg($bin), escapeshellarg('--out=' . $out));
            $proc = proc_open($cmd, [1 => ['pipe', 'w']], $pipes);
            self::assertIsResource($proc);
            fclose($pipes[1]);
            self::assertSame(0, proc_close($proc), "bin/regenerate failed for {$out}");
        };

        try {
            $run($outA);
            $run($outB);

            $hashA = hash_file('sha256', $outA . '/generated/schema-manifest.json');
            $hashB = hash_file('sha256', $outB . '/generated/schema-manifest.json');
            self::assertSame($hashA, $hashB, 'two consecutive runs differ');

            $committed = hash_file('sha256', self::MANIFEST);
            self::assertSame($hashA, $committed, 'fresh run does not reproduce committed manifest');
            self::assertSame(self::COMMITTED_MANIFEST_SHA256, $committed, 'committed manifest pin drifted');

            // Whole-tree byte equality between the two runs.
            $filesA = self::relativeHashes($outA . '/generated');
            $filesB = self::relativeHashes($outB . '/generated');
            self::assertSame($filesA, $filesB);
            self::assertGreaterThan(8000, count($filesA));
        } finally {
            exec('rm -rf ' . escapeshellarg($outA) . ' ' . escapeshellarg($outB));
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
