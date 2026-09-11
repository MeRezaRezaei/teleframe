<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Nf5;

use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Nf5\Nf5Catalog;
use MeRezaRezaei\Teleframe\Schema\Nf5\Nf5MigrationWriter;
use MeRezaRezaei\Teleframe\Schema\Nf5\Nf5TableResolver;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;

final class Nf5MigrationWriterTest extends TestCase
{
    // T5c: 3-up from tests/Schema/Nf5/ = repo root
    private function stage0Parents(): array
    {
        $scheme  = TlParser::parseFile(__DIR__.'/../../../schema/sources/TL_telegram_v227.tl');
        $catalog = Nf5Catalog::load(__DIR__.'/../../../docs/superpowers/specs/2026-09-11-telegram-mirror-catalog.json', $scheme);
        $resolver = new Nf5TableResolver($catalog, $scheme);
        return $resolver->resolveAll(['tf_users', 'tf_messages', 'tf_messages_service', 'tf_message_medias', 'tf_message_entities']);
    }

    // T5a: recursive delete — unlink() does not work on directories
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

    public function test_writes_migration_files(): void
    {
        $outDir = sys_get_temp_dir().'/tf_n5test_'.uniqid('mig_');
        @mkdir($outDir, 0777, true);

        $writer = new Nf5MigrationWriter($outDir);
        $paths = $writer->writeAll($this->stage0Parents());

        self::assertNotEmpty($paths);
        foreach ($paths as $path) {
            self::assertFileExists($path);
            $src = file_get_contents($path);
            self::assertStringContainsString('GENERATED', $src);
            self::assertStringContainsString('account_id', $src);
        }

        // At least one FK file exists
        $fkFile = glob($outDir.'/*_create_tf_foreign_keys.php');
        self::assertNotEmpty($fkFile);

        $this->rrmdir($outDir); // T5a
    }

    public function test_boolean_columns_get_default_false(): void
    {
        $outDir = sys_get_temp_dir().'/tf_n5test_'.uniqid('mig_');
        @mkdir($outDir, 0777, true);
        $paths = (new Nf5MigrationWriter($outDir))->writeAll($this->stage0Parents());
        $userFile = reset($paths);
        $src = file_get_contents($userFile);
        self::assertStringContainsString("->default(false)", $src);
        $this->rrmdir($outDir); // T5a
    }
}
