<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Ingest\ChunkFetcher;
use MeRezaRezaei\Teleframe\Ingest\MirrorChunkSync;
use MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorChunkDecomposer;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactDecomposer;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactWriter;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorTableResolver;
use Orchestra\Testbench\TestCase as TestbenchTestCase;

/**
 * Mirror chunk sync — the verbatim's session-register gate end to end:
 * fixture state → fixture Difference → decomposed rows → inserted into the
 * migrated relational DB. Zero FK clues = the full schema held for the chunk.
 */
final class MirrorChunkSyncTest extends TestbenchTestCase
{
    private MirrorChunkSync $sync;

    protected function setUp(): void
    {
        parent::setUp();

        $scheme = TlParser::parseFile(dirname(__DIR__, 2).'/schema/sources/TL_telegram_v227.tl');
        $catalog = MirrorCatalog::load(dirname(__DIR__, 2).'/docs/superpowers/specs/2026-09-11-telegram-mirror-catalog.json', $scheme);
        $resolver = new MirrorTableResolver($catalog, $scheme);
        $this->sync = new MirrorChunkSync(
            new MirrorChunkDecomposer(new MirrorFactDecomposer($resolver, $catalog), $catalog),
            new MirrorFactWriter,
        );
    }

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
        $out = sys_get_temp_dir().'/tf5s_'.uniqid();
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
                            'id' => 1,
                            'peer_id' => ['_type' => 2, '_id' => 900],
                            'date' => 1726000000,
                            'message' => 'first',
                        ],
                    ],
                    'users' => [
                        ['_' => 'user', 'id' => 101, 'verified' => true],
                    ],
                    'chats' => [
                        ['_' => 'chat', 'id' => 900, 'title' => 'mirror lab', 'participants_count' => 3, 'date' => 1726000000, 'version' => 1],
                    ],
                    'state' => [
                        '_' => 'updates.state',
                        'pts' => 11, 'qts' => 2, 'date' => 1726000000, 'seq' => 5,
                    ],
                ];
            }
        };
    }

    public function test_session_register_sync_ingests_the_big_update(): void
    {
        $out = $this->migrateMirror();
        try {
            $report = $this->sync->sync(DB::connection(), 42, $this->fixtureFetcher());

            self::assertSame(11, $report['state']['pts'], 'remote state returned for watermarking');
            self::assertSame(3, $report['rows'], 'every fact kind in the chunk decomposes');
            self::assertSame(3, $report['inserted'], 'every decomposed row inserts');
            self::assertEmpty($report['clues'], 'well-formed chunk → no decomposer clues');
            self::assertEmpty($report['fkClues'], 'well-formed chunk → no FK clues — schema held');

            self::assertSame(1, DB::table('tf_messages')->where('account_id', 42)->where('id', 1)->count());
            self::assertSame(1, DB::table('tf_users')->where('account_id', 42)->where('id', 101)->count());
            self::assertSame('mirror lab', DB::table('tf_chats')->where('account_id', 42)->where('id', 900)->value('title'));
        } finally {
            $this->rrmdir($out);
        }
    }

    public function test_prior_state_is_forwarded_to_the_fetcher(): void
    {
        $fetcher = new class implements ChunkFetcher
        {
            public ?array $seenPrior = null;

            public function getState(): array
            {
                return ['pts' => 5, 'date' => 1726000000, 'qts' => 0, 'seq' => 0];
            }

            public function getDifference(array $state, ?array $priorState = null): array
            {
                $this->seenPrior = $priorState;

                return ['_' => 'updates.difference', 'new_messages' => [], 'users' => [], 'chats' => []];
            }
        };

        $this->sync->sync(DB::connection(), 42, $fetcher, ['pts' => 3, 'date' => 1725000000, 'qts' => 0, 'seq' => 0]);

        self::assertSame(3, $fetcher->seenPrior['pts'], 'persisted watermark drives the difference offset');
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
