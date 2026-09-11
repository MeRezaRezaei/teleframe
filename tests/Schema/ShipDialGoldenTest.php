<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use PHPUnit\Framework\TestCase;

/**
 * Task 4 golden gate: the curated migration dial. `php bin/regenerate --ship`
 * copies the tf_* per-type migration files from the full generated/ mirror
 * into migrations/ at the package root — byte-identical copies (global
 * sequence numbers preserved), never re-derived — for the provider's
 * loadMigrationsFrom publish surface.
 *
 * Verified against the real v227 generated set: 13 tf_* mirror migrations are
 * shipped; app-owned migrations (user_bindings, telegram_apps, telegram_accounts)
 * live alongside but have no generated/ source.
 */
final class ShipDialGoldenTest extends TestCase
{
    private const PACKAGE_ROOT = __DIR__ . '/../..';
    private const SHIP_DIR = self::PACKAGE_ROOT . '/migrations';
    private const GENERATED_DIR = self::PACKAGE_ROOT . '/generated/migrations';

    /**
     * App-owned migrations under migrations/ that are hand-authored (Phase 5b
     * identity bindings) and therefore have NO generated/ source and are NOT
     * reproduced by `bin/regenerate --ship` (the dial only copies TL per-type
     * migrations). Exempted from the two regenerate-reproduction golden
     * checks; the byte-identical check still verifies they exist.
     */
    private const APP_OWNED_MIGRATIONS = [
        '2026_09_08_000100_create_tl_user_bindings_table.php',
        '2026_09_09_000200_create_telegram_apps_table.php',
        '2026_09_09_000201_create_telegram_accounts_table.php',
    ];

    public function test_shipped_subset_count(): void
    {
        $files = self::migrationFiles(self::SHIP_DIR);
        self::assertGreaterThanOrEqual(13, $files, 'curated dial must have at least 13 tf_* migrations');
        self::assertLessThanOrEqual(20, $files, 'curated dial must stay under 20 migrations total');
    }

    public function test_shipped_subset_contains_core_tf_migrations(): void
    {
        $names = self::migrationNames(self::SHIP_DIR);
        foreach (['create_tf_users_table', 'create_tf_chats_table', 'create_tf_messages_table'] as $stem) {
            self::assertContains(true, array_map(
                static fn (string $n): bool => str_contains($n, $stem),
                $names,
            ), "no shipped migration matches {$stem}");
        }
    }

    public function test_all_generated_tf_migrations_are_shipped(): void
    {
        $shipped = self::migrationNames(self::SHIP_DIR);
        $generated = self::migrationNames(self::GENERATED_DIR);
        // Every generated tf_* file must appear in the shipped set
        foreach ($generated as $genName) {
            self::assertContains($genName, $shipped, "generated migration {$genName} not shipped");
        }
    }

    public function test_root_namespace_and_special_files_do_not_ship(): void
    {
        $names = self::migrationNames(self::SHIP_DIR);
        self::assertSame([], array_filter($names, static fn (string $n): bool =>
            str_contains($n, 'create_tl_route_tables') || str_contains($n, 'add_tl_foreign_keys')));
    }

    public function test_shipped_files_are_byte_identical_copies_of_generated(): void
    {
        foreach (glob(self::SHIP_DIR . '/*.php') ?: [] as $shipped) {
            $name = basename($shipped);
            if (in_array($name, self::APP_OWNED_MIGRATIONS, true)) {
                continue; // app-owned (Phase 5b): hand-authored, no generated source
            }
            $generated = self::GENERATED_DIR . '/' . $name;
            self::assertFileExists($generated, $name . ' has no generated/ counterpart');
            self::assertSame(
                hash_file('sha256', $generated),
                hash_file('sha256', $shipped),
                $name . ' drifted from its generated/ source',
            );
        }

        foreach (self::APP_OWNED_MIGRATIONS as $name) {
            self::assertFileExists(self::SHIP_DIR . '/' . $name, $name . ' missing');
        }
    }

    public function test_ship_run_to_temp_dir_reproduces_committed_subset(): void
    {
        $bin = self::PACKAGE_ROOT . '/bin/regenerate';
        $out = sys_get_temp_dir() . '/tl-ship-golden-' . getmypid();
        $cmd = sprintf('%s %s --out=%s --ship 2>&1', escapeshellarg(PHP_BINARY), escapeshellarg($bin), escapeshellarg($out));
        $proc = proc_open($cmd, [1 => ['pipe', 'w']], $pipes);
        self::assertIsResource($proc);
        fclose($pipes[1]);
        self::assertSame(0, proc_close($proc), 'bin/regenerate --ship failed');

        try {
            $fresh = self::migrationNames($out . '/migrations');
            $committed = array_values(array_diff(self::migrationNames(self::SHIP_DIR), self::APP_OWNED_MIGRATIONS));
            sort($fresh);
            sort($committed);
            self::assertSame($committed, $fresh, 'fresh --ship run does not reproduce the committed subset');
        } finally {
            exec('rm -rf ' . escapeshellarg($out));
        }
    }

    public function test_provider_registers_publishable_migration_path(): void
    {
        $source = (string) file_get_contents(self::PACKAGE_ROOT . '/src/Laravel/Providers/TeleframeServiceProvider.php');
        self::assertStringContainsString("loadMigrationsFrom(dirname(__DIR__, 3) . '/migrations')", $source);
    }

    /** @return int count of *.php migration files in dir */
    private static function migrationFiles(string $dir): int
    {
        return count(glob(rtrim($dir, '/') . '/*.php') ?: []);
    }

    /** @return list<string> basenames of *.php migration files in dir */
    private static function migrationNames(string $dir): array
    {
        return array_map('basename', glob(rtrim($dir, '/') . '/*.php') ?: []);
    }
}
