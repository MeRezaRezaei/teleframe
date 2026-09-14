<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Laravel\Console\DaemonCommand;
use MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider;
use Orchestra\Testbench\TestCase as TestbenchTestCase;

/**
 * The daemon's default mirror-ingester seam — resolved from the container
 * and driven end to end: one decoded update through decompose → write →
 * classify → gated event against the migrated relational DB. This is the
 * verbatim's regular Redis observer ("observe → relational DB updated"),
 * runnable without any host wiring.
 */
final class MirrorIngesterSeamTest extends TestbenchTestCase
{
    protected function getApplicationBasePath()
    {
        return dirname(__DIR__, 2);
    }

    protected function getPackageProviders($app): array
    {
        return [TeleframeServiceProvider::class];
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

    private function migrateMirror(): string
    {
        $out = sys_get_temp_dir().'/tf5m_'.uniqid();
        @mkdir($out.'/migrations/mirror', 0777, true);

        $this->artisan('teleframe:mirror', ['--stage' => '0', '--out' => $out])->assertExitCode(0);
        $this->artisan('migrate', ['--path' => $out.'/migrations/mirror', '--realpath' => true])->assertExitCode(0);

        return $out;
    }

    public function test_default_seam_ingests_into_the_mirror(): void
    {
        $out = $this->migrateMirror();
        try {
            $ingester = $this->app->make(DaemonCommand::MIRROR_INGESTER_KEY);
            self::assertIsCallable($ingester, 'the default seam is a callable — no host wiring needed');

            $ingester([
                '_' => 'message',
                'id' => 5,
                'peer_id' => ['_type' => 2, '_id' => 900],
                'date' => 1726000000,
                'message' => 'daemon observer proof',
                'out' => false,
            ], 42);

            self::assertSame(1, DB::table('tf_messages')->where('account_id', 42)->where('id', 5)->count());
            self::assertSame(
                'daemon observer proof',
                DB::table('tf_messages')->where('account_id', 42)->where('id', 5)->value('message')
            );
        } finally {
            $this->rrmdir($out);
        }
    }

    public function test_default_seam_ignores_unknown_constructors(): void
    {
        $out = $this->migrateMirror();
        try {
            $ingester = $this->app->make(DaemonCommand::MIRROR_INGESTER_KEY);

            $ingester(['_' => 'noSuchConstructor', 'id' => 9], 42);

            self::assertSame(0, DB::table('tf_messages')->count(), 'unknown ctor → nothing stored, no throw');
        } finally {
            $this->rrmdir($out);
        }
    }

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
}
