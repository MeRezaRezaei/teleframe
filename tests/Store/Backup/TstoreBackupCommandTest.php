<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store\Backup;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use MeRezaRezaei\Teleframe\Handler\InMemoryCache;
use MeRezaRezaei\Teleframe\Laravel\Console\TstoreCommand;
use MeRezaRezaei\Teleframe\Notify\NoShowWatchdog;
use MeRezaRezaei\Teleframe\Notify\WatchState;
use MeRezaRezaei\Teleframe\Store\Backup\BackupException;
use MeRezaRezaei\Teleframe\Store\Backup\BackupScheduleSpec;
use MeRezaRezaei\Teleframe\Store\Backup\BackupSupport;
use MeRezaRezaei\Teleframe\Store\Backup\DatabaseBackupService;
use MeRezaRezaei\Teleframe\Store\Backup\DatabaseDumper;
use MeRezaRezaei\Teleframe\Store\Backup\DatabaseTarget;
use MeRezaRezaei\Teleframe\Store\Backup\DumpResult;
use MeRezaRezaei\Teleframe\Store\Backup\DumpWorkspace;
use MeRezaRezaei\Teleframe\Store\Backup\MemoryBackupRunIndex;
use MeRezaRezaei\Teleframe\Store\Backup\TemporalBackupScheduler;
use MeRezaRezaei\Teleframe\Store\Cache\MemoryHotCache;
use MeRezaRezaei\Teleframe\Store\Index\MemoryStoreIndex;
use MeRezaRezaei\Teleframe\Store\Queue\SyncUploadQueue;
use MeRezaRezaei\Teleframe\Store\Schedules\NullSchedulePublisher;
use MeRezaRezaei\Teleframe\Store\StoreService;
use MeRezaRezaei\Teleframe\Store\TelegramStoreChannel;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;
use MeRezaRezaei\Teleframe\Tests\Support\FakeStoreApi;
use Psr\Log\NullLogger;

/**
 * `teleframe:tstore backup|schedule` (design 2026-09-24 §7) — the store
 * backup CLI face, mirroring TstoreCommandTest: the tstore seams AND the
 * store-backup seams (BackupSupport::SERVICE_KEY, DatabaseTarget::class,
 * BackupSupport::WATCHDOG_KEY) are bound to offline doubles, so both new
 * actions run without credentials.
 *
 * `backup` succeeds against the bound seams (real StoreService over
 * FakeStoreApi + a recording dumper spooling through the workspace) and
 * fails loud when the dumper throws (BackupException → RuntimeException
 * catch → FAILURE, never a silent drop); `schedule` prints the
 * BackupScheduleSpec publisher posture + the watchdog registration state.
 */
final class TstoreBackupCommandTest extends TestCase
{
    private const ACCOUNT_ID = 7;

    private const BUCKET = 'teleframe-backup:default';

    private FakeStoreApi $api;

    private MemoryStoreIndex $index;

    private MemoryBackupRunIndex $runs;

    private NoShowWatchdog $watchdog;

    protected function setUp(): void
    {
        parent::setUp();

        $this->api = new FakeStoreApi(scope: 'cli-backup');
        $this->index = new MemoryStoreIndex;
        $this->runs = new MemoryBackupRunIndex;
        $this->watchdog = new NoShowWatchdog(new WatchState(new InMemoryCache, 'teleframe.notify.watch.'));
        $this->watchdog->register('backup', 7200);

        $this->app->make(Kernel::class)->registerCommand(new TstoreCommand);

        $this->app->bind(TstoreCommand::CHANNEL_FACTORY_KEY, fn (string $bucket): TelegramStoreChannel => new TelegramStoreChannel($bucket, $this->api->apiMap()));
        $this->app->instance(TstoreCommand::INDEX_KEY, $this->index);
        $this->app->instance(TstoreCommand::HOT_CACHE_KEY, new MemoryHotCache(ttlSeconds: 600, maxBytes: 8 * 1024 * 1024));
        $this->app->instance(TstoreCommand::QUEUE_KEY, new SyncUploadQueue);
        $this->app->instance(TstoreCommand::SCHEDULES_KEY, new NullSchedulePublisher);
        $this->app->instance(TstoreCommand::SERVICE_KEY, $this->store());

        // Store-backup seams (the provider binds these; the test fakes
        // them): the config-built default target + the orchestrator + the
        // watchdog the `schedule` action inspects.
        $this->app->instance(DatabaseTarget::class, new DatabaseTarget('mysql', 'appdb', host: 'db.internal', user: 'backup'));
        $this->app->instance(BackupSupport::WATCHDOG_KEY, $this->watchdog);
        $this->app->instance(BackupSupport::SERVICE_KEY, $this->backupService($this->succeedingDumper()));
    }

    public function test_backup_succeeds_against_the_bound_seams(): void
    {
        $exit = Artisan::call('teleframe:tstore', ['action' => 'backup']);
        // Artisan::output() drains the buffer (BufferedOutput::fetch()) —
        // capture once, assert many.
        $output = Artisan::output();

        self::assertSame(TstoreCommand::SUCCESS, $exit);
        self::assertStringContainsString('Backup stored: appdb-', $output);
        self::assertStringContainsString('teleframe-backup:default', $output);
        self::assertStringContainsString('sealed', $output);

        // The run-ledger row exists (dump → put → index → heartbeat).
        $rows = $this->runs->listRuns(self::ACCOUNT_ID, null, 10);
        self::assertCount(1, $rows);
        self::assertSame('ok', $rows[0]['status']);
        self::assertSame('sealed', $rows[0]['mode']);
        self::assertNotNull($this->watchdog->lastSeen('backup'));
    }

    public function test_backup_errors_loud_when_the_dumper_fails(): void
    {
        // Rebind the orchestrator with a failing dumper => the CLI must
        // fail loud (BackupException is a RuntimeException; the command
        // prints the message and returns FAILURE), never a silent drop.
        $this->app->instance(BackupSupport::SERVICE_KEY, $this->backupService(new class implements DatabaseDumper
        {
            public function dump(DatabaseTarget $target, DumpWorkspace $workspace): DumpResult
            {
                throw new BackupException('mysqldump failed (exit 2): connection refused');
            }
        }));

        $exit = Artisan::call('teleframe:tstore', ['action' => 'backup']);
        $output = Artisan::output();

        self::assertSame(TstoreCommand::FAILURE, $exit);
        self::assertStringContainsString('mysqldump failed (exit 2): connection refused', $output);
        // THE LEDGER IS NOT EMPTY AND THAT IS THE FIX, not a regression. It used
        // to assert zero rows, which was correct for the order the service used to
        // run in — dump, put, ledger — and wrong for what happens when a run dies
        // AFTER the upload: the dump had already reached the channel and the ledger
        // INSERT then failed, leaving an orphaned remote copy with no local record
        // and no way to find it (goal 46.87 item 4).
        //
        // The service now CLAIMS the run before it uploads, so a run that started
        // and did not finish is DISCOVERABLE: one row, status `running`, zero
        // observed bytes. That is the honest record of what happened, and a host
        // reconciles against it. Asserting zero rows again would restore exactly
        // the blindness this change removed.
        $claimed = $this->runs->listRuns(self::ACCOUNT_ID, null, 10);
        self::assertCount(1, $claimed, 'the claim outlives the failure, so the run is findable');
        self::assertSame('running', $claimed[0]['status']);
        self::assertSame(0, (int) $claimed[0]['bytes'], 'nothing was observed, so nothing is claimed');

    }

    public function test_schedule_prints_publisher_and_watchdog_registration_posture(): void
    {
        $exit = Artisan::call('teleframe:tstore', ['action' => 'schedule']);
        $output = Artisan::output();

        self::assertSame(TstoreCommand::SUCCESS, $exit);

        // Publisher posture: the null driver + the Temporal switch path.
        self::assertStringContainsString('Schedule driver: null', $output);
        self::assertStringContainsString('teleclient.store.backup.schedules=temporal', $output);
        self::assertStringContainsString('teleframe:tstore backup', $output);

        // Watchdog posture: the 'backup' topic is registered (expect 7200
        // s = the test's registration) and never seen yet → grace period.
        self::assertStringContainsString('Watchdog: "backup" registered', $output);
        self::assertStringContainsString('expectEverySeconds 7200', $output);
        self::assertStringContainsString('never (grace period)', $output);
    }

    public function test_backup_prune_keeps_the_newest_runs(): void
    {
        // Wave-2-D retention (design §6): --prune=N keeps the newest N runs
        // in the run bucket. Seed two distinct runs (sequenced dumper →
        // unique startedAt per dump → unique object names), then a third
        // backup with --prune=1 must delete the two OLDEST ledger rows AND
        // their Telegram objects, keeping only the newest run.
        $this->app->instance(BackupSupport::SERVICE_KEY, $this->backupService($this->sequencedDumper()));

        // pruneRuns() resolves the run-ledger seam itself: unbound, the
        // container would throw (BindingResolutionException → FAILURE).
        $this->app->instance(BackupSupport::INDEX_KEY, $this->runs);

        self::assertSame(TstoreCommand::SUCCESS, Artisan::call('teleframe:tstore', ['action' => 'backup']));
        self::assertSame(TstoreCommand::SUCCESS, Artisan::call('teleframe:tstore', ['action' => 'backup']));

        $exit = Artisan::call('teleframe:tstore', ['action' => 'backup', '--prune' => '1']);
        $output = Artisan::output();

        self::assertSame(TstoreCommand::SUCCESS, $exit);
        self::assertStringContainsString('Pruned 2 run(s) — kept the newest 1 in teleframe-backup:default.', $output);

        // The ledger keeps exactly the newest run (the third backup) …
        //
        // THE `id` ASSERTION MOVED, and the reason is worth keeping. It used to
        // assert `id === 3` — one surrogate key per run. `DatabaseBackupService`
        // now CLAIMS a run in the ledger BEFORE it uploads anything (goal 46.87
        // item 4: an unregistered account used to fail its INSERT *after* the
        // dump had already reached the channel), which means two writes per run
        // and the counter advances twice. Asserting on the surrogate couples this
        // test to how many times the ledger is written, which is an
        // implementation detail. What matters is that the row kept is the one the
        // surviving OBJECT came from — so that is what is asserted, and it is a
        // stronger claim: it ties the ledger to the wire rather than to a counter.
        $rows = $this->runs->listRuns(self::ACCOUNT_ID, null, 10);
        self::assertCount(1, $rows);
        self::assertSame('ok', $rows[0]['status']);

        // … and the store keeps exactly that run's object (the two oldest
        // objects were deleteMessages'd off the wire + dropped from the index).
        $objects = $this->index->listObjects(self::ACCOUNT_ID, self::BUCKET);
        self::assertCount(1, $objects);

        // The one ledger row is the one the one surviving object came from —
        // asserted across the two indexes rather than by the ledger's surrogate
        // id, which the claim write advances twice per run.
        self::assertSame($rows[0]['object_name'], $objects[0]['name']);
    }

    public function test_backup_prune_rejects_non_numeric_keep(): void
    {
        // ctype_digit guard: --prune=abc must fail loud with the exact
        // message (the dump itself already ran — command-level validation
        // happens after the backup step).
        $exit = Artisan::call('teleframe:tstore', ['action' => 'backup', '--prune' => 'abc']);
        $output = Artisan::output();

        self::assertSame(TstoreCommand::FAILURE, $exit);
        self::assertStringContainsString('--prune must be a non-negative integer', $output);
    }

    public function test_schedule_prints_the_bound_scheduler_publisher_posture(): void
    {
        // Wave-2-D: when the TemporalBackupScheduler seam is bound, the
        // schedule action prints 'Scheduler: ' + the publisher describe()
        // (thin adapter → NullSchedulePublisher posture here) AFTER the
        // plain BackupScheduleSpec describe().
        $this->app->instance(BackupSupport::SCHEDULER_KEY, new TemporalBackupScheduler(
            new NullSchedulePublisher,
            new BackupScheduleSpec(86400),
        ));

        $exit = Artisan::call('teleframe:tstore', ['action' => 'schedule']);
        $output = Artisan::output();

        self::assertSame(TstoreCommand::SUCCESS, $exit);
        // The publisher text no longer opens with "tstore maintenance schedules
        // are off": NullSchedulePublisher is bound at the tstore, app and backup
        // seams alike, and its describe() used to name config keys NOTHING reads
        // (teleframe.tstore.schedules, hardcoded off at
        // TeleframeServiceProvider.php:667). It now names every driver a
        // provider really reads.
        self::assertStringContainsString('Scheduler: Schedule driver: null. Nothing is scheduled', $output);
        self::assertStringContainsString('teleframe.store.backup.schedule.publisher=temporal', $output);
        // The ABSENCE assertion below used to be the invariant "this text must
        // not name a key with no armed seam behind it" — which is why
        // teleframe.tstore.schedules was withheld. That reason is gone: the
        // tstore seam now has an arming point (`teleframe:tstore schedules
        // --install`, StoreMaintenanceScheduleSpec) and the notify seam has one
        // too (`teleframe:notify schedules --install`), so the text names both.
        // What must still hold is that a named key is a READ key and has an
        // arming command — checked machine-side in
        // tests/Store/SchedulePublisherDescriptionTest.
        self::assertStringContainsString('teleframe.tstore.schedules=temporal', $output);
        self::assertStringContainsString('teleframe:tstore schedules --install', $output);
        // And the seam being described here is the BACKUP one, so the arming
        // command must be the backup action, not the maintenance one.
        self::assertStringContainsString('teleframe.store.backup.schedule.publisher=temporal, then teleframe:tstore schedule ', $output);
        self::assertStringContainsString('Schedule driver: null. Store backup schedules are off.', $output);
    }

    private function store(): StoreService
    {
        return new StoreService(
            accountId: self::ACCOUNT_ID,
            channelFactory: fn (string $bucket): TelegramStoreChannel => new TelegramStoreChannel($bucket, $this->api->apiMap()),
            index: $this->index,
            cache: new MemoryHotCache(ttlSeconds: 600, maxBytes: 8 * 1024 * 1024),
            queue: new SyncUploadQueue,
        );
    }

    private function backupService(DatabaseDumper $dumper): DatabaseBackupService
    {
        return new DatabaseBackupService(
            accountId: self::ACCOUNT_ID,
            dumper: $dumper,
            store: $this->store(),
            runs: $this->runs,
            watchdog: $this->watchdog,
            logger: new NullLogger,
            passphrase: 'cli-backup-pass',
            bucket: self::BUCKET,
        );
    }

    /** The fake child: spools its dump bytes through the real workspace. */
    private function succeedingDumper(): DatabaseDumper
    {
        return new class implements DatabaseDumper
        {
            public function dump(DatabaseTarget $target, DumpWorkspace $workspace): DumpResult
            {
                ['path' => $path, 'handle' => $handle] = $workspace->spool();
                $bytes = "CREATE TABLE app (id int);\n";
                fwrite($handle, $bytes);
                fclose($handle);
                $startedAt = 1700000000;

                return new DumpResult(
                    path: $path,
                    bytes: strlen($bytes),
                    driver: $target->driver,
                    database: $target->database,
                    startedAt: $startedAt,
                    finishedAt: $startedAt + 1,
                    exitCode: 0,
                    stderrTail: '',
                );
            }
        };
    }

    /**
     * Like succeedingDumper(), but startedAt advances per dump() call so
     * each run gets a UNIQUE object name ({database}-{Y-m-d-His}.sql) —
     * the prune tests need multiple distinct runs in the ledger.
     */
    private function sequencedDumper(): DatabaseDumper
    {
        return new class implements DatabaseDumper
        {
            private int $startedAt = 1700000000;

            public function dump(DatabaseTarget $target, DumpWorkspace $workspace): DumpResult
            {
                ['path' => $path, 'handle' => $handle] = $workspace->spool();
                $bytes = "CREATE TABLE app (id int);\n";
                fwrite($handle, $bytes);
                fclose($handle);
                $startedAt = $this->startedAt++;

                return new DumpResult(
                    path: $path,
                    bytes: strlen($bytes),
                    driver: $target->driver,
                    database: $target->database,
                    startedAt: $startedAt,
                    finishedAt: $startedAt + 1,
                    exitCode: 0,
                    stderrTail: '',
                );
            }
        };
    }
}
