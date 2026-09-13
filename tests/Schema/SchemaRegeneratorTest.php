<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use PHPUnit\Framework\TestCase;
use MeRezaRezaei\Teleframe\Schema\Generator\SchemaRegenerator;
use MeRezaRezaei\Teleframe\Schema\Generator\TlRegenerateException;

final class SchemaRegeneratorTest extends TestCase
{
    private const PACKAGE_ROOT = __DIR__ . '/../..';

    /** Full committed .tl set — the only scheme that can host the mirror
     *  manifest (the SQL-extraction emitter validates every promoted column
     *  against classified ctors and rejects mini schemes as drift). */
    private const SOURCES = self::PACKAGE_ROOT . '/schema/sources';

    private function tmpDir(string $tag): string
    {
        $dir = sys_get_temp_dir() . '/tlgen-' . $tag . '-' . uniqid();
        mkdir($dir, 0777, true);
        return $dir;
    }

    public function test_regenerate_writes_outputs_and_manifest(): void
    {
        $out = $this->tmpDir('out');
        $result = (new SchemaRegenerator())->regenerate(self::SOURCES, $out);

        self::assertFileExists($out . '/src/Schema/Generated/schema-manifest.json');
        self::assertFileExists($out . '/src/Schema/Generated/migrations');
        self::assertFileExists($out . '/src/Schema/Generated/Models/TlUser.php');
        self::assertFileExists($out . '/src/Schema/Generated/Data/Types/TlUserAbstractData.php');
        self::assertFileExists($out . '/src/Schema/Generated/Factories/TlUserFactory.php');
        self::assertGreaterThan(1000, $result['counts']['constructors']);
        self::assertGreaterThan(0, $result['counts']['tables']);
        $manifest = json_decode((string) file_get_contents($out . '/src/Schema/Generated/schema-manifest.json'), true);
        self::assertSame($result['manifest']['hash'], $manifest['hash']);
    }

    public function test_deterministic(): void
    {
        $a = $this->tmpDir('a');
        $b = $this->tmpDir('b');
        (new SchemaRegenerator())->regenerate(self::SOURCES, $a);
        (new SchemaRegenerator())->regenerate(self::SOURCES, $b);
        self::assertSame(
            sha1_file($a . '/src/Schema/Generated/schema-manifest.json'),
            sha1_file($b . '/src/Schema/Generated/schema-manifest.json'),
        );
        $ita = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($a . '/src/Schema/Generated', \FilesystemIterator::SKIP_DOTS));
        $count = 0;
        foreach ($ita as $fa) {
            if (!$fa->isFile()) {
                continue;
            }
            $rel = substr($fa->getPathname(), strlen($a . '/src/Schema/Generated'));
            self::assertSame(sha1_file($fa->getPathname()), sha1_file($b . '/src/Schema/Generated' . $rel), "differs: {$rel}");
            $count++;
        }
        self::assertGreaterThan(10, $count);
    }

    public function test_regenerate_wipes_stale_outputs(): void
    {
        // Night W4: the wipe path is a shell-free recursive delete; stale
        // files in nested generated trees (and shipped migrations/) must
        // not survive a regeneration.
        $out = $this->tmpDir('wipe');
        mkdir($out . '/src/Schema/Generated/Models/deep', 0777, true);
        file_put_contents($out . '/src/Schema/Generated/Models/deep/Stale.php', '<?php // stale');
        mkdir($out . '/migrations', 0777, true);
        file_put_contents($out . '/migrations/stale.php', '<?php // stale');

        (new SchemaRegenerator())
            ->shipNamespaces(['messages'])
            ->regenerate(self::SOURCES, $out);

        self::assertFileDoesNotExist($out . '/src/Schema/Generated/Models/deep/Stale.php');
        self::assertFileDoesNotExist($out . '/src/Schema/Generated/Models/deep');
        self::assertFileDoesNotExist($out . '/migrations/stale.php');
        self::assertFileExists($out . '/src/Schema/Generated/Models/TlUser.php');
    }

    public function test_count_gate_blocks_and_force_bypasses(): void
    {
        $out = $this->tmpDir('gate');
        mkdir($out . '/src/Schema/Generated', 0777, true);
        file_put_contents($out . '/src/Schema/Generated/schema-manifest.json', json_encode([
            'counts' => ['constructors' => 10000],
        ]));

        $engine = new SchemaRegenerator();
        try {
            $engine->regenerate(self::SOURCES, $out);
            self::fail('expected TlRegenerateException');
        } catch (TlRegenerateException $e) {
            self::assertStringContainsString('30%', $e->getMessage());
        }

        $result = $engine->force(true)->regenerate(self::SOURCES, $out);
        self::assertGreaterThan(0, $result['counts']['constructors']);
    }
}
