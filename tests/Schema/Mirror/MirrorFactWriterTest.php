<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Mirror;

use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactDecomposer;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactWriter;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorTableResolver;
use Orchestra\Testbench\TestCase as TestbenchTestCase;

/**
 * FK-clue integration — the verbatim's "whatever foreign key that fails gives
 * us a clue to the wrong path of ingesting we have".
 *
 * Runs the stage-0 generator output against SQLite, decomposes a real
 * message payload, inserts parent-first, and asserts:
 * - a correct order (parent → children) inserts cleanly (0 FK clues);
 * - a wrong path (child before missing parent) surfaces as an FK clue.
 */
final class MirrorFactWriterTest extends TestbenchTestCase
{
    private MirrorFactDecomposer $decomposer;

    private MirrorFactWriter $writer;

    protected function setUp(): void
    {
        parent::setUp();

        $scheme = TlParser::parseFile(dirname(__DIR__, 3).'/schema/sources/TL_telegram_v227.tl');
        $catalog = MirrorCatalog::load(dirname(__DIR__, 3).'/docs/superpowers/specs/2026-09-11-telegram-mirror-catalog.json', $scheme);
        $resolver = new MirrorTableResolver($catalog, $scheme);
        $this->decomposer = new MirrorFactDecomposer($resolver, $catalog);
        $this->writer = new MirrorFactWriter;
    }

    protected function getApplicationBasePath()
    {
        return dirname(__DIR__, 3);
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
        $out = sys_get_temp_dir().'/tf5w_'.uniqid();
        @mkdir($out.'/migrations/mirror', 0777, true);

        $this->artisan('teleframe:mirror', ['--stage' => '0', '--out' => $out])->assertExitCode(0);
        $this->artisan('migrate', ['--path' => $out.'/migrations/mirror', '--realpath' => true])->assertExitCode(0);

        return $out;
    }

    public function test_parent_first_ingest_inserts_cleanly(): void
    {
        $out = $this->migrateMirror();
        try {
            $payload = [
                '_' => 'message',
                'id' => 1001,
                'peer_id' => ['_type' => 2, '_id' => 900],
                'date' => 1726000000,
                'message' => 'hello from the mirror',
                'views' => 5,
                'from_boosts_applied' => 3,
                'out' => true,
            ];

            $result = $this->decomposer->decompose('tf_messages', 42, $payload);
            self::assertEmpty($result['clues'], 'valid payload must decompose without clues');

            $write = $this->writer->write(DB::connection(), $result['rows']);
            self::assertSame(count($result['rows']), $write['inserted'], 'every decomposed row must insert');
            self::assertEmpty($write['fkClues'], 'parent-first order must not trigger FK clues');

            self::assertSame(1, DB::table('tf_messages')->where('account_id', 42)->where('id', 1001)->count());
            self::assertSame(5, DB::table('tf_messages_views')->where('account_id', 42)->where('id', 1001)->value('views'));
        } finally {
            $this->rrmdir($out);
        }
    }

    public function test_child_before_parent_is_an_fk_clue(): void
    {
        $out = $this->migrateMirror();
        try {
            // Wrong path: only the child fact, no parent row yet.
            $childRow = [
                'table' => 'tf_messages_views',
                'row' => ['account_id' => 42, 'id' => 9999, 'views' => 1],
            ];

            $write = $this->writer->write(DB::connection(), [$childRow]);

            self::assertSame(0, $write['inserted'], 'orphan child must not insert');
            self::assertCount(1, $write['fkClues'], 'FK failure must surface as exactly one clue');
            self::assertStringContainsString('tf_messages_views', $write['fkClues'][0]);
            self::assertStringContainsString('FOREIGN KEY', $write['fkClues'][0]);
        } finally {
            $this->rrmdir($out);
        }
    }

    public function test_reingest_after_parent_lands_is_clean(): void
    {
        $out = $this->migrateMirror();
        try {
            // The gradual loop: orphan child fails → ingest parent → retry child → clean.
            $parent = [
                'table' => 'tf_messages',
                'row' => [
                    'account_id' => 42,
                    'id' => 777,
                    'peer_id_type' => 2,
                    'peer_id_id' => 900,
                    'constructor' => 'message',
                    'date' => 1726000000,
                    'message' => 'parent lands later',
                ],
            ];
            $child = [
                'table' => 'tf_messages_views',
                'row' => ['account_id' => 42, 'id' => 777, 'views' => 4],
            ];

            $first = $this->writer->write(DB::connection(), [$child]);
            self::assertCount(1, $first['fkClues']);

            $this->writer->write(DB::connection(), [$parent]);
            $second = $this->writer->write(DB::connection(), [$child]);
            self::assertSame(1, $second['inserted']);
            self::assertEmpty($second['fkClues'], 'after the parent exists the same child inserts cleanly');
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
