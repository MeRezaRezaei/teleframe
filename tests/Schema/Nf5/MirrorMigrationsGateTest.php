<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Nf5;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as TestbenchTestCase;

final class MirrorMigrationsGateTest extends TestbenchTestCase
{
    // T8c: rrmdir helper — must clean up temp dirs after every test
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

    // T8b: base_path() must resolve to the repo root so the command can find
    // the TL scheme + catalog. Override getApplicationBasePath() (Testbench
    // otherwise points base_path() at vendor/orchestra/testbench-core/laravel).
    protected function getApplicationBasePath()
    {
        return dirname(__DIR__, 3);
    }

    protected function getPackageProviders($app): array
    {
        return [\MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
    }

    public function test_stage0_migrations_run_and_schema_is_nf5_clean(): void
    {
        $out = sys_get_temp_dir().'/tf5gate_'.uniqid();
        @mkdir($out.'/migrations/mirror', 0777, true);

        try {
            $this->artisan('teleframe:mirror', ['--stage' => '0', '--out' => $out])->assertExitCode(0);

            // Run every generated migration against the in-memory DB
            $migrationFiles = glob($out.'/migrations/mirror/*.php');
            self::assertNotEmpty($migrationFiles);

            // T8a: pass the absolute path directly to --path with --realpath
            $this->artisan('migrate', ['--path' => $out.'/migrations/mirror', '--realpath' => true])->assertExitCode(0);

            // getAllTables() is not available on SQLite; query sqlite_master directly.
            $rows = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            $tableNames = array_values(array_map(fn ($r) => $r->name, $rows));
            $tableNames = array_values(array_diff($tableNames, ['migrations']));
            self::assertNotEmpty($tableNames);

            foreach ($tableNames as $tableName) {
                foreach (Schema::getColumns($tableName) as $col) {
                    $type = strtolower($col['type'] ?? '');
                    self::assertStringNotContainsString('json', $type, "{$tableName}.{$col['name']} must not be json");
                    self::assertStringNotContainsString('blob', $type, "{$tableName}.{$col['name']} must not be blob");
                    self::assertStringNotContainsString('binary', $type, "{$tableName}.{$col['name']} must not be binary");
                    // Zero nullable: every column must be NOT NULL
                    self::assertFalse((bool) ($col['nullable'] ?? false), "{$tableName}.{$col['name']} must be NOT NULL");
                }
            }
        } finally {
            // T8c: always clean up
            $this->rrmdir($out);
        }
    }
}
