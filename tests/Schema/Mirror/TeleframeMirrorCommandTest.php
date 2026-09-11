<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Mirror;

use Orchestra\Testbench\TestCase as TestbenchTestCase;

final class TeleframeMirrorCommandTest extends TestbenchTestCase
{
    // T7a: recursive delete — unlink() does not work on directories
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

    // T7b: base_path() must resolve to the repo root so the command can find
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

    public function test_stage0_writes_migrations_and_models(): void
    {
        $out = sys_get_temp_dir().'/tf5cmd_'.uniqid();
        @mkdir($out, 0777, true);

        $this->artisan('teleframe:mirror', ['--stage' => '0', '--out' => $out])
            ->expectsOutputToContain('tf_users')
            ->assertExitCode(0);

        self::assertFileExists($out.'/Models/Mirror/TfUser.php');
        self::assertDirectoryExists($out.'/migrations/mirror');
        $this->rrmdir($out); // T7a
    }
}
