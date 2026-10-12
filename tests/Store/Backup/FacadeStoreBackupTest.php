<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store\Backup;

use Illuminate\Support\Facades\Facade;
use MeRezaRezaei\Teleframe\Handler\InMemoryCache;
use MeRezaRezaei\Teleframe\Laravel\Facades\StoreBackup;
use MeRezaRezaei\Teleframe\Notify\NoShowWatchdog;
use MeRezaRezaei\Teleframe\Notify\WatchState;
use MeRezaRezaei\Teleframe\Store\Backup\BackupRunReport;
use MeRezaRezaei\Teleframe\Store\Backup\BackupSupport;
use MeRezaRezaei\Teleframe\Store\Backup\DatabaseBackupService;
use MeRezaRezaei\Teleframe\Store\Backup\DatabaseDumper;
use MeRezaRezaei\Teleframe\Store\Backup\DatabaseTarget;
use MeRezaRezaei\Teleframe\Store\Backup\DumpResult;
use MeRezaRezaei\Teleframe\Store\Backup\DumpWorkspace;
use MeRezaRezaei\Teleframe\Store\Backup\MemoryBackupRunIndex;
use MeRezaRezaei\Teleframe\Store\Cache\MemoryHotCache;
use MeRezaRezaei\Teleframe\Store\Index\MemoryStoreIndex;
use MeRezaRezaei\Teleframe\Store\Queue\SyncUploadQueue;
use MeRezaRezaei\Teleframe\Store\StoreService;
use MeRezaRezaei\Teleframe\Store\TelegramStoreChannel;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;
use MeRezaRezaei\Teleframe\Tests\Support\FakeStoreApi;
use Psr\Log\NullLogger;

/**
 * StoreBackup facade (design 2026-09-24 §7) — mirror of TStoreFacadeTest:
 * the facade resolves the DatabaseBackupService bound under
 * BackupSupport::SERVICE_KEY (a rebindable container seam), and the static
 * backup()/runs()/service() forward onto it. `backup()` with no target
 * resolves the container-bound DatabaseTarget::class — the same
 * config-built default the `teleframe:tstore backup` CLI uses.
 *
 * The seams are bound to the SAME offline doubles as
 * DatabaseBackupServiceTest: a real StoreService over FakeStoreApi, a
 * memory run ledger, a recording dumper, and a real watchdog over a
 * memory PSR-16 cache — no credentials, no I/O beyond the spool.
 */
final class FacadeStoreBackupTest extends TestCase
{
    public const STARTED_AT = 1700000000;

    private const BUCKET = 'teleframe-backup:default';

    private FakeStoreApi $api;

    private MemoryStoreIndex $index;

    private MemoryBackupRunIndex $runs;

    /** Object names the recording dumper produced (shared spy). */
    private \stdClass $dumpLog;

    protected function setUp(): void
    {
        parent::setUp();

        // The facade root is cached statically per accessor across tests in
        // the same process — a stale root from a previous app would break
        // assertSame against THIS app's binding.
        Facade::clearResolvedInstances();

        $this->api = new FakeStoreApi(scope: 'facade');
        $this->index = new MemoryStoreIndex;
        $this->runs = new MemoryBackupRunIndex;

        $this->dumpLog = new \stdClass;
        $this->dumpLog->names = [];
        $dumpLog = $this->dumpLog;

        $dumper = new class($dumpLog) implements DatabaseDumper
        {
            public function __construct(private \stdClass $log) {}

            public function dump(DatabaseTarget $target, DumpWorkspace $workspace): DumpResult
            {
                ['path' => $path, 'handle' => $handle] = $workspace->spool();
                $bytes = "CREATE TABLE app (id int);\n";
                fwrite($handle, $bytes);
                fclose($handle);

                $result = new DumpResult(
                    path: $path,
                    bytes: strlen($bytes),
                    driver: $target->driver,
                    database: $target->database,
                    startedAt: FacadeStoreBackupTest::STARTED_AT,
                    finishedAt: FacadeStoreBackupTest::STARTED_AT + 1,
                    exitCode: 0,
                    stderrTail: '',
                );
                $this->log->names[] = $result->objectName();

                return $result;
            }
        };

        $store = new StoreService(
            accountId: 7,
            channelFactory: fn (string $bucket): TelegramStoreChannel => new TelegramStoreChannel($bucket, $this->api->apiMap()),
            index: $this->index,
            cache: new MemoryHotCache(ttlSeconds: 600, maxBytes: 8 * 1024 * 1024),
            queue: new SyncUploadQueue,
        );

        $watchdog = new NoShowWatchdog(new WatchState(new InMemoryCache, 'teleframe.notify.watch.'));

        $this->app->instance(BackupSupport::SERVICE_KEY, new DatabaseBackupService(
            accountId: 7,
            dumper: $dumper,
            store: $store,
            runs: $this->runs,
            watchdog: $watchdog,
            logger: new NullLogger,
            passphrase: 'facade-pass',
            bucket: self::BUCKET,
        ));

        // The facade's backup(null) resolves the default target from the
        // container (the provider binds it from teleframe.store.backup.
        // database.*; here the test binds a fake).
        $this->app->instance(DatabaseTarget::class, new DatabaseTarget('mysql', 'appdb', host: 'db.internal'));
    }

    public function test_facade_resolves_the_bound_backup_service(): void
    {
        $root = StoreBackup::getFacadeRoot();

        self::assertInstanceOf(DatabaseBackupService::class, $root);
        self::assertSame($root, $this->app->make(BackupSupport::SERVICE_KEY));
    }

    public function test_static_backup_forwards_and_uses_the_default_target(): void
    {
        $report = StoreBackup::backup();

        self::assertInstanceOf(BackupRunReport::class, $report);
        self::assertSame($this->objectName(), $report->name);
        self::assertSame(self::BUCKET, $report->bucket);
        self::assertTrue(in_array($this->objectName(), $this->dumpLog->names, true));

        $rows = StoreBackup::runs();
        self::assertCount(1, $rows);
        self::assertSame($this->objectName(), $rows[0]['object_name']);
    }

    public function test_static_runs_forwards_to_the_service_root(): void
    {
        // Seed one run through the service root directly.
        StoreBackup::service()->backup(new DatabaseTarget('mysql', 'appdb'), false);

        $rows = StoreBackup::runs(self::BUCKET, 10);

        self::assertCount(1, $rows);
        self::assertSame($this->objectName(), $rows[0]['object_name']);
    }

    public function test_static_service_returns_the_same_resolved_root(): void
    {
        self::assertSame(StoreBackup::getFacadeRoot(), StoreBackup::service());
    }

    private function objectName(): string
    {
        return 'appdb-'.gmdate('Y-m-d-His', self::STARTED_AT).'.sql';
    }
}
