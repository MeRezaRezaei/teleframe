<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorModelWriter;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorTableResolver;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;

final class MirrorModelWriterTest extends TestCase
{
    // T6c: 3-up from tests/Schema/Mirror/ = repo root
    private function stage0Tables(): array
    {
        $scheme  = TlParser::parseFile(__DIR__.'/../../../schema/sources/TL_telegram_v227.tl');
        $catalog = MirrorCatalog::load(__DIR__.'/../../../docs/superpowers/specs/2026-09-11-telegram-mirror-catalog.json', $scheme);
        $resolver = new MirrorTableResolver($catalog, $scheme);
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
        $paths = (new MirrorModelWriter($outDir))->writeAll($this->stage0Tables());
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
        (new MirrorModelWriter($outDir))->writeAll($this->stage0Tables());
        $userSrc = (string) file_get_contents($outDir.'/Models/Mirror/TfUser.php');
        self::assertStringContainsString('MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror', $userSrc);
        self::assertStringContainsString("protected \$table = 'tf_users';", $userSrc);
        self::assertStringContainsString('use AccountScoped;', $userSrc);
        $this->rrmdir($outDir); // T6b
    }
}
