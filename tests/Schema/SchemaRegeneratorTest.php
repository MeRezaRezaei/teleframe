<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use PHPUnit\Framework\TestCase;
use MeRezaRezaei\Teleframe\Schema\Generator\SchemaRegenerator;
use MeRezaRezaei\Teleframe\Schema\Generator\TlRegenerateException;

final class SchemaRegeneratorTest extends TestCase
{
    private function tmpDir(string $tag): string
    {
        $dir = sys_get_temp_dir() . '/tlgen-' . $tag . '-' . uniqid();
        mkdir($dir, 0777, true);
        return $dir;
    }

    public function test_regenerate_writes_outputs_and_manifest(): void
    {
        $out = $this->tmpDir('out');
        $result = (new SchemaRegenerator())->regenerate(__DIR__ . '/fixtures', $out);

        self::assertFileExists($out . '/generated/schema-manifest.json');
        self::assertFileExists($out . '/generated/migrations');
        self::assertFileExists($out . '/generated/Models/TlUser.php');
        self::assertFileExists($out . '/generated/Data/Types/TlUserAbstractData.php');
        self::assertFileExists($out . '/generated/Factories/TlUserUserEmptyFactory.php');
        self::assertSame(6, $result['counts']['constructors'] + $result['counts']['methods'] - $result['counts']['methods'] - 0 - 0 - 0 - 0);
        self::assertGreaterThan(0, $result['counts']['tables']);
        $manifest = json_decode((string) file_get_contents($out . '/generated/schema-manifest.json'), true);
        self::assertSame($result['manifest']['hash'], $manifest['hash']);
    }

    public function test_deterministic(): void
    {
        $a = $this->tmpDir('a');
        $b = $this->tmpDir('b');
        (new SchemaRegenerator())->regenerate(__DIR__ . '/fixtures', $a);
        (new SchemaRegenerator())->regenerate(__DIR__ . '/fixtures', $b);
        self::assertSame(
            sha1_file($a . '/generated/schema-manifest.json'),
            sha1_file($b . '/generated/schema-manifest.json'),
        );
        $ita = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($a . '/generated', \FilesystemIterator::SKIP_DOTS));
        $count = 0;
        foreach ($ita as $fa) {
            if (!$fa->isFile()) {
                continue;
            }
            $rel = substr($fa->getPathname(), strlen($a . '/generated'));
            self::assertSame(sha1_file($fa->getPathname()), sha1_file($b . '/generated' . $rel), "differs: {$rel}");
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
        mkdir($out . '/generated/Models/deep', 0777, true);
        file_put_contents($out . '/generated/Models/deep/Stale.php', '<?php // stale');
        mkdir($out . '/migrations', 0777, true);
        file_put_contents($out . '/migrations/stale.php', '<?php // stale');

        (new SchemaRegenerator())
            ->shipNamespaces(['messages'])
            ->regenerate(__DIR__ . '/fixtures', $out);

        self::assertFileDoesNotExist($out . '/generated/Models/deep/Stale.php');
        self::assertFileDoesNotExist($out . '/generated/Models/deep');
        self::assertFileDoesNotExist($out . '/migrations/stale.php');
        self::assertFileExists($out . '/generated/Models/TlUser.php');
    }

    public function test_count_gate_blocks_and_force_bypasses(): void
    {
        $out = $this->tmpDir('gate');
        mkdir($out . '/generated', 0777, true);
        file_put_contents($out . '/generated/schema-manifest.json', json_encode([
            'counts' => ['constructors' => 10000],
        ]));

        $engine = new SchemaRegenerator();
        try {
            $engine->regenerate(__DIR__ . '/fixtures', $out);
            self::fail('expected TlRegenerateException');
        } catch (TlRegenerateException $e) {
            self::assertStringContainsString('30%', $e->getMessage());
        }

        $result = $engine->force(true)->regenerate(__DIR__ . '/fixtures', $out);
        self::assertGreaterThan(0, $result['counts']['constructors']);
    }
}
