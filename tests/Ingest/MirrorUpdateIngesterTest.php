<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Ingest\Events\UpdateStored;
use MeRezaRezaei\Teleframe\Ingest\MirrorUpdateIngester;
use MeRezaRezaei\Teleframe\Ingest\RoutingEventGateway;
use MeRezaRezaei\Teleframe\Ingest\SelfOriginatedClassifier;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Ingest\UpdateRouter;
use MeRezaRezaei\Teleframe\Laravel\Models\UpdateRoutingRule;
use MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfMessage;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactDecomposer;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactWriter;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorTableResolver;
use Orchestra\Testbench\TestCase as TestbenchTestCase;

/**
 * MirrorUpdateIngester — the verbatim's daily loop end to end: one update
 * through decompose → write → classify → gated event, against the migrated
 * relational DB. Every fact is stored (first half of truth); only peers
 * the settings marked act_on emit UpdateStored (second half — the app
 * watches for specific updates).
 */
final class MirrorUpdateIngesterTest extends TestbenchTestCase
{
    private array $dispatched = [];

    private MirrorUpdateIngester $ingester;

    protected function setUp(): void
    {
        parent::setUp();

        $scheme = TlParser::parseFile(dirname(__DIR__, 2).'/schema/sources/TL_telegram_v227.tl');
        $catalog = MirrorCatalog::load(dirname(__DIR__, 2).'/docs/superpowers/specs/2026-09-11-telegram-mirror-catalog.json', $scheme);
        $resolver = new MirrorTableResolver($catalog, $scheme);
        $router = new UpdateRouter(DB::connection());
        $classifier = new SelfOriginatedClassifier($router);

        $events = new class($this->dispatched) implements Dispatcher
        {
            public function __construct(private array &$sink) {}

            public function listen($events, $listener = null): void {}

            public function hasListeners($eventName): bool
            {
                return true;
            }

            public function subscribe($subscriber): void {}

            public function until($event, $payload = [])
            {
                return null;
            }

            public function dispatch($event, $payload = [], $halt = false): array
            {
                $this->sink[] = $event;

                return [];
            }

            public function push($event, $payload = []): void {}

            public function flush($event): void {}

            public function forget($event): void {}

            public function forgetPushed(): void {}

            public function getRawListeners($eventName): array
            {
                return [];
            }
        };

        $this->ingester = new MirrorUpdateIngester(
            new MirrorFactDecomposer($resolver, $catalog),
            new MirrorFactWriter,
            $classifier,
            new RoutingEventGateway($router, $events),
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

    private function migrateMirror(): void
    {
        $this->artisan('migrate', [
            '--force' => true,
            '--realpath' => true,
            '--path' => UpdateIngestor::migrationPaths(),
        ])->assertExitCode(0);
    }

    private function hydrateMessage(): callable
    {
        return fn (array $payload) => (new TfMessage)->forceFill([
            'account_id' => $payload['account_id'] ?? 42,
            'id' => (int) ($payload['id'] ?? 0),
            'peer_id_type' => (int) ($payload['peer_id']['_type'] ?? 0),
            'peer_id_id' => (int) ($payload['peer_id']['_id'] ?? 0),
            'date' => (int) ($payload['date'] ?? 0),
            'message' => (string) ($payload['message'] ?? ''),
            'constructor' => (string) ($payload['_'] ?? 'message'),
        ]);
    }

    public function test_others_message_stores_and_emits_when_peer_marked_act_on(): void
    {
        $this->migrateMirror();

        DB::table('tg_update_routing')->insert([
            'account_id' => 42,
            'peer_type' => 2,
            'peer_id' => 900,
            'mode' => UpdateRoutingRule::MODE_ACT_ON,
        ]);

        $payload = [
            '_' => 'message',
            'id' => 1,
            'peer_id' => ['_type' => 2, '_id' => 900],
            'date' => 1726000000,
            'message' => 'hello from outside',
            'out' => false,
        ];

        $result = $this->ingester->ingest(DB::connection(), 42, $payload, 'tf_messages', $this->hydrateMessage());

        self::assertSame(1, $result['inserted'], 'the fact is stored');
        self::assertEmpty($result['clues']);
        self::assertEmpty($result['fkClues']);
        self::assertTrue($result['emitted'], 'peer marked act_on in settings → app input (UpdateStored)');
        self::assertCount(1, $this->dispatched);
        self::assertInstanceOf(UpdateStored::class, $this->dispatched[0]);
    }

    public function test_others_message_without_rule_stores_but_stays_silent(): void
    {
        $this->migrateMirror();

        $payload = [
            '_' => 'message',
            'id' => 3,
            'peer_id' => ['_type' => 2, '_id' => 901],
            'date' => 1726000002,
            'message' => 'unmarked peer',
            'out' => false,
        ];

        $result = $this->ingester->ingest(DB::connection(), 42, $payload, 'tf_messages', $this->hydrateMessage());

        self::assertSame(1, $result['inserted'], 'the fact IS stored (first half of truth)');
        self::assertFalse($result['emitted'], 'NO event — nobody marked this peer act_on (verbatim default)');
        self::assertCount(0, $this->dispatched);
        self::assertSame(1, DB::table('tf_messages')->where('account_id', 42)->where('id', 3)->count());
    }

    public function test_own_message_stores_but_stays_silent(): void
    {
        $this->migrateMirror();

        $payload = [
            '_' => 'message',
            'id' => 2,
            'peer_id' => ['_type' => 2, '_id' => 900],
            'date' => 1726000001,
            'message' => 'we sent this',
            'out' => true,
        ];

        $result = $this->ingester->ingest(DB::connection(), 42, $payload, 'tf_messages', $this->hydrateMessage());

        self::assertSame(1, $result['inserted'], 'the fact IS stored (first half of truth)');
        self::assertFalse($result['emitted'], 'NO event — reflection of our behaviour (loop prevention)');
        self::assertCount(0, $this->dispatched);
        self::assertSame(1, DB::table('tf_messages')->where('account_id', 42)->where('id', 2)->count());
    }
}
