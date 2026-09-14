<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Mirror;

use PHPUnit\Framework\TestCase;

/**
 * Telegram-reliance invariants (owner verbatim 2026-09-14, "NF5 mirror + ingest truth model").
 *
 * "one of the most obvoius among them is auto increment since telegram data base is
 *  already doing it and first you should spot these things and making sure you make
 *  a mirror about them"
 *
 * ID assignment is Telegram's job: the mirror stores Telegram-supplied identity values
 * and must NEVER auto-increment. Every mirror table is keyed by (account_id, telegram
 * key[, position]) — the composite PK — and the shared Eloquent base is non-incrementing.
 * Any observed auto-increment in the generated surface means we stopped mirroring and
 * started assigning: that is a wrong ingest path by construction.
 */
final class TelegramRelianceInvariantTest extends TestCase
{
    private const MIRROR_MIGRATIONS = __DIR__.'/../../../src/Schema/Generated/migrations/mirror';

    private const MIRROR_MODEL = __DIR__.'/../../../src/Schema/Eloquent/TfMirrorModel.php';

    public function test_no_mirror_migration_auto_increments(): void
    {
        $files = glob(self::MIRROR_MIGRATIONS.'/*.php');
        self::assertNotEmpty($files, 'mirror migrations must exist');

        foreach ($files as $file) {
            $source = (string) file_get_contents($file);
            foreach (['autoIncrement', 'increments', 'bigIncrements', 'smallIncrements', 'mediumIncrements', 'tinyIncrements'] as $needle) {
                self::assertStringNotContainsString($needle, $source, "{$file} must not use {$needle}() — ID assignment is Telegram's job");
            }
        }
    }

    public function test_every_mirror_table_has_account_first_composite_primary_key(): void
    {
        $files = glob(self::MIRROR_MIGRATIONS.'/*.php');
        self::assertNotEmpty($files);

        $tables = 0;
        $primaries = 0;
        foreach ($files as $file) {
            $source = (string) file_get_contents($file);
            // Every schema builder table definition must start with account_id.
            preg_match_all('/Schema::create\(\s*[\'"]([^\'"]+)[\'"]/', $source, $createMatches);
            foreach ($createMatches[1] as $table) {
                $tables++;
                self::assertMatchesRegularExpression(
                    "/Schema::create\(\s*['\"]{$table}['\"][^;]*?->bigInteger\('account_id'\)/s",
                    $source,
                    "{$table} must declare account_id as its first column (NF5 I1)"
                );
            }
            preg_match_all("/->primary\(\[[^\]]+\]\)/", $source, $pkMatches);
            $primaries += count($pkMatches[0]);
        }

        self::assertGreaterThan(380, $tables, 'expected the full mirror table surface');
        self::assertSame($tables, $primaries, 'every mirror table must declare a composite primary key');
    }

    public function test_every_mirror_primary_key_leads_with_account_id_and_telegram_column(): void
    {
        $files = glob(self::MIRROR_MIGRATIONS.'/*.php');
        self::assertNotEmpty($files);

        foreach ($files as $file) {
            $source = (string) file_get_contents($file);
            preg_match_all('/->primary\(\[([^\]]+)\]\)/', $source, $pkMatches);
            foreach ($pkMatches[1] as $pk) {
                $cols = array_values(array_filter(array_map('trim', explode(',', $pk)), fn (string $c) => $c !== ''));
                // Composite key: account_id first, then 1..3 Telegram-supplied
                // identity columns, then (only when the table is positioned) 'position'.
                self::assertContains(count($cols), [2, 3, 4], "{$file}: PK must be (account_id, telegram_key[, position]) — got [{$pk}]");
                self::assertStringContainsString("'account_id'", $cols[0], "{$file}: PK must lead with account_id");
                if (in_array("'position'", $cols, true)) {
                    self::assertStringContainsString("'position'", end($cols), "{$file}: 'position' may only be the trailing PK column — got [{$pk}]");
                }
            }
        }
    }

    public function test_shared_eloquent_base_is_non_incrementing(): void
    {
        $source = (string) file_get_contents(self::MIRROR_MODEL);
        self::assertStringContainsString('public $incrementing = false;', $source);
        self::assertStringNotContainsString('public $incrementing = true;', $source);
        self::assertStringContainsString("protected \$keyType = 'int';", $source);
    }

    public function test_no_id_shadowing_mirror_model_uses_telegram_supplied_columns_only(): void
    {
        // The mirror must not invent surrogate identity: model tables key off the
        // composite PK built from account_id + Telegram columns (TfMirrorModel
        // exposes 'id' as the primaryKey name only; incrementing stays false and
        // no sequence/keygen is attached anywhere in the generated surface).
        $source = (string) file_get_contents(self::MIRROR_MODEL);
        self::assertStringNotContainsString('serial', $source);
        self::assertStringNotContainsString('sequence', $source);
        self::assertStringNotContainsString('autoincrement', $source);
    }
}
