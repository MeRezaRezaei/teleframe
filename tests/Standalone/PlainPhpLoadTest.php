<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Standalone;

use Illuminate\Container\Container;
use MeRezaRezaei\Teleframe\App\AppCall;
use MeRezaRezaei\Teleframe\App\AppCallSpec;
use MeRezaRezaei\Teleframe\App\AppDefinition;
use MeRezaRezaei\Teleframe\App\AppMode;
use MeRezaRezaei\Teleframe\Backfill\BackfillWorker;
use MeRezaRezaei\Teleframe\Backup\InMemoryVault;
use MeRezaRezaei\Teleframe\Bus\RedisStreamSink;
use MeRezaRezaei\Teleframe\Bus\RouteTable;
use MeRezaRezaei\Teleframe\Bus\StreamSchema;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\WebSocketFrameCodec;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\WebSocketRelayServer;
use MeRezaRezaei\Teleframe\Daemon\AccountWorker;
use MeRezaRezaei\Teleframe\Handler\Deduplicator;
use MeRezaRezaei\Teleframe\Handler\HandlerRegistry;
use MeRezaRezaei\Teleframe\Handler\InMemoryCache;
use MeRezaRezaei\Teleframe\Handler\Pipeline;
use MeRezaRezaei\Teleframe\Handler\Update;
use MeRezaRezaei\Teleframe\Handler\UpdateDispatcher;
use MeRezaRezaei\Teleframe\Ingest\EntityAggregator;
use MeRezaRezaei\Teleframe\Ingest\Events\UpdateStored;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Laravel\Http\Middleware\ScopeTelegramReadOnly;
use MeRezaRezaei\Teleframe\Laravel\Services\ReadOnlyScope;
use MeRezaRezaei\Teleframe\Laravel\Services\ReadOnlyVaultDispatch;
use MeRezaRezaei\Teleframe\Laravel\Store\S3DurableVault;
use MeRezaRezaei\Teleframe\Laravel\Store\S3GatewayContract;
use MeRezaRezaei\Teleframe\Passport\CachePendingLoginRegistry;
use MeRezaRezaei\Teleframe\Passport\MemoryBindingWriter;
use MeRezaRezaei\Teleframe\Passport\MemoryPendingLoginRegistry;
use MeRezaRezaei\Teleframe\Passport\PassportAnonymousLoginService;
use MeRezaRezaei\Teleframe\Passport\PassportConfig;
use MeRezaRezaei\Teleframe\Passport\PassportKeyLoader;
use MeRezaRezaei\Teleframe\Passport\PassportLoginException;
use MeRezaRezaei\Teleframe\Passport\PendingLoginBinding;
use MeRezaRezaei\Teleframe\Passport\SessionFingerprint;
use MeRezaRezaei\Teleframe\Realtime\CentrifugoBridge;
use MeRezaRezaei\Teleframe\Realtime\ChannelNames;
use MeRezaRezaei\Teleframe\Realtime\HttpCentrifugoBridge;
use MeRezaRezaei\Teleframe\Schema\Generator\SchemaRegenerator;
use MeRezaRezaei\Teleframe\Schema\Generator\TeleframeSchemeLoader;
use MeRezaRezaei\Teleframe\Store\Cache\MemoryHotCache;
use MeRezaRezaei\Teleframe\Store\Index\MemoryStoreIndex;
use MeRezaRezaei\Teleframe\Store\MediaReference;
use MeRezaRezaei\Teleframe\Store\ObjectManifest;
use MeRezaRezaei\Teleframe\Store\Queue\SyncUploadQueue;
use MeRezaRezaei\Teleframe\Store\ReclaimOutcome;
use MeRezaRezaei\Teleframe\Store\ReferencePolicy;
use MeRezaRezaei\Teleframe\Store\Schedules\NullSchedulePublisher;
use MeRezaRezaei\Teleframe\Store\Schedules\RecurringScheduleClient;
use MeRezaRezaei\Teleframe\Store\Schedules\SdkTemporalScheduleClient;
use MeRezaRezaei\Teleframe\Store\Schedules\StoreMaintenanceScheduleSpec;
use MeRezaRezaei\Teleframe\Store\Schedules\TemporalClientResolver;
use MeRezaRezaei\Teleframe\Store\Schedules\TemporalSchedulePublisher;
use MeRezaRezaei\Teleframe\Store\Schedules\Worker\ContainerResolver;
use MeRezaRezaei\Teleframe\Store\Schedules\Worker\StoreBackupDumpActivity;
use MeRezaRezaei\Teleframe\Store\Schedules\Worker\TemporalWorker;
use MeRezaRezaei\Teleframe\Store\Schedules\Worker\WorkflowTypeCatalog;
use MeRezaRezaei\Teleframe\Store\StoreService;
use MeRezaRezaei\Teleframe\Supervisor\OpsReadModel;
use MeRezaRezaei\Teleframe\Supervisor\ProcessCatalog;
use MeRezaRezaei\Teleframe\Supervisor\ProcessSpec;
use MeRezaRezaei\Teleframe\Supervisor\Supervisor;
use MeRezaRezaei\Teleframe\Supervisor\SystemProcessSpawner;
use MeRezaRezaei\Teleframe\Supervisor\UnknownProcessException;
use MeRezaRezaei\Teleframe\Teleframe;
use MeRezaRezaei\Teleframe\Testing\FakeDispatcher;
use MeRezaRezaei\Teleframe\Tests\Laravel\Store\FakeS3Gateway;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayCache;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayContainer;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayRedis;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;

/**
 * Mirrors bin/standalone-smoke.php: every merged module must be loadable and
 * constructible with NO Laravel app booted. Before the Phase 1 fix this
 * fatals: UpdateStored imported Illuminate\Foundation\Events\Dispatchable
 * and Illuminate\Queue\SerializesModels — neither is a production dependency.
 */
final class PlainPhpLoadTest extends TestCase
{
    public function test_update_stored_loads_without_laravel(): void
    {
        self::assertTrue(class_exists(UpdateStored::class));
    }

    public function test_update_stored_has_no_framework_traits(): void
    {
        $uses = class_uses(UpdateStored::class);

        self::assertSame([], $uses);
    }

    public function test_ingestor_constructs_without_laravel(): void
    {
        $ingestor = new UpdateIngestor;

        self::assertInstanceOf(UpdateIngestor::class, $ingestor);
    }

    public function test_public_face_constructs_from_ingest_singletons(): void
    {
        $container = new Container;
        $container->instance(UpdateIngestor::class, new UpdateIngestor);
        $container->instance(EntityAggregator::class, new EntityAggregator);
        $teleframe = new Teleframe($container);

        self::assertInstanceOf(Teleframe::class, $teleframe);
    }

    public function test_bus_stream_schema_codec_round_trips(): void
    {
        // The v2 committed-key entry: {v, account_id, seq, position,
        // constructor, ts} — the row identity, never an inline payload
        // (D-039). The old fixture round-tripped a v1 `update` blob, which
        // the encoder now refuses on purpose.
        $entry = [
            'v' => StreamSchema::VERSION,
            'account_id' => 42,
            'seq' => 7,
            'position' => 1,
            'constructor' => 'updateNewMessage',
            'ts' => 1720000000,
        ];

        self::assertSame($entry, StreamSchema::decode(StreamSchema::encode($entry)));
    }

    public function test_bus_sink_and_route_table_bind_to_the_memory_double(): void
    {
        $redis = new ArrayRedis;

        self::assertInstanceOf(RouteTable::class, new RouteTable($redis));
        self::assertInstanceOf(RedisStreamSink::class, new RedisStreamSink($redis, 42));
    }

    public function test_backfill_worker_constructs_without_a_fetch_source(): void
    {
        $worker = new BackfillWorker(10, 3600, static fn (int $seconds): bool => true);

        self::assertInstanceOf(BackfillWorker::class, $worker);
    }

    public function test_backup_memory_vault_constructs(): void
    {
        self::assertInstanceOf(InMemoryVault::class, new InMemoryVault);
    }

    public function test_store_modules_construct_without_laravel(): void
    {
        // Mirror the Store smoke checks: manifest round-trip, index upsert,
        // cache remember, sync queue enqueue, schedules seam, service ctor.
        $manifest = ObjectManifest::new(
            'smoke.bin',
            3,
            str_repeat('a', 64),
            ObjectManifest::MODE_RAW,
            'application/octet-stream',
            null,
            [],
        );
        $reference = MediaReference::fromIndexRow([
            'doc_id' => 777,
            'access_hash' => 123456789,
            'file_reference' => 'AQAD-file-reference-bytes',
            'size' => 4096,
            'mime' => 'image/jpeg',
        ]);
        self::assertSame(777, $reference->toInputDocumentFileLocation()['id']);

        // Cycle 16 W2: the reclamation policy is plain-PHP constructible and
        // the safe default never frees anything, with no Illuminate in sight.
        self::assertFalse(ReferencePolicy::retainLocal()->reclaims());
        self::assertFalse(ReferencePolicy::retainLocal()->permitsReclaim(true));
        self::assertTrue(ReferencePolicy::dropLocalAfterIndex()->permitsReclaim(true));
        self::assertFalse(ReferencePolicy::dropLocalAfterIndex()->permitsReclaim(false));
        self::assertSame(
            ReclaimOutcome::RETAINED,
            (new ReclaimOutcome(ReclaimOutcome::RETAINED, ReclaimOutcome::REASON_POLICY_RETAINS))->state(),
        );
        self::assertTrue(
            (new ReclaimOutcome(ReclaimOutcome::RECLAIMED, ReclaimOutcome::REASON_DELETED))->freedBytes(),
        );

        $reparsed = ObjectManifest::fromArray($manifest->toArray());

        self::assertSame('smoke.bin', $reparsed->name());
        self::assertSame(ObjectManifest::MODE_RAW, $reparsed->mode());

        $index = new MemoryStoreIndex;
        $index->upsertBucket(1, 'data:x', ['kind' => 'data', 'channel_id' => 5]);
        self::assertNotNull($index->findBucket(1, 'data:x'));
        self::assertCount(1, $index->listBuckets(1, 'data'));

        $cache = new MemoryHotCache(ttlSeconds: 60, maxBytes: 1024);
        $cache->put('smoke', str_repeat('a', 64), 0, 'bytes');
        self::assertSame('bytes', $cache->get('smoke', str_repeat('a', 64), 0));

        $queue = new SyncUploadQueue;

        // confine() refuses a source that does not resolve to a real file, so
        // the fixture is a real file. Contents are irrelevant; existence is
        // the whole contract, and it is removed again below.
        $source = tempnam(sys_get_temp_dir(), 'smoke-');
        self::assertIsString($source);

        try {
            $queue->enqueue([
                'account_id' => 1,
                'bucket' => 'b',
                'name' => 'n',
                'mode' => 'raw',
                'source_path' => $source,
                'size' => 3,
            ]);
            self::assertFalse($queue->isAsync());
        } finally {
            @unlink($source);
        }

        self::assertStringContainsString('Schedule driver: null', (new NullSchedulePublisher)->describe());

        $service = new StoreService(
            accountId: 1,
            channelFactory: static function (string $bucket): never {
                throw new \RuntimeException('channel factory invoked — smoke constructs only');
            },
            index: new MemoryStoreIndex,
            cache: new MemoryHotCache(ttlSeconds: 60, maxBytes: 1024),
            queue: new SyncUploadQueue,
        );

        self::assertInstanceOf(StoreService::class, $service);
    }

    /**
     * Mirror of the smoke gate's three Store\Schedules checks, added with them.
     * temporal/sdk is an OPTIONAL runtime dependency (require-dev here), so all
     * three must hold on a host that has no ext-grpc, no SDK and no Temporal
     * server: a named capability that cannot even be AUTOLOADED is unreachable
     * for every consumer that does not install the optional package.
     */
    public function test_the_temporal_client_resolver_constructs_and_refuses_by_name_with_the_sdk_absent(): void
    {
        // The factory seam models "the optional SDK is not usable here" without
        // needing ext-grpc, a network, or a Temporal server, so this check runs
        // identically in CI and on a bare host. The refusal must be the NAMED
        // one — a null, a no-op or a different message would be the silent
        // success this seam exists to kill.
        $refused = null;

        try {
            (new TemporalClientResolver(null, static fn (): ?RecurringScheduleClient => null))
                ->resolve('127.0.0.1:7233');
        } catch (\RuntimeException $e) {
            $refused = $e->getMessage();
        }

        self::assertSame(SdkTemporalScheduleClient::sdkMissingMessage(), $refused);
    }

    public function test_the_sdk_temporal_schedule_client_constructs_and_refuses_by_name_without_dialing(): void
    {
        // Construction must touch no socket — a client that dialled in its
        // constructor would open a queue connection during a Laravel boot — and
        // this file must LOAD with the SDK absent. A bare stdClass stands in for
        // the SDK object so the check needs no Temporal server; duck typing then
        // has to fail LOUDLY rather than pretend the schedule was armed.
        $client = new SdkTemporalScheduleClient(new \stdClass, 'default');

        $refused = null;
        try {
            $client->createOrUpdateSchedule('tstore-backup', 'teleframe:tstore backup', 900, 'tstore');
        } catch (\RuntimeException $e) {
            $refused = $e->getMessage();
        }

        self::assertSame('default', $client->namespace());
        self::assertNotNull($refused);
        self::assertStringContainsString('createSchedule()+getHandle()', $refused);
    }

    public function test_the_store_maintenance_schedule_spec_constructs_and_is_deterministic(): void
    {
        // The phpunit mirror of the bin/standalone-smoke.php check of the same
        // name — the smoke file's header requires this file to stay in lockstep
        // with it. `teleframe:tstore schedules --install` arms what this builds,
        // so two properties matter and neither is visible without a container:
        // it constructs with no arguments, and its schedule ids are
        // DETERMINISTIC. A non-deterministic id makes a second arming stack a
        // second schedule instead of refreshing the one, which is exactly what
        // the SDK client's upsert idempotence exists to prevent.
        $spec = new StoreMaintenanceScheduleSpec(3600, 900);

        $first = $spec->installSpec();
        $second = $spec->installSpec();

        self::assertNotSame([], $first);
        self::assertArrayHasKey('tstore-hot-cache-prune', $first);
        self::assertArrayHasKey('tstore-registry-sweep', $first);
        self::assertSame($first, $second, 'a second arming must refresh, not stack');
        self::assertSame(array_keys($first), array_keys($second));
    }

    public function test_the_temporal_publisher_refuses_without_a_client_and_describes_honestly(): void
    {
        // The temporal driver's no-client arm — the one a host with neither a
        // binding nor the SDK actually reaches. The describe() half is the
        // honesty pin: it must not claim a schedule is armed before install()
        // has run, which is exactly what the old "are enabled" text did.
        $publisher = new TemporalSchedulePublisher('127.0.0.1:7233', null, 'tstore');
        $described = $publisher->describe();

        $refused = null;
        try {
            $publisher->install(['tstore-backup' => ['activity' => 'teleframe:tstore backup', 'interval_seconds' => 900]]);
        } catch (\RuntimeException $e) {
            $refused = $e->getMessage();
        }

        self::assertSame(TemporalSchedulePublisher::missingClientMessage(1), $refused);
        self::assertStringContainsString('Schedule driver: temporal', $described);
        self::assertStringContainsString('No Temporal client is bound', $described);
        self::assertStringNotContainsString('enabled', strtolower($described));
    }

    /**
     * Mirror of the smoke gate's AppCallSpec check. The schedule id is
     * `app-call-{app}-{call}-{12-hex sha256(app . NUL . call)}` as of a8b63957;
     * before that it was `app-call-{callName}` alone, unique only WITHIN one app,
     * so two apps each declaring `status-ping` installed the SAME id and the
     * SDK client's describe-then-upsert silently replaced app A's cadence. The
     * cross-app distinctness assertion is the regression pin, and the literal
     * below is the exact string the fix changed.
     */
    /**
     * Mirror of the smoke gate's TemporalWorker check — the WORKER half of the
     * temporal seam, which did not exist until this slice.
     *
     * The property the gate exists for: `SdkTemporalScheduleClient` arms a real
     * server-side schedule whose action is `StartWorkflowAction::new($type)`
     * with a raw string type. Before TemporalWorker shipped, no file in src/
     * implemented any of those strings, so an "armed … recurring every …" line
     * described a job whose every tick started nothing. A worker is a
     * long-running process, so the construction half matters as much as the
     * registration: a constructor that dialled Temporal or resolved a container
     * seam would open a connection on `php artisan list`.
     */
    public function test_the_temporal_worker_constructs_serves_the_shipped_types_and_refuses_by_name(): void
    {
        $worker = new TemporalWorker('tstore', [['echo', 'health']]);

        self::assertSame([], $worker->resolver()->seams(), 'constructing must resolve no container seam');
        self::assertSame('tstore', $worker->taskQueue());
        self::assertSame(
            [
                'teleclient.notify.check-no-show',
                'teleframe.notify.check-no-show',
                'teleframe:tstore backup',
                // Added by 9c44f927: StoreMaintenanceScheduleSpec arms these two,
                // so leaving them unserved would have made the worker's own
                // honesty list non-empty — and an emptied list only stays
                // meaningful while every type the tree arms really IS served.
                'teleframe:tstore prune',
                'teleframe:tstore sync',
                'telegram.app.run-call echo health',
            ],
            $worker->servedTypes(),
            'both no-show spellings must be served: the notify seam and the backup seam '
            .'emit different strings for the same work (NotifyCommand.php:230 vs BackupScheduleSpec.php:62)',
        );
        self::assertCount(5, TemporalWorker::servedActivities());

        $described = $worker->describe();
        self::assertStringContainsString('no live Temporal server', $described,
            'a worker describe() that reads as proof of firing is the false green in the worker itself');

        // SDK-absent, via the availability probe: temporal/sdk IS installed in
        // this checkout (require-DEV), so without the probe the guard could
        // never be exercised and would rot.
        $absent = new TemporalWorker('tstore', [], new ContainerResolver, new WorkflowTypeCatalog, static fn (): bool => false);

        $refused = null;
        try {
            $absent->register(new \stdClass);
        } catch (\RuntimeException $e) {
            $refused = $e->getMessage();
        }

        self::assertNotNull($refused);
        self::assertStringContainsString('composer require temporal/sdk', $refused);
        self::assertStringContainsString('php bin/teleframe-worker', $refused);

        // A container-free activity must refuse BY SEAM NAME, never return an
        // empty result that reads as a clean run.
        $activityRefused = null;
        try {
            (new StoreBackupDumpActivity(ContainerResolver::refusing()))->dump([]);
        } catch (\RuntimeException $e) {
            $activityRefused = $e->getMessage();
        }

        self::assertNotNull($activityRefused);
        self::assertStringContainsString(StoreBackupDumpActivity::SERVICE_SEAM, $activityRefused);
    }

    public function test_the_app_call_install_payload_id_is_app_namespaced_and_digest_disambiguated(): void
    {
        $app = AppDefinition::define('echo-bot', AppMode::Bot, calls: [
            AppCall::every('status-ping', 3600, 'messages.sendMessage', ['peer' => '@ops']),
        ]);
        $spec = new AppCallSpec;
        $id = $spec->callId($app, $app->calls()[0]);
        $payload = $spec->installSpec($app)[$id] ?? null;

        $otherApp = AppDefinition::define('other-bot', AppMode::Bot, calls: [
            AppCall::every('status-ping', 60, 'messages.sendMessage'),
        ]);

        self::assertNotNull($payload);
        self::assertStringStartsWith('app-call-echo-bot-status-ping-', $id);
        self::assertNotSame(
            $id,
            $spec->callId($otherApp, $otherApp->calls()[0]),
            'two apps declaring the same call name must not install the same schedule id',
        );
        self::assertSame(3600, $payload['interval_seconds']);
        self::assertSame('telegram.app.run-call echo-bot status-ping', $payload['activity']);
        self::assertSame('echo-bot', $payload['app']);
        self::assertSame('status-ping', $payload['call']);
    }

    public function test_schema_loader_parses_without_config(): void
    {
        $scheme = TeleframeSchemeLoader::parseString('bool#0c734172 = Bool;');

        self::assertNotSame([], $scheme->types());
    }

    public function test_schema_regenerator_loads_the_committed_sources(): void
    {
        $scheme = (new SchemaRegenerator)->loadScheme(dirname(__DIR__, 2).'/schema/sources');

        self::assertNotSame([], $scheme->types());
    }

    public function test_daemon_account_worker_constructs_with_injected_factories(): void
    {
        $worker = new AccountWorker(
            ['account_id' => 1, 'flood_cap_seconds' => 3600],
            static function (): void {
                throw new \RuntimeException('scope factory invoked — test never touches the wire');
            },
            null,
            static fn (int $seconds): bool => true,
        );

        self::assertInstanceOf(AccountWorker::class, $worker);
    }

    public function test_handler_stack_constructs_without_laravel(): void
    {
        $registry = new HandlerRegistry;
        $sends = new InMemoryCache;
        $dispatcher = new UpdateDispatcher($registry, new Pipeline, new ArrayContainer, $sends);

        self::assertInstanceOf(UpdateDispatcher::class, $dispatcher);
        self::assertInstanceOf(HandlerRegistry::class, $registry);
        self::assertInstanceOf(Update::class, Update::fromBus(['_' => 'x'], 1));
        self::assertInstanceOf(Deduplicator::class, new Deduplicator(new InMemoryCache));
    }

    public function test_fake_dispatcher_constructs_and_runs_the_real_pipeline(): void
    {
        $registry = new HandlerRegistry;
        $fired = 0;
        $registry->onMessage(static function (Update $u) use (&$fired): void {
            $fired++;
        });
        $sends = new ArrayCache;

        $fake = new FakeDispatcher(
            [['update' => ['_' => 'updateNewMessage'], 'account_id' => 7]],
            $registry,
            new ArrayContainer,
            $sends,
        );
        $result = $fake->run();

        self::assertSame(1, $fired);
        self::assertCount(1, $result['dispatched']);
    }

    public function test_channel_names_produces_correct_shapes(): void
    {
        self::assertSame('account:42:updates', ChannelNames::accountUpdates(42));
        self::assertSame('account:7:messages:99', ChannelNames::accountMessages(7, 99));
    }

    public function test_http_centrifugo_bridge_constructs_without_laravel(): void
    {
        $bridge = new HttpCentrifugoBridge('http://localhost:8000/api', 'key', 'secret');

        self::assertInstanceOf(HttpCentrifugoBridge::class, $bridge);
    }

    public function test_centrifugo_bridge_interface_exists(): void
    {
        self::assertTrue(interface_exists(CentrifugoBridge::class));
    }

    public function test_teleframe_facade_constructs_without_laravel(): void
    {
        $registry = new HandlerRegistry;
        $sends = new ArrayCache;
        $container = new ArrayContainer;
        $container->set(HandlerRegistry::class, $registry);
        $container->set(UpdateDispatcher::class, new UpdateDispatcher($registry, new Pipeline, $container, $sends));
        $container->set(CacheInterface::class, $sends);

        $teleframe = new Teleframe($container);
        $fired = 0;
        $teleframe->onMessage(static function (Update $u) use (&$fired): void {
            $fired++;
        });
        $results = $teleframe->run([['update' => ['_' => 'updateNewMessage'], 'account_id' => 1]]);

        self::assertInstanceOf(Teleframe::class, $teleframe);
        self::assertSame(1, $fired);
        self::assertCount(1, $results);
    }

    public function test_web_socket_frame_codec_loads_and_decodes_a_binary_frame(): void
    {
        self::assertTrue(class_exists(WebSocketFrameCodec::class));

        $frame = WebSocketFrameCodec::frame(
            WebSocketFrameCodec::OPCODE_BINARY,
            'smoke-payload',
        );
        $parsed = WebSocketFrameCodec::parseFrame($frame);

        self::assertNotNull($parsed);
        self::assertSame('smoke-payload', $parsed['payload']);
    }

    public function test_web_socket_relay_server_constructs_without_connecting(): void
    {
        // Constructing must never dial the wire (same lazy rule as the
        // RelayServer / AccountRelay seam).
        $relay = new WebSocketRelayServer;

        self::assertInstanceOf(WebSocketRelayServer::class, $relay);
    }

    public function test_the_supervisor_constructs_with_no_container_and_starts_nothing(): void
    {
        $supervisor = new Supervisor(new SystemProcessSpawner);

        self::assertSame([], $supervisor->report(), 'no process is spawned at construction');
        self::assertNull($supervisor->state('ingest'));
        self::assertSame(
            ['daemon', 'ingest', 'relay', 'tstore-work'],
            (new ProcessCatalog)->names(),
        );
    }

    public function test_the_ops_read_model_constructs_and_reads_the_memory_redis_double(): void
    {
        self::assertFalse((new OpsReadModel)->snapshot()->bus, 'no bus bound is honest, not fatal');

        $redis = new ArrayRedis;
        $redis->hset(StreamSchema::STRIKES_KEY, '1-0', '2');
        $snapshot = (new OpsReadModel($redis))->snapshot();

        self::assertSame([['entry_id' => '1-0', 'attempts' => 2, 'failing' => true]], $snapshot->failingIngest);
    }

    public function test_an_unknown_supervised_process_name_refuses_loudly(): void
    {
        $this->expectException(UnknownProcessException::class);

        (new ProcessCatalog)->specFor('ingst');
    }

    public function test_process_spec_builds_argv_without_a_process(): void
    {
        $spec = new ProcessSpec(name: 'ingest', command: ['php', 'artisan', 'teleframe:ingest']);

        self::assertSame(['php', 'artisan', 'teleframe:ingest'], $spec->command);
    }

    public function test_read_only_scope_and_dispatch_are_plain_php_constructible(): void
    {
        $scope = new ReadOnlyScope;
        self::assertFalse($scope->isReadOnly(), 'a fresh scope is off by default — standalone behaviour unchanged');

        $scope->begin();
        self::assertTrue($scope->isReadOnly(), 'an open frame marks the scope read-only');
        $scope->end();

        $calls = 0;
        $dispatch = new ReadOnlyVaultDispatch(
            static function (string $method, array $params) use (&$calls): array {
                $calls++;

                return ['_' => $method];
            },
            $scope,
        );

        self::assertSame('messages.getDialogs', $dispatch('messages.getDialogs', [])['_'], 'reads pass through');
        self::assertSame(1, $calls, 'the underlying dispatch was reached exactly once');

        $middleware = new ScopeTelegramReadOnly($scope, static fn (): bool => true);
        self::assertInstanceOf(ScopeTelegramReadOnly::class, $middleware, 'the route middleware is plain-PHP constructible');
    }

    /**
     * The ANONYMOUS passport LOGIN family, mirrored from
     * bin/standalone-smoke.php: the one surface in this module that can turn a
     * sessionless visitor into a logged-in user, and therefore the one whose
     * classes must be constructible with NO container. Two of these are not
     * just loadability checks — they are the security properties stated as
     * facts: a fingerprint is a REQUIRED argument of the login, and a blank
     * session id is refused rather than digested into a constant every
     * sessionless caller would share.
     */
    public function test_session_fingerprint_refuses_a_blank_session_id(): void
    {
        $a = SessionFingerprint::of('session-a');

        self::assertSame(hash('sha256', 'session-a'), $a);
        self::assertNotSame($a, SessionFingerprint::of('session-b'));
        self::assertTrue(SessionFingerprint::matches($a, $a));
        self::assertFalse(SessionFingerprint::matches($a, SessionFingerprint::of('session-b')));

        $refused = false;
        try {
            SessionFingerprint::of('');
        } catch (PassportLoginException) {
            $refused = true;
        }
        self::assertTrue($refused);
    }

    public function test_the_pending_login_registries_construct_and_bind_one_time(): void
    {
        $memory = new MemoryPendingLoginRegistry(60);
        $ticket = $memory->issue('fingerprint-a');
        $binding = $memory->consume($ticket->token());

        self::assertInstanceOf(PendingLoginBinding::class, $binding);
        self::assertSame('fingerprint-a', $binding->sessionFingerprint());
        self::assertSame(hash('sha256', $ticket->nonce()), $binding->nonceDigest());
        self::assertNull($memory->consume($ticket->token()), 'a marker is one-time');

        $cache = new CachePendingLoginRegistry(new NoopCache, 60, 'teleframe.passport.pending.');
        $cacheTicket = $cache->issue('fingerprint-a');
        self::assertNotNull($cache->consume($cacheTicket->token()));
        self::assertNull($cache->consume($cacheTicket->token()));
        self::assertFalse($cache->isAtomic());
    }

    public function test_the_anonymous_login_service_constructs_inert_with_a_required_fingerprint(): void
    {
        $service = new PassportAnonymousLoginService(
            config: new PassportConfig([]),
            keys: new PassportKeyLoader('/nonexistent.pem'), // never read — construct only
            pending: new MemoryPendingLoginRegistry(60),
            writer: new MemoryBindingWriter,
        );

        self::assertSame($service, $service->service());

        // Inert: a default config cannot mint anything. The throw IS the
        // assertion. It is captured and asserted non-null, so a swallowed
        // refusal and a wrong-typed refusal both gate red, and the green path
        // spends one real assertion instead of a constant.
        $thrown = null;
        try {
            $service->start(SessionFingerprint::of('a-session'));
        } catch (PassportLoginException $e) {
            $thrown = $e;
        }
        self::assertNotNull($thrown, 'a default config must refuse to start an anonymous login');

        // The binding is NOT optional, stated at the type level.
        $fingerprint = (new \ReflectionMethod($service, 'login'))->getParameters()[2];
        self::assertSame('sessionFingerprint', $fingerprint->getName());
        self::assertFalse($fingerprint->allowsNull());
        self::assertFalse($fingerprint->isOptional());
        self::assertFalse($fingerprint->isDefaultValueAvailable());
    }

    /**
     * The phpunit mirror of the bin/standalone-smoke.php check of the same
     * name — the smoke file's header requires this file to stay in lockstep
     * with it, and `Laravel\Store\S3DurableVault` is the durable backup tier
     * that arrived this cycle. It must construct with NO container, NO
     * secret and NO MinIO, because the gateway is an injected
     * `S3GatewayContract`: a constructor that resolved a client or read a
     * credential would open a network call on `php artisan list`.
     *
     * Against `FakeS3Gateway` (this repo's existing in-memory double, already
     * used by the S3 tier suite) the manifest/chunk round-trip is proved with
     * the two properties that are NOT visible from construction alone:
     *
     *  1. CIPHERTEXT ADDRESSING. A chunk is stored under its CIPHERTEXT hash,
     *     while the manifest records the PLAINTEXT content address, and a
     *     reader resolves one to the other. So the object KEY must never
     *     carry the plaintext hash, and `getChunk()` must be called with the
     *     plaintext address the manifest holds.
     *  2. LOUD ABSENCE. A missing chunk throws instead of returning an empty
     *     string — an empty chunk would decrypt to nothing and be restored
     *     as a silently truncated file.
     */
    public function test_the_s3_durable_vault_constructs_and_round_trips_a_manifest_and_chunk_offline(): void
    {
        $gateway = new FakeS3Gateway;
        $vault = new S3DurableVault('smoke-set', $gateway);

        self::assertInstanceOf(S3DurableVault::class, $vault);
        self::assertInstanceOf(S3GatewayContract::class, $vault->gateway());
        self::assertSame($gateway, $vault->gateway(), 'the vault writes through the injected gateway, never a private one');

        $plainHash = hash('sha256', 'plaintext');
        $wireHash = hash('sha256', 'ciphertext');
        $vault->putChunk($wireHash, 'ciphertext');

        $vault->putManifest(json_encode([
            'generation' => 1,
            'created' => '2026-09-24T00:00:00+00:00',
            'files' => [['name' => 'smoke.bin', 'size' => 3]],
            'chunk_hashes' => [$plainHash],
            'wire_chunks' => [$wireHash => $plainHash],
        ], JSON_THROW_ON_ERROR));

        $keys = array_keys($gateway->listAsMap());

        self::assertCount(0, array_filter(
            $keys,
            static fn (string $key): bool => ! str_starts_with($key, 'teleframe-backup/smoke-set/'),
        ), 'every object is namespaced under the backup set — no stray key escapes the prefix');
        self::assertContains('teleframe-backup/smoke-set/chunks/'.$wireHash, $keys);
        self::assertNotContains(
            $plainHash,
            array_map(static fn (string $key): string => basename($key), $keys),
            'the plaintext hash must be invisible in the object NAME',
        );

        $latest = $vault->getLatestManifest();

        self::assertNotNull($latest);
        self::assertSame(1, $latest['generation']);
        self::assertSame('ciphertext', $vault->getChunk($plainHash), 'a reader resolves the PLAINTEXT address the manifest records');

        $missingRefused = false;
        try {
            $vault->getChunk(hash('sha256', 'absent'));
        } catch (\RuntimeException) {
            $missingRefused = true;
        }
        self::assertTrue($missingRefused, 'a missing chunk is a loud RuntimeException, never an empty string');
    }
}

/**
 * A PSR-16 store in one array, so CachePendingLoginRegistry can be proved to
 * construct AND round-trip a marker with no framework and no Redis present.
 */
final class NoopCache implements CacheInterface
{
    /** @var array<string, mixed> */
    private array $entries = [];

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->entries[$key] ?? $default;
    }

    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        $this->entries[$key] = $value;

        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->entries[$key]);

        return true;
    }

    public function clear(): bool
    {
        $this->entries = [];

        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        return [];
    }

    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        return true;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->entries);
    }
}
