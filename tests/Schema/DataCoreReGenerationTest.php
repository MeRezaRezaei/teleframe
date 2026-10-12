<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use PHPUnit\Framework\TestCase;

/**
 * Data-core regeneration idempotence gate (plan §5): two consecutive
 * bin/regenerate runs into temp out-dirs must produce byte-identical
 * src/Schema/Generated trees INCLUDING the three declaration-driven dirs
 * (Validators/Requests/Events), and identical manifest hashes. Mirrors the
 * invocation pattern of RegenerationGoldenTest (proc_open over bin/regenerate)
 * but asserts ONLY run-vs-run equality of the fresh outputs — the committed
 * manifest pin moves in the gate wave and is out of scope here.
 */
final class DataCoreReGenerationTest extends TestCase
{
    private const PACKAGE_ROOT = __DIR__.'/../..';

    public function test_two_regeneration_runs_are_byte_identical_including_new_dirs(): void
    {
        $bin = self::PACKAGE_ROOT.'/bin/regenerate';
        self::assertFileExists($bin);

        $outA = sys_get_temp_dir().'/dc-regen-a-'.getmypid();
        $outB = sys_get_temp_dir().'/dc-regen-b-'.getmypid();

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

            // Manifest hash equality across the two runs.
            $manifestA = $outA.'/src/Schema/Generated/schema-manifest.json';
            $manifestB = $outB.'/src/Schema/Generated/schema-manifest.json';
            self::assertFileExists($manifestA);
            self::assertFileExists($manifestB);
            self::assertSame(
                hash_file('sha256', $manifestA),
                hash_file('sha256', $manifestB),
                'manifest hash differs between consecutive runs',
            );

            // Whole-tree byte equality: every file incl. Validators/Requests/Events.
            $filesA = self::relativeHashes($outA.'/src/Schema/Generated');
            $filesB = self::relativeHashes($outB.'/src/Schema/Generated');
            self::assertSame($filesA, $filesB, 'Generated trees differ between consecutive runs');

            // The three new dirs are represented in the run output's manifest
            // counts, and their presence is symmetric across the two runs.
            $counts = json_decode((string) file_get_contents($manifestA), true)['counts'];
            self::assertIsArray($counts);
            self::assertArrayHasKey('validators', $counts);
            self::assertArrayHasKey('requests', $counts);
            self::assertArrayHasKey('events', $counts);
            self::assertIsInt($counts['validators']);
            self::assertIsInt($counts['requests']);
            self::assertIsInt($counts['events']);
            foreach (['Validators', 'Requests', 'Events'] as $dir) {
                self::assertSame(
                    is_dir($outA.'/src/Schema/Generated/'.$dir),
                    is_dir($outB.'/src/Schema/Generated/'.$dir),
                    "presence parity mismatch for Generated/{$dir}",
                );
            }
        } finally {
            exec('rm -rf '.escapeshellarg($outA).' '.escapeshellarg($outB));
        }
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
