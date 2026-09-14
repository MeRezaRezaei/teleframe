<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Ingest\ChunkFetcher;
use MeRezaRezaei\Teleframe\Ingest\PtsWatermark;
use MeRezaRezaei\Teleframe\Laravel\Console\IngestGateCommand;
use MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayRedis;
use Orchestra\Testbench\TestCase as TestbenchTestCase;

/**
 * teleframe:ingest:gate — the session-register schema-integrity gate,
 * end to end: fixture fetcher (via the FETCHER_KEY seam) → migrated
 * relational DB → report + watermark persistence.
 *
 * @property ?ArrayRedis $redis
 */
final class IngestGateCommandTest extends TestbenchTestCase
{
    private ?ArrayRedis $redis = null;

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
        $out = sys_get_temp_dir().'/tf5g_'.uniqid();
        @mkdir($out.'/migrations/mirror', 0777, true);

        $this->artisan('teleframe:mirror', ['--stage' => '0', '--out' => $out])->assertExitCode(0);
        $this->artisan('migrate', ['--path' => $out.'/migrations/mirror', '--realpath' => true])->assertExitCode(0);

        return $out;
    }

    private function fixtureFetcher(): ChunkFetcher
    {
        return new class implements ChunkFetcher
        {
            public function getState(): array
            {
                return ['pts' => 11, 'date' => 1726000000, 'qts' => 2, 'seq' => 5];
            }

            public function getDifference(array $state, ?array $priorState = null): array
            {
                return [
                    '_' => 'updates.difference',
                    'new_messages' => [
                        [
                            '_' => 'message',
                            'id' => 7,
                            'peer_id' => ['_type' => 2, '_id' => 900],
                            'date' => 1726000000,
                            'message' => 'gate proof',
                        ],
                    ],
                    'users' => [
                        ['_' => 'user', 'id' => 101, 'verified' => true],
                    ],
                    'chats' => [
                        ['_' => 'chat', 'id' => 900, 'title' => 'gate lab', 'participants_count' => 3, 'date' => 1726000000, 'version' => 1],
                    ],
                    'state' => [
                        '_' => 'updates.state',
                        'pts' => 11, 'qts' => 2, 'date' => 1726000000, 'seq' => 5,
                    ],
                ];
            }
        };
    }

    public function test_gate_ingests_chunk_and_succeeds(): void
    {
        $out = $this->migrateMirror();
        try {
            $this->app->bind(IngestGateCommand::FETCHER_KEY, fn (): callable => fn (int $accountId): ChunkFetcher => $this->fixtureFetcher());
            $this->app->singleton(PtsWatermark::class, fn () => new PtsWatermark(new ArrayRedis));

            $this->artisan('teleframe:ingest:gate', ['account' => '42'])
                ->assertExitCode(0)
                ->expectsOutputToContain('Integrity OK')
                ->expectsOutputToContain('inserted');

            self::assertSame(1, DB::table('tf_messages')->where('account_id', 42)->where('id', 7)->count());
            self::assertSame('gate lab', DB::table('tf_chats')->where('account_id', 42)->where('id', 900)->value('title'));
        } finally {
            $this->rrmdir($out);
        }
    }

    public function test_gate_only_flag_does_not_persist_watermark(): void
    {
        $out = $this->migrateMirror();
        try {
            $this->app->bind(IngestGateCommand::FETCHER_KEY, fn (): callable => fn (int $accountId): ChunkFetcher => $this->fixtureFetcher());
            $this->app->singleton(PtsWatermark::class, function () {
                $redis = new ArrayRedis;
                $wm = new PtsWatermark($redis);
                $wm->put(42, ['pts' => 1, 'date' => 1, 'qts' => 1, 'seq' => 1]);
                $this->redis = $redis;

                return $wm;
            });

            $this->artisan('teleframe:ingest:gate', ['account' => '42', '--only' => true])
                ->assertExitCode(0)
                ->expectsOutputToContain('watermark not persisted');

            self::assertSame('1', $this->redis->hget('tg:pts:42', 'pts'), '--only leaves the old watermark untouched');
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
