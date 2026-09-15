<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use PHPUnit\Framework\TestCase;

/**
 * Golden gate: the curated migration dial. The hand-authored tf_*
 * migrations live in src/Laravel/Migrations and are NEVER auto-generated
 * — bin/regenerate no longer produces migrations (MigrationGenerator
 * disabled per owner verbatim #3: "i dont want the auto generation any
 * more"). App-owned migrations (user_bindings, telegram_apps, etc.)
 * have no generated source either.
 *
 * Post-purge reality (2026-09-15):
 * - 9 curated hand-authored tf_* migrations (messages/media/updates)
 * - 5 app-owned migrations (user_bindings, telegram_apps, ...)
 * - 14 total in src/Laravel/Migrations
 * - bin/regenerate --ship is a no-op (no generated migrations to copy)
 */
final class ShipDialGoldenTest extends TestCase
{
    private const PACKAGE_ROOT = __DIR__.'/../..';

    private const SHIP_DIR = self::PACKAGE_ROOT.'/src/Laravel/Migrations';

    /**
     * App-owned migrations under src/Laravel/Migrations/ that are hand-authored
     * and have NO generated source. Always present, never overwritten by
     * bin/regenerate.
     */
    private const APP_OWNED_MIGRATIONS = [
        '2026_09_08_000100_create_tl_user_bindings_table.php',
        '2026_09_09_000200_create_telegram_apps_table.php',
        '2026_09_09_000201_create_telegram_accounts_table.php',
        '2026_09_14_000000_add_user_id_to_telegram_accounts_table.php',
        '2026_09_14_000001_create_tg_update_routing_table.php',
    ];

    public function test_shipped_subset_count(): void
    {
        $files = self::migrationFiles(self::SHIP_DIR);
        self::assertGreaterThanOrEqual(12, $files, 'curated dial must have at least 12 migrations (9 curated + 5 app-owned)');
        self::assertLessThanOrEqual(20, $files, 'curated dial must stay under 20 migrations total');
    }

    public function test_shipped_subset_contains_curated_messages_domain(): void
    {
        $names = self::migrationNames(self::SHIP_DIR);
        foreach (['create_tf_messages_tables'] as $stem) {
            self::assertContains(true, array_map(
                static fn (string $n): bool => str_contains($n, $stem),
                $names,
            ), "no shipped migration matches {$stem}");
        }
    }

    public function test_root_namespace_and_special_files_do_not_ship(): void
    {
        $names = self::migrationNames(self::SHIP_DIR);
        self::assertSame([], array_filter($names, static fn (string $n): bool => str_contains($n, 'create_tl_route_tables') || str_contains($n, 'add_tl_foreign_keys')));
    }

    public function test_app_owned_migrations_present(): void
    {
        foreach (self::APP_OWNED_MIGRATIONS as $name) {
            self::assertFileExists(self::SHIP_DIR.'/'.$name, $name.' missing');
        }
    }

    public function test_curated_migrations_are_hand_authored(): void
    {
        foreach (self::APP_OWNED_MIGRATIONS as $skip) {
            $file = self::SHIP_DIR.'/'.$skip;
            if (is_file($file)) {
                self::assertStringNotContainsString(
                    '@generated',
                    (string) file_get_contents($file),
                    "app-owned migration {$skip} must not carry @generated marker",
                );
            }
        }
        $curated = glob(self::SHIP_DIR.'/2026_09_14_2000*.php') ?: [];
        self::assertNotEmpty($curated, 'no curated hand-authored migrations found');
        foreach ($curated as $path) {
            self::assertStringNotContainsString(
                '@generated',
                (string) file_get_contents($path),
                'curated migration must not carry @generated marker: '.basename($path),
            );
        }
    }

    public function test_provider_registers_publishable_migration_path(): void
    {
        $source = (string) file_get_contents(self::PACKAGE_ROOT.'/src/Laravel/Providers/TeleframeServiceProvider.php');

        // Pint (format-on-save) alternates `dirname(__DIR__) . '/Migrations'`
        // and `dirname(__DIR__).'/Migrations'`; the contract is that the
        // provider loads THIS package's Migrations dir, so compare with the
        // concat spacing normalized instead of pinning one exact variant.
        $normalized = preg_replace('/\s*\.\s*/', '.', $source);

        self::assertStringContainsString("loadMigrationsFrom(dirname(__DIR__).'/Migrations')", (string) $normalized);
    }

    /** @return int count of *.php migration files in dir */
    private static function migrationFiles(string $dir): int
    {
        return count(glob(rtrim($dir, '/').'/*.php') ?: []);
    }

    /** @return list<string> basenames of *.php migration files in dir */
    private static function migrationNames(string $dir): array
    {
        return array_map('basename', glob(rtrim($dir, '/').'/*.php') ?: []);
    }
}
