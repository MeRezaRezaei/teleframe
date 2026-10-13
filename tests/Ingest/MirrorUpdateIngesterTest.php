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
use MeRezaRezaei\Teleframe\Laravel\SchemaArtifacts;
use MeRezaRezaei\Teleframe\Mirror\Models\TfMessage;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactDecomposer;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactWriter;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorTableResolver;
use MeRezaRezaei\Teleframe\Tests\Ingest\Concerns\RunsMigrations;
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
    use RunsMigrations;

    /**
     * The tenants this fixture writes under.
     *
     * MEASURED: every account id written here is the literal 42 (the
     * `hydrateMessage()` default and every `account_id` in the payloads and
     * the routing rows). With foreign-key enforcement on, `tf_messages` and
     * `tg_update_routing` both carry `account_id -> telegram_accounts`, so the
     * write was refused at the DATABASE and the tests failed on their own
     * fixture — the fact count came back 0.
     *
     * Declared HERE rather than by relaxing `seedIngestTenant()`: that helper
     * contracts that each test declares the tenants it means to use, so a
     * test writing under an unplanted id fails LOUDLY. A blanket auto-seed
     * would also rob the orphan-refusal tests of their ability to plant one.
     */
    protected function ingestTenantIds(): array
    {
        return [42];
    }

    private array $dispatched = [];

    private MirrorUpdateIngester $ingester;

    protected function setUp(): void
    {
        parent::setUp();

        $scheme = TlParser::parseFile(dirname(__DIR__, 2).'/schema/sources/TL_telegram_v227.tl');
        $catalog = MirrorCatalog::load(SchemaArtifacts::catalog(), $scheme);
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

        // AFTER the migration, never before: `telegram_accounts` is one of the
        // migrated tables, so seeding first would insert into nothing.
        $this->seedIngestTenant(...$this->ingestTenantIds());
    }

    private function hydrateMessage(): callable
    {
        return fn (array $payload) => (new TfMessage)->forceFill([
            'account_id' => $payload['account_id'] ?? 42,
            'id' => (int) ($payload['id'] ?? 0),
            'peer_type' => (int) ($payload['peer_id']['_type'] ?? 0),
            'peer_id' => (int) ($payload['peer_id']['_id'] ?? 0),
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
        self::assertSame(true, $result['emit_scheduled'], 'peer marked act_on in settings → the UpdateStored event was HANDED TO THE DISPATCHER');
        // NOT "the listeners ran". UpdateStored implements
        // ShouldDispatchAfterCommit, so on the mirror path — where
        // MirrorIngesterFactory has an open transaction — Illuminate registers
        // the listeners as a post-commit callback and returns without
        // invoking any of them. `UpdateStoredDefersListenersUntilCommitTest`
        // owns the proof that they have not; asserting it again here would be
        // a second home for one fact. This test's job is narrower and is the
        // half the key is actually named for: the event was dispatched.
        self::assertCount(1, $this->dispatched, 'the boolean and the dispatch are the same fact, not two independent ones');
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
        self::assertSame(false, $result['emit_scheduled'], 'NO event scheduled — nobody marked this peer act_on (verbatim default)');
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
        self::assertSame(false, $result['emit_scheduled'], 'NO event scheduled — reflection of our behaviour (loop prevention)');
        self::assertCount(0, $this->dispatched);
        self::assertSame(1, DB::table('tf_messages')->where('account_id', 42)->where('id', 2)->count());
    }

    /**
     * NEGATIVE CONTROL — the rename is real, and the old lying key is GONE.
     *
     * The three tests above would all still pass if this change had been made
     * as an ADDITION rather than a rename: a report carrying both `emitted`
     * and `emit_scheduled` satisfies every assertion in them. That is exactly
     * the failure this gate exists to catch — an `emitted` alias left behind
     * keeps the promise that the listeners ran alive under the old name
     * forever, which is deprecation of nothing and entrenchment of the lie.
     *
     * It also catches the half-applied rename (new key absent, old key left
     * in place), which would make every assertion above pass against a report
     * that never earned its name.
     *
     * `assertIsBool` rather than `assertTrue` because a truthy non-boolean
     * (`1`, `'1'`) satisfies `assertTrue` while lying about the type this key
     * declares in its `@return` shape.
     */
    public function test_the_result_carries_the_honest_key_and_not_the_lying_one(): void
    {
        $this->migrateMirror();

        DB::table('tg_update_routing')->insert([
            'account_id' => 42,
            'peer_type' => 2,
            'peer_id' => 900,
            'mode' => UpdateRoutingRule::MODE_ACT_ON,
        ]);

        $result = $this->ingester->ingest(DB::connection(), 42, [
            '_' => 'message',
            'id' => 1,
            'peer_id' => ['_type' => 2, '_id' => 900],
            'date' => 1726000000,
            'message' => 'hello from outside',
            'out' => false,
        ], 'tf_messages', $this->hydrateMessage());

        self::assertArrayHasKey('emit_scheduled', $result, 'the honest key must be present');
        self::assertArrayNotHasKey(
            'emitted',
            $result,
            '`emitted` promised the listeners ran. Commit 7b7b7c31 made that false '
            .'while the name stayed, so the key is removed rather than aliased: a '
            .'surviving `emitted` would keep promising delivery to whoever reads it.',
        );
        self::assertIsBool($result['emit_scheduled'], 'the key declares bool; a truthy 1 would be a different lie');
        self::assertSame(true, $result['emit_scheduled']);
        self::assertCount(1, $this->dispatched, 'and it still tracks a real dispatch');
    }
}
