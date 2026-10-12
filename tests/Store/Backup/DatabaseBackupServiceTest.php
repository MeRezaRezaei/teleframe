<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store\Backup;

use InvalidArgumentException;
use MeRezaRezaei\Teleframe\Handler\InMemoryCache;
use MeRezaRezaei\Teleframe\Notify\NoShowWatchdog;
use MeRezaRezaei\Teleframe\Notify\WatchState;
use MeRezaRezaei\Teleframe\Store\Backup\BackupException;
use MeRezaRezaei\Teleframe\Store\Backup\BackupRunIndexContract;
use MeRezaRezaei\Teleframe\Store\Backup\BackupRunReport;
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
use MeRezaRezaei\Teleframe\Tests\Support\FakeStoreApi;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * DatabaseBackupService (2026-09-24 backup-store remainder design §2/§4/§5)
 * — the FULL offline chain, every port faked except the real orchestrator:
 *
 *   fake DatabaseDumper (anonymous class writing a real spool through the
 *   workspace, fixed startedAt → deterministic objectName)
 *   + REAL StoreService over FakeStoreApi (inline sealed put — the whole
 *     chunk/seal/upload/metadata path runs)
 *   + MemoryBackupRunIndex ledger
 *   + a REAL NoShowWatchdog over an in-memory PSR-16 cache (spied through
 *     lastSeen('backup'))
 *   + NullLogger (silent).
 *
 * Asserts the design's observable contract: the put is sealed and carries
 * 'application/sql' (read back from the object row's manifest metadata);
 * the run row object_name is '{db}-{Y-m-d-His}.sql'; the heartbeat lands
 * after success; a dumper failure rethrows AND never touches the
 * heartbeat; sealed + null passphrase → put() throws loud (InvalidArgument
 * Exception) BEFORE any run row exists — the slice never invents a
 * default secret.
 */
final class DatabaseBackupServiceTest extends TestCase
{
    private const ACCOUNT_ID = 7;

    private const BUCKET = 'teleframe-backup:default';

    /** Fixed identity moment → deterministic '{database}-{Y-m-d-His}.sql'. */
    public const STARTED_AT = 1700000000; // 2023-11-14 22:13:20 UTC

    private FakeStoreApi $api;

    private MemoryStoreIndex $index;

    private MemoryBackupRunIndex $runs;

    private InMemoryCache $cache;

    private NoShowWatchdog $watchdog;

    protected function setUp(): void
    {
        $this->api = new FakeStoreApi(scope: 'backup');
        $this->index = new MemoryStoreIndex;
        $this->runs = new MemoryBackupRunIndex;
        $this->cache = new InMemoryCache;
        $this->watchdog = new NoShowWatchdog(new WatchState($this->cache, 'teleframe.notify.watch.'));
    }

    public function test_full_chain_sealed_put_run_row_and_heartbeat(): void
    {
        $dumpBytes = "CREATE TABLE app (id int);\n";
        $service = $this->service($this->succeedingDumper($dumpBytes));

        $report = $service->backup($this->target(), false);

        self::assertInstanceOf(BackupRunReport::class, $report);
        self::assertSame($this->objectName(), $report->name);
        self::assertSame(self::BUCKET, $report->bucket);
        self::assertSame(strlen($dumpBytes), $report->bytes);
        self::assertSame('sealed', $report->mode);
        self::assertFalse($report->queued);
        self::assertNotNull($report->heartbeatAt);

        // put was called sealed with 'application/sql' — read back from the
        // persisted object row's manifest metadata (the put's observable truth).
        $object = $this->index->findObject(self::ACCOUNT_ID, self::BUCKET, $this->objectName());
        self::assertNotNull($object);
        self::assertSame('sealed', $object['mode']);
        self::assertSame('application/sql', $object['metadata']['mime']);

        // The ledger row mirrors the observed put result.
        $rows = $this->runs->listRuns(self::ACCOUNT_ID, null, 10);
        self::assertCount(1, $rows);
        self::assertSame($this->objectName(), $rows[0]['object_name']);
        self::assertSame('sealed', $rows[0]['mode']);
        self::assertSame('ok', $rows[0]['status']);
        self::assertSame('mysql', $rows[0]['driver']);
        self::assertSame('appdb', $rows[0]['database']);
        self::assertSame(strlen($dumpBytes), $rows[0]['bytes']);

        // Heartbeat touched on SUCCESS only.
        self::assertNotNull($this->watchdog->lastSeen('backup'));

        // Inline runs release (unlink) the spool — no tfbk-* leak.
        $spoolDir = (new DumpWorkspace)->dir();
        self::assertSame([], array_values(array_filter(
            glob($spoolDir.'/tfbk-*') ?: [],
            static fn (string $path): bool => is_file($path),
        )));
    }

    public function test_dumper_failure_rethrows_and_never_touches_the_heartbeat(): void
    {
        $service = $this->service(new class implements DatabaseDumper
        {
            public function dump(DatabaseTarget $target, DumpWorkspace $workspace): DumpResult
            {
                throw new BackupException('mysqldump failed (exit 2): boom');
            }
        });

        try {
            $service->backup($this->target(), false);
            self::fail('expected BackupException to rethrow');
        } catch (BackupException $e) {
            self::assertStringContainsString('boom', $e->getMessage());
        }

        // The no-show alert is the point: a failed run must NOT touch the
        // heartbeat, and must not leave a ledger row or a spool behind.
        self::assertNull($this->watchdog->lastSeen('backup'));
        self::assertSame([], $this->runs->listRuns(self::ACCOUNT_ID, null, 10));
    }

    public function test_sealed_with_null_passphrase_throws_loud_before_any_run_row(): void
    {
        // The slice never invents a default secret: null passphrase is
        // forwarded to put(), which throws its own loud sealed-empty error
        // BEFORE any run row, heartbeat or ledger write.
        $service = $this->service($this->succeedingDumper("CREATE TABLE t (id int);\n"), passphrase: null);

        try {
            $service->backup($this->target(), false);
            self::fail('expected InvalidArgumentException for a sealed put without a passphrase');
        } catch (InvalidArgumentException $e) {
            self::assertStringContainsString('sealed objects require a passphrase', $e->getMessage());
        }

        self::assertNull($this->watchdog->lastSeen('backup'));
        self::assertSame([], $this->runs->listRuns(self::ACCOUNT_ID, null, 10));
        self::assertNull($this->index->findObject(self::ACCOUNT_ID, self::BUCKET, $this->objectName()));
    }

    public function test_runs_returns_the_ledger_through_the_service(): void
    {
        $dumpBytes = "CREATE TABLE app (id int);\n";
        $service = $this->service($this->succeedingDumper($dumpBytes));
        $service->backup($this->target(), false);

        $rows = $service->runs('', 10);

        self::assertCount(1, $rows);
        self::assertSame($this->objectName(), $rows[0]['object_name']);
    }

    /** @param  BackupRunIndexContract|null  $runs  injected ledger override */
    private function service(DatabaseDumper $dumper, ?string $passphrase = 's3cret', ?NoShowWatchdog $watchdog = null, ?BackupRunIndexContract $runs = null, ?LoggerInterface $logger = null): DatabaseBackupService
    {
        $store = new StoreService(
            accountId: self::ACCOUNT_ID,
            channelFactory: fn (string $bucket): TelegramStoreChannel => new TelegramStoreChannel($bucket, $this->api->apiMap()),
            index: $this->index,
            cache: new MemoryHotCache(ttlSeconds: 600, maxBytes: 8 * 1024 * 1024),
            queue: new SyncUploadQueue,
        );

        return new DatabaseBackupService(
            accountId: self::ACCOUNT_ID,
            dumper: $dumper,
            store: $store,
            runs: $runs ?? $this->runs,
            watchdog: $watchdog ?? $this->watchdog,
            logger: $logger ?? new NullLogger,
            passphrase: $passphrase,
            bucket: self::BUCKET,
        );
    }

    private function target(): DatabaseTarget
    {
        return new DatabaseTarget('mysql', 'appdb', host: 'db.internal', user: 'backup');
    }

    /** The deterministic object name for STARTED_AT — 'appdb-20231114-221320.sql'. */
    private function objectName(): string
    {
        return 'appdb-'.gmdate('Y-m-d-His', self::STARTED_AT).'.sql';
    }

    /**
     * The fake child: writes the dump bytes into a REAL workspace spool
     * (so the real StoreService put path reads a seekable file) and
     * returns the observed DumpResult with the fixed startedAt.
     */
    private function succeedingDumper(string $dumpBytes): DatabaseDumper
    {
        return new class($dumpBytes) implements DatabaseDumper
        {
            public function __construct(private readonly string $dumpBytes) {}

            public function dump(DatabaseTarget $target, DumpWorkspace $workspace): DumpResult
            {
                ['path' => $path, 'handle' => $handle] = $workspace->spool();
                fwrite($handle, $this->dumpBytes);
                fclose($handle);

                return new DumpResult(
                    path: $path,
                    bytes: strlen($this->dumpBytes),
                    driver: $target->driver,
                    database: $target->database,
                    startedAt: DatabaseBackupServiceTest::STARTED_AT,
                    finishedAt: DatabaseBackupServiceTest::STARTED_AT + 3,
                    exitCode: 0,
                    stderrTail: '',
                );
            }
        };
    }
}
