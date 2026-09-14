<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use MeRezaRezaei\Teleframe\Ingest\Events\UpdateStored;
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

    public function test_wire_peer_channel_shape_reaches_the_model_and_rows(): void
    {
        $out = $this->migrateMirror();
        try {
            $ingester = $this->app->make(DaemonCommand::MIRROR_INGESTER_KEY);

            // The wire decodes the peer as its ctor object (peerChannel#channel_id),
            // not the canonical _type/_id pair — the exact "telegram data came from
            // its mtproto" shape the verbatim says must be insertable as-is.
            $ingester([
                '_' => 'message',
                'id' => 7,
                'peer_id' => ['_' => 'peerChannel', 'channel_id' => 900],
                'date' => 1726000000,
                'message' => 'wire channel shape',
                'out' => false,
            ], 42);

            $row = DB::table('tf_messages')->where('account_id', 42)->where('id', 7)->first();
            self::assertNotNull($row, 'the wire update must store');
            self::assertSame(3, (int) $row->peer_id_type, 'peerChannel → canonical type 3 (spec enum)');
            self::assertSame(900, (int) $row->peer_id_id, 'channel_id → canonical peer id');
        } finally {
            $this->rrmdir($out);
        }
    }

    public function test_wire_peer_shape_reaches_the_emitted_model(): void
    {
        $out = $this->migrateMirror();
        try {
            $seen = [];
            Event::listen(UpdateStored::class, static function (UpdateStored $event) use (&$seen): void {
                $seen[] = $event->model;
            });
            $this->artisan('migrate', [
                '--path' => dirname(__DIR__, 2).'/src/Laravel/Migrations/2026_09_14_000001_create_tg_update_routing_table.php',
                '--realpath' => true,
            ])->assertExitCode(0);
            DB::table('tg_update_routing')->insert([
                'account_id' => 42,
                'peer_type' => 3,
                'peer_id' => 900,
                'mode' => 'act_on',
            ]);

            $ingester = $this->app->make(DaemonCommand::MIRROR_INGESTER_KEY);
            $ingester([
                '_' => 'message',
                'id' => 9,
                'peer_id' => ['_' => 'peerChannel', 'channel_id' => 900],
                'date' => 1726000000,
                'message' => 'wire shape to model',
                'out' => false,
            ], 42);

            self::assertCount(1, $seen, 'act_on peer → UpdateStored emitted');
            $model = $seen[0];
            self::assertSame(3, (int) $model->getAttribute('peer_id_type'), 'model carries canonical peer type');
            self::assertSame(900, (int) $model->getAttribute('peer_id_id'), 'model carries canonical peer id');
            self::assertSame('wire shape to model', (string) $model->getAttribute('message'));
        } finally {
            $this->rrmdir($out);
        }
    }

    public function test_wire_iteration_peer_is_blocked_not_silently_stored(): void
    {
        $out = $this->migrateMirror();
        try {
            $ingester = $this->app->make(DaemonCommand::MIRROR_INGESTER_KEY);

            // A peer the mirror cannot place must be blocked by the schema —
            // peer_id_type/peer_id_id are NOT NULL, so the insert fails and the
            // writer surfaces a clue (the wrong path made loud), never a fake 0.
            $ingester([
                '_' => 'message',
                'id' => 8,
                'peer_id' => ['_' => 'inputPeerUser', 'user_id' => 900], // not a Fact-side ctor
                'date' => 1726000000,
                'message' => 'input variant shape',
                'out' => false,
            ], 42);

            self::assertSame(
                0,
                DB::table('tf_messages')->where('account_id', 42)->where('id', 8)->count(),
                'unresolvable peer → NOT NULL blocks the row; no silent 0/0 in the mirror',
            );
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
