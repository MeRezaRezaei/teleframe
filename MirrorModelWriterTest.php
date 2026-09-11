<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Nf5;

use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Nf5\Nf5Catalog;
use MeRezaRezaei\Teleframe\Schema\Nf5\Nf5ModelWriter;
use MeRezaRezaei\Teleframe\Schema\Nf5\Nf5TableResolver;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;

final class Nf5ModelWriterTest extends TestCase
{
    // T6c: 3-up from tests/Schema/Nf5/ = repo root
    private function stage0Tables(): array
    {
        $scheme  = TlParser::parseFile(__DIR__.'/../../../schema/sources/TL_telegram_v227.tl');
        $catalog = Nf5Catalog::load(__DIR__.'/../../../docs/superpowers/specs/2026-09-11-telegram-mirror-catalog.json', $scheme);
        $resolver = new Nf5TableResolver($catalog, $scheme);
        return $resolver->resolveAll(['tf_users', 'tf_messages', 'tf_messages_service']);
    }

    // T6b: recursive delete helper (unlink doesn't work on directories)
    private function rrmdir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() && ! $item->isLink() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($dir);
    }

    public function test_writes_parent_and_child_models(): void
    {
        $outDir = sys_get_temp_dir().'/tf_n5models_'.uniqid();
        @mkdir($outDir, 0777, true);
        $paths = (new Nf5ModelWriter($outDir))->writeAll($this->stage0Tables());
        self::assertNotEmpty($paths);
        foreach ($paths as $p) {
            self::assertFileExists($p);
            self::assertStringContainsString('GENERATED', (string) file_get_contents($p));
        }
        $this->rrmdir($outDir); // T6b
    }

    public function test_model_namespace_and_table_are_correct(): void
    {
        $outDir = sys_get_temp_dir().'/tf_n5models_'.uniqid();
        @mkdir($outDir, 0777, true);
        (new Nf5ModelWriter($outDir))->writeAll($this->stage0Tables());
        $userSrc = (string) file_get_contents($outDir.'/Models/Mirror/TfUser.php');
        self::assertStringContainsString('MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror', $userSrc);
        self::assertStringContainsString("protected \$table = 'tf_users';", $userSrc);
        self::assertStringContainsString('use AccountScoped;', $userSrc);
        $this->rrmdir($outDir); // T6b
    }
}
