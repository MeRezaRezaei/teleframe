<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactoryWriter;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorModelWriter;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorTableResolver;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class MirrorFactoryWriterTest extends TestCase
{
    public function test_writes_factories_for_each_table(): void
    {
        $scheme  = TlParser::parseFile(__DIR__.'/../../../schema/sources/TL_telegram_v227.tl');
        $catalog = MirrorCatalog::load(__DIR__.'/../../../docs/superpowers/specs/2026-09-11-telegram-mirror-catalog.json', $scheme);
        $resolver = new MirrorTableResolver($catalog, $scheme);
        $parents = $resolver->resolveAll(['tf_users', 'tf_messages']);
        $outDir  = sys_get_temp_dir().'/tf5fact_'.uniqid();
        try {
            @mkdir($outDir.'/Models/Mirror', 0777, true);
            (new MirrorModelWriter($outDir))->writeAll($parents);
            $paths = (new MirrorFactoryWriter($outDir))->writeAll($parents);
            self::assertNotEmpty($paths);
            self::assertFileExists($outDir.'/Factories/Mirror/TfUserFactory.php');
            $src = (string) file_get_contents($outDir.'/Factories/Mirror/TfUserFactory.php');
            self::assertStringContainsString('GENERATED', $src);
        } finally {
            $this->rrmdir($outDir);
        }
    }

    // T6f: factories must mirror models — the full catalog subtree, one
    // factory per distinct table, deduped across shared catalog targets.
    public function test_full_catalog_factories_match_models_and_are_deduped(): void
    {
        $scheme  = TlParser::parseFile(__DIR__.'/../../../schema/sources/TL_telegram_v227.tl');
        $catalog = MirrorCatalog::load(__DIR__.'/../../../docs/superpowers/specs/2026-09-11-telegram-mirror-catalog.json', $scheme);
        $resolver = new MirrorTableResolver($catalog, $scheme);
        $parents = $resolver->resolveAll($catalog->tableNames());
        $outDir  = sys_get_temp_dir().'/tf5fact_'.uniqid();
        try {
            $modelPaths = (new MirrorModelWriter($outDir))->writeAll($parents);
            $factoryPaths = (new MirrorFactoryWriter($outDir))->writeAll($parents);
            self::assertNotEmpty($modelPaths);
            self::assertSame(count($modelPaths), count($factoryPaths));
            // One file per distinct class name on disk — never two tables clobbering one file.
            self::assertSame(count($modelPaths), count(array_unique($modelPaths, SORT_REGULAR)));
            self::assertSame(count($factoryPaths), count(array_unique($factoryPaths, SORT_REGULAR)));
            foreach ($factoryPaths as $path) {
                self::assertFileExists($path);
            }
        } finally {
            $this->rrmdir($outDir);
        }
    }

    private function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($it as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($dir);
    }
}
