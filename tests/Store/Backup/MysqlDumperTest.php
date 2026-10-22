<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store\Backup;

use MeRezaRezaei\Teleframe\Handler\InMemoryCache;
use MeRezaRezaei\Teleframe\Notify\NoShowWatchdog;
use MeRezaRezaei\Teleframe\Notify\WatchState;
use MeRezaRezaei\Teleframe\Store\Backup\BackupException;
use MeRezaRezaei\Teleframe\Store\Backup\DatabaseBackupService;
use MeRezaRezaei\Teleframe\Store\Backup\DatabaseTarget;
use MeRezaRezaei\Teleframe\Store\Backup\DumpWorkspace;
use MeRezaRezaei\Teleframe\Store\Backup\MemoryBackupRunIndex;
use MeRezaRezaei\Teleframe\Store\Backup\MysqlDumper;
use MeRezaRezaei\Teleframe\Store\Cache\MemoryHotCache;
use MeRezaRezaei\Teleframe\Store\Index\MemoryStoreIndex;
use MeRezaRezaei\Teleframe\Store\Queue\SyncUploadQueue;
use MeRezaRezaei\Teleframe\Store\StoreService;
use MeRezaRezaei\Teleframe\Store\TelegramStoreChannel;
use MeRezaRezaei\Teleframe\Tests\Support\FakeStoreApi;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * MysqlDumper (2026-09-24 backup-store remainder design §2/§3/§9) —
 * offline through the injectable proc runner. The fake runner is the
 * process stand-in: it receives (argv, env, cwd) where cwd is the spool
 * DIRECTORY (the dumper streams the child stdout there), so the fake
 * writes its dump bytes into the tfbk-* spool file itself and returns
 * exit+stderr through the contract. The real dump() path is exercised:
 * spool created, argv/env shaped ps-safe, exit-checked, and every
 * non-zero exit raises BackupException carrying the capped 4 KiB stderr
 * tail. probe() gates the binary: missing → null.
 *
 * Zero regex, no live credentials, no live MySQL.
 */
final class MysqlDumperTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/tf-mysqldump-'.bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir.'/tfbk-*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->dir);
    }

    private function workspace(): DumpWorkspace
    {
        return new DumpWorkspace($this->dir);
    }

    /** Find the spool file the workspace created (exactly one exists). */
    private function spoolPath(): string
    {
        $files = glob($this->dir.'/tfbk-*') ?: [];
        self::assertCount(1, $files, 'expected exactly one spool file');

        return $files[0];
    }

    public function test_success_writes_the_spool_and_returns_the_observed_result(): void
    {
        $argvSeen = null;
        $envSeen = null;

        // The fake child: write dump bytes into the spool tempnam the
        // dumper created in $cwd, then report success.
        $runner = function (array $argv, array $env, string $cwd) use (&$argvSeen, &$envSeen): array {
            $argvSeen = $argv;
            $envSeen = $env;
            $files = glob($cwd.'/tfbk-*') ?: [];
            if (count($files) === 1) {
                file_put_contents($files[0], "-- MySQL dump 8.0\nCREATE TABLE t (id int);\n");
            }

            return ['exit' => 0, 'stderr' => ''];
        };

        $target = new DatabaseTarget('mysql', 'appdb', host: 'db.internal', port: 3307, user: 'backup');
        $result = (new MysqlDumper($runner))->dump($target, $this->workspace());

        // argv = binary + the target's driver-shaped non-secret flags.
        self::assertSame('mysqldump', $argvSeen[0]);
        self::assertContains('--single-transaction', $argvSeen);
        self::assertContains('--quick', $argvSeen);
        self::assertContains('--no-tablespaces', $argvSeen);
        self::assertContains('--host=db.internal', $argvSeen);
        self::assertContains('--port=3307', $argvSeen);
        self::assertContains('--user=backup', $argvSeen);
        self::assertSame('appdb', $argvSeen[count($argvSeen) - 1]);

        // env = the target's base env; the password key is filled by the
        // dumper from the HOST environment (never argv, never the target).
        self::assertArrayHasKey('MYSQL_PWD', $envSeen);

        $dumpBytes = strlen("-- MySQL dump 8.0\nCREATE TABLE t (id int);\n");
        self::assertSame($this->spoolPath(), $result->path);
        self::assertSame($dumpBytes, $result->bytes);
        self::assertSame('mysql', $result->driver);
        self::assertSame('appdb', $result->database);
        self::assertSame(0, $result->exitCode);
        self::assertSame('', $result->stderrTail);
        self::assertSame('appdb-'.gmdate('Y-m-d-His', $result->startedAt).'.sql', $result->objectName());
    }

    public function test_non_zero_exit_throws_backup_exception_with_the_capped_stderr_tail(): void
    {
        $runner = static function (array $argv, array $env, string $cwd): array {
            return ['exit' => 2, 'stderr' => str_repeat('E', 8192).'TAIL-MARK'];
        };

        try {
            (new MysqlDumper($runner))->dump(new DatabaseTarget('mysql', 'appdb'), $this->workspace());
            self::fail('expected BackupException');
        } catch (BackupException $e) {
            // Last 4 KiB of stderr, byte-safe (str_repeat + marker).
            self::assertStringStartsWith('mysqldump failed (exit 2): ', $e->getMessage());
            self::assertStringEndsWith('TAIL-MARK', $e->getMessage());
            // The tail is capped: only the final 4096 bytes survive.
            $tail = substr($e->getMessage(), strlen('mysqldump failed (exit 2): '));
            self::assertSame(4096, strlen($tail));
        }
    }

    /**
     * DEFECT-2, unit level: exit 0 with NOTHING in the spool is a failure.
     *
     * The fake child is the exact shape of the real accident: a clean exit and
     * no bytes — what mysqldump does against an empty database, or with a
     * `tables` whitelist matching nothing.
     */
    public function test_a_zero_byte_dump_on_exit_zero_throws_rather_than_returning(): void
    {
        $runner = static function (array $argv, array $env, string $cwd): array {
            // Deliberately writes nothing: the spool stays zero-length.
            return ['exit' => 0, 'stderr' => ''];
        };

        try {
            (new MysqlDumper($runner))->dump(new DatabaseTarget('mysql', 'appdb'), $this->workspace());
            self::fail('expected BackupException on a zero-byte dump');
        } catch (BackupException $e) {
            self::assertStringContainsString('mysqldump', $e->getMessage());
            self::assertStringContainsString('ZERO-BYTE', $e->getMessage());
            self::assertStringContainsString('appdb', $e->getMessage());
            self::assertStringContainsString('no watchdog heartbeat was taken', $e->getMessage());
        }
    }

    /**
     * NEGATIVE CONTROL: a ONE-BYTE dump is not zero and must still succeed, so
     * the guard above cannot be "reject every dump" in disguise.
     */
    public function test_a_one_byte_dump_still_succeeds(): void
    {
        $runner = static function (array $argv, array $env, string $cwd): array {
            $files = glob($cwd.'/tfbk-*') ?: [];
            if (count($files) === 1) {
                file_put_contents($files[0], '.');
            }

            return ['exit' => 0, 'stderr' => ''];
        };

        $result = (new MysqlDumper($runner))->dump(new DatabaseTarget('mysql', 'appdb'), $this->workspace());

        self::assertSame(1, $result->bytes);
        self::assertSame(0, $result->exitCode);
    }

    /**
     * DEFECT-2, END TO END — the part that actually defeated the watchdog.
     *
     * A real DatabaseBackupService (real StoreService, real ledger, real
     * NoShowWatchdog over an in-memory cache) driven by the REAL MysqlDumper
     * with a fake child that reports exit 0 and writes nothing.
     *
     * The claim being proved is not merely "the dumper throws". It is that the
     * heartbeat is NEVER TOUCHED, because a failed backup that refreshes the
     * 'backup' heartbeat is precisely the failure the no-show check exists to
     * catch — it would cancel its own alarm. Before the fix this run stored a
     * zero-byte object, wrote an 'ok' ledger row and touched the heartbeat, so
     * the alert stayed silent and the operator believed they had DB backups.
     */
    public function test_a_zero_byte_dump_never_touches_the_watchdog_heartbeat(): void
    {
        $cache = new InMemoryCache;
        $watchdog = new NoShowWatchdog(new WatchState($cache, 'teleframe.notify.watch.'));
        $runs = new MemoryBackupRunIndex;

        $service = $this->service(
            new MysqlDumper(static fn (array $argv, array $env, string $cwd): array => ['exit' => 0, 'stderr' => '']),
            $watchdog,
            $runs,
        );

        try {
            $service->backup(new DatabaseTarget('mysql', 'appdb', host: 'db.internal', user: 'backup'), false);
            self::fail('expected the zero-byte dump to abort the run');
        } catch (BackupException $e) {
            self::assertStringContainsString('ZERO-BYTE', $e->getMessage());
        }

        // The alarm stays armed: nothing claimed a backup happened.
        self::assertNull(
            $watchdog->lastSeen('backup'),
            'a zero-byte dump MUST NOT touch the backup heartbeat — that is what silences the no-show alert',
        );

        // And it left no trace a later reader could mistake for success.
        //
        // ONE row, and it is a CLAIM: status `running`, zero observed bytes. The
        // service claims the run before it uploads, so a run that started and did
        // not finish is findable rather than invisible — which is the fix for
        // goal 46.87 item 4, where a ledger INSERT failing AFTER the upload left an
        // orphaned remote copy with no local record. What must never be mistaken
        // for success is `ok`, and no row here claims it.
        $left = $runs->listRuns(7, null, 10);
        self::assertCount(1, $left);
        self::assertSame('running', $left[0]['status']);
        self::assertNotSame('ok', $left[0]['status'], 'a run that never finished must never read as ok');
        self::assertSame(0, (int) $left[0]['bytes'], 'nothing was observed, so nothing is claimed');
    }

    /**
     * NEGATIVE CONTROL for the heartbeat assertion above, and the reason that
     * assertion carries weight.
     *
     * Identical wiring, identical service, real dumper — the ONLY difference
     * is that the fake child writes a byte. The heartbeat IS then touched and
     * the ledger DOES get its row. So the previous test's `assertNull` is
     * measuring the zero-byte guard and not a watchdog that was never wired to
     * fire in the first place.
     */
    public function test_a_non_empty_dump_does_touch_the_watchdog_heartbeat(): void
    {
        $cache = new InMemoryCache;
        $watchdog = new NoShowWatchdog(new WatchState($cache, 'teleframe.notify.watch.'));
        $runs = new MemoryBackupRunIndex;

        $runner = static function (array $argv, array $env, string $cwd): array {
            $files = glob($cwd.'/tfbk-*') ?: [];
            if (count($files) === 1) {
                file_put_contents($files[0], "CREATE TABLE app (id int);\n");
            }

            return ['exit' => 0, 'stderr' => ''];
        };

        $service = $this->service(new MysqlDumper($runner), $watchdog, $runs);
        $service->backup(new DatabaseTarget('mysql', 'appdb', host: 'db.internal', user: 'backup'), false);

        self::assertNotNull(
            $watchdog->lastSeen('backup'),
            'a real dump MUST touch the heartbeat — otherwise the negative control proves nothing',
        );
        self::assertCount(1, $runs->listRuns(7, null, 10));
    }

    /**
     * The offline chain the two tests above run through: a real StoreService
     * over FakeStoreApi, a real ledger, the given watchdog. Only the dumper
     * and the child differ between them.
     */
    private function service(
        MysqlDumper $dumper,
        NoShowWatchdog $watchdog,
        MemoryBackupRunIndex $runs,
    ): DatabaseBackupService {
        $api = new FakeStoreApi(scope: 'backup');

        $store = new StoreService(
            accountId: 7,
            channelFactory: fn (string $bucket): TelegramStoreChannel => new TelegramStoreChannel($bucket, $api->apiMap()),
            index: new MemoryStoreIndex,
            cache: new MemoryHotCache(ttlSeconds: 600, maxBytes: 8 * 1024 * 1024),
            queue: new SyncUploadQueue,
        );

        return new DatabaseBackupService(
            accountId: 7,
            dumper: $dumper,
            store: $store,
            runs: $runs,
            watchdog: $watchdog,
            logger: new NullLogger,
            passphrase: 's3cret',
            bucket: 'teleframe-backup:default',
        );
    }

    public function test_missing_binary_probe_returns_null(): void
    {
        $runner = static fn (array $argv, array $env, string $cwd): array => ['exit' => 127, 'stderr' => 'mysqldump: not found'];

        self::assertNull((new MysqlDumper($runner))->probe());
        self::assertNull((new MysqlDumper(static fn (): array => ['exit' => 127, 'stderr' => '']))->probe());
    }

    public function test_probe_returns_the_version_line_from_a_successful_probe(): void
    {
        $runner = static fn (array $argv, array $env, string $cwd): array => ['exit' => 0, 'stderr' => "mysqldump  Ver 8.0.36\nsomething else"];

        self::assertSame('mysqldump  Ver 8.0.36', (new MysqlDumper($runner))->probe());
    }

    /**
     * probe() must not resolve or execute a binary through the CURRENT
     * WORKING DIRECTORY.
     *
     * is_executable('mysqldump') checks the RELATIVE name against the CWD, so a
     * mysqldump dropped in the process CWD was preferred over the system one and
     * then executed — with PGPASSWORD/MYSQL_PWD in its child env. With a
     * relative PATH entry ('.') the exploit is complete: posix_spawn resolves
     * the bare name through that entry and runs the decoy. The lookup is now a
     * PATH walk over ABSOLUTE entries only.
     */
    public function test_a_working_directory_binary_is_never_resolved_or_executed(): void
    {
        $cwd = getcwd();
        $previousPath = getenv('PATH');
        $decoy = $cwd.DIRECTORY_SEPARATOR.'mysqldump';
        $marker = $cwd.DIRECTORY_SEPARATOR.'tf-decoy-was-executed';

        file_put_contents($decoy, "#!/bin/sh\ntouch ".$marker."\necho 'attacker 9.9.9'\n");
        chmod($decoy, 0o755);
        putenv('PATH=.'); // the relative entry that makes the CWD resolvable

        try {
            // No runner injected: this is the raw path, the one that resolves
            // and executes the binary itself.
            self::assertNull((new MysqlDumper)->probe(), 'a CWD mysqldump must not be found');
            self::assertFileDoesNotExist($marker, 'the decoy must never have been executed');
        } finally {
            @unlink($decoy);
            @unlink($marker);
            putenv($previousPath === false ? 'PATH' : 'PATH='.$previousPath);
        }
    }
}
