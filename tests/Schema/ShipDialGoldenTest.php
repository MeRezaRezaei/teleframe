<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use PHPUnit\Framework\TestCase;

/**
 * Task 4 golden gate: the curated migration dial. `php bin/regenerate --ship`
 * copies ONLY the per-type migration files whose TL namespace is on the dial
 * (config teleframe.ship_namespaces, default auth/messages/users/
 * channels/updates/help/contacts) from the full generated/ mirror into
 * migrations/ at the package root — byte-identical copies (global sequence
 * numbers preserved), never re-derived — for the provider's
 * loadMigrationsFrom publish surface.
 *
 * Verified against the real v227 generated set: the dial selects 113 files;
 * payments/phone/stickers namespaces genuinely exist in generated/ and stay
 * unshipped; `create_tl_users_*` satisfies the create_tl_user* pin.
 */
final class ShipDialGoldenTest extends TestCase
{
    private const PACKAGE_ROOT = __DIR__ . '/../..';
    private const SHIP_DIR = self::PACKAGE_ROOT . '/migrations';
    private const GENERATED_DIR = self::PACKAGE_ROOT . '/generated/migrations';

    public function test_shipped_subset_count_under_two_hundred(): void
    {
        $files = self::migrationFiles(self::SHIP_DIR);
        self::assertGreaterThan(80, $files, 'curated dial unexpectedly small');
        self::assertLessThan(200, $files, 'curated dial must stay under 200 migrations');
    }

    public function test_shipped_subset_contains_core_namespace_migrations(): void
    {
        $names = self::migrationNames(self::SHIP_DIR);
        self::assertContains('2026_08_28_000632_create_tl_users_user_full_table.php', $names);
        foreach (['create_tl_users_', 'create_tl_auth_', 'create_tl_messages_'] as $prefix) {
            self::assertContains(true, array_map(
                static fn (string $n): bool => str_starts_with($n, '2026_08_28_') && str_contains($n, $prefix),
                $names,
            ), "no shipped migration matches {$prefix}*");
        }
    }

    public function test_off_dial_namespaces_stay_in_generated_only(): void
    {
        $shipped = self::migrationNames(self::SHIP_DIR);
        // Real generated-set namespace filenames (v227): payments.*, phone.*,
        // stickers.* — none of these may ship under the default dial. Prefix-
        // anchored at the namespace position, so e.g. messages.*Stickers*
        // types (on-dial, different namespace segment) do not false-positive.
        $offDial = ['create_tl_payments_', 'create_tl_phone_', 'create_tl_stickers_'];
        foreach ($offDial as $prefix) {
            self::assertContains(true, array_map(
                static fn (string $n): bool => str_contains($n, $prefix),
                self::migrationNames(self::GENERATED_DIR),
            ), "sanity: {$prefix}* must exist in the full generated set"
                . ' (otherwise this assertion pins nothing)');
            foreach ($shipped as $name) {
                self::assertStringNotContainsString($prefix, $name, "off-dial migration shipped: {$name}");
            }
        }
    }

    public function test_root_namespace_and_special_files_do_not_ship(): void
    {
        $names = self::migrationNames(self::SHIP_DIR);
        self::assertNotContains('2026_08_28_000403_create_tl_user_table.php', $names); // root User
        self::assertNotContains('2026_08_28_000401_create_tl_updates_table.php', $names); // root Updates
        self::assertSame([], array_filter($names, static fn (string $n): bool =>
            str_contains($n, 'create_tl_route_tables') || str_contains($n, 'add_tl_foreign_keys')));
    }

    public function test_shipped_files_are_byte_identical_copies_of_generated(): void
    {
        foreach (glob(self::SHIP_DIR . '/*.php') ?: [] as $shipped) {
            $generated = self::GENERATED_DIR . '/' . basename($shipped);
            self::assertFileExists($generated, basename($shipped) . ' has no generated/ counterpart');
            self::assertSame(
                hash_file('sha256', $generated),
                hash_file('sha256', $shipped),
                basename($shipped) . ' drifted from its generated/ source',
            );
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
            $committed = self::migrationNames(self::SHIP_DIR);
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
