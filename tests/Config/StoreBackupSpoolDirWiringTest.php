<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Config;

use MeRezaRezaei\Teleframe\Laravel\Console\TstoreCommand;
use MeRezaRezaei\Teleframe\Laravel\StoreBackupServiceProvider;
use MeRezaRezaei\Teleframe\Store\Backup\BackupException;
use MeRezaRezaei\Teleframe\Store\Backup\BackupSupport;
use MeRezaRezaei\Teleframe\Store\Backup\DatabaseBackupService;
use MeRezaRezaei\Teleframe\Store\Backup\DatabaseDumper;
use MeRezaRezaei\Teleframe\Store\Backup\DatabaseTarget;
use MeRezaRezaei\Teleframe\Store\Backup\DumpResult;
use MeRezaRezaei\Teleframe\Store\Backup\DumpWorkspace;
use MeRezaRezaei\Teleframe\Store\Cache\MemoryHotCache;
use MeRezaRezaei\Teleframe\Store\Index\MemoryStoreIndex;
use MeRezaRezaei\Teleframe\Store\Queue\SyncUploadQueue;
use MeRezaRezaei\Teleframe\Store\StoreService;
use Orchestra\Testbench\TestCase;

/**
 * teleframe.store.backup.spool_dir — the key the audit proved was DEAD.
 *
 * It was dead because the only DumpWorkspace the backup path ever built was
 * `new DumpWorkspace` with no argument, so $dir was always '' and every dump
 * landed in sys_get_temp_dir()/teleframe-dumps. A host with a small or
 * ephemeral system temp, or a dump that must never touch it, had no way to
 * say so. The DumpWorkspace docblock made the same false claim.
 *
 * These tests exercise the WHOLE chain behaviourally — config key → the
 * provider's service binding → DatabaseBackupService → the DumpWorkspace the
 * dumper is actually handed — because a test that only checks the ctor
 * signature would pass while the provider kept passing nothing. The dumper is
 * the seam that observes the workspace, and it throws right after recording, so
 * nothing downstream (Telegram, S3, the put path) is ever touched.
 */
final class StoreBackupSpoolDirWiringTest extends TestCase
{
    protected function getPackageProviders(mixed $app): array
    {
        return [StoreBackupServiceProvider::class];
    }

    public function test_the_configured_spool_dir_reaches_the_workspace_the_dumper_is_handed(): void
    {
        $spoolDir = $this->tempDir().'/host-volume';
        $seen = $this->spoolDirSeenByDumper($spoolDir);

        self::assertSame(
            rtrim($spoolDir, '/\\'),
            $seen,
            'the dumper was handed a workspace pointing somewhere other than the'
            ."\nconfigured spool_dir. Either the provider did not pass the key, or the"
            ."\nservice did not hand it to DumpWorkspace — the key is dead again.",
        );
    }

    public function test_the_default_spool_dir_is_untouched_and_still_resolves_to_system_temp(): void
    {
        // The default must not change behaviour for an existing host: '' is
        // still "let DumpWorkspace decide", which is system temp.
        $seen = $this->spoolDirSeenByDumper('');

        self::assertSame(
            rtrim(sys_get_temp_dir(), '/\\').DIRECTORY_SEPARATOR.'teleframe-dumps',
            $seen,
            'with no configured spool_dir the workspace must fall back to'
            ."\nsys_get_temp_dir()/teleframe-dumps, exactly as it always has.",
        );
    }

    public function test_reading_the_key_never_resolves_or_creates_the_directory(): void
    {
        $spoolDir = $this->tempDir().'/must-not-exist-yet';

        // Resolving the whole service binding is the strongest form of "lazy":
        // it runs the provider closure that reads the key. If anything in the
        // chain mkdir'd, created a file, or dialled, it would already have
        // happened here.
        $this->bindStoreAndDumper();
        $this->app['config']->set('teleframe.store.backup.spool_dir', $spoolDir);
        $this->app->make(BackupSupport::SERVICE_KEY);

        self::assertDirectoryDoesNotExist(
            $spoolDir,
            'resolving the backup service created the spool directory. The dir must be'
            ."\nmade 0700 on the first spool(), so a wrong path fails loud at the first"
            ."\ndump instead of at wiring — and so the standalone-smoke gate, which boots"
            ."\nno container, stays meaningful.",
        );
    }

    public function test_a_spool_dir_that_cannot_be_used_fails_loud_at_the_first_dump_not_at_booting(): void
    {
        // A file where the directory should be: mkdir cannot win, so spool()
        // throws. That is the documented failure point — the key is read at
        // resolve time, but nothing TOUCHES the path until a real dump.
        $blocker = $this->tempDir().'/i-am-a-file';
        file_put_contents($blocker, 'not a directory');

        $seen = null;
        $this->bindStoreAndDumper(static function (DumpWorkspace $workspace) use (&$seen): void {
            $seen = $workspace->dir();
            $workspace->spool();
        }, $blocker);

        try {
            $this->runBackup();
            self::fail('expected a loud BackupException on the first dump');
        } catch (BackupException $e) {
            self::assertStringContainsString('cannot create the dump workspace directory', $e->getMessage());
        }

        self::assertSame($blocker, $seen, 'the configured dir must still be what the dumper saw');
    }

    /**
     * Configure spool_dir, run one backup, and report the directory the dumper
     * was handed. The dumper throws on purpose so the put path never runs.
     */
    private function spoolDirSeenByDumper(string $spoolDir): string
    {
        $seen = null;
        $this->bindStoreAndDumper(static function (DumpWorkspace $workspace) use (&$seen): void {
            $seen = $workspace->dir();

            throw new BackupException('stop here: the workspace dir is all this test needs');
        });
        $this->app['config']->set('teleframe.store.backup.spool_dir', $spoolDir);

        try {
            $this->runBackup();
        } catch (BackupException) {
            // Expected: the recording dumper always throws.
        }

        self::assertIsString($seen, 'the dumper was never reached, so nothing observed the workspace');

        return $seen;
    }

    /**
     * Resolve the provider-built service and run one backup against a target,
     * with the tstore seam and the dumper both stubbed.
     */
    private function runBackup(): void
    {
        $this->app->make(BackupSupport::SERVICE_KEY)
            ->backup(new DatabaseTarget('mysql', 'appdb', host: 'db.internal', user: 'backup'));
    }

    /**
     * Bind the two seams the backup service composes over: the tstore
     * StoreService (real class — it is final — but built entirely from
     * in-memory ports, so nothing is dialed) and the dumper that records the
     * workspace.
     */
    private function bindStoreAndDumper(?callable $onDump = null, string $spoolDir = ''): void
    {
        $this->app->instance(TstoreCommand::SERVICE_KEY, new StoreService(
            accountId: 1,
            // Never invoked: the dumper throws before the put path reaches it.
            channelFactory: static fn (string $bucket) => throw new BackupException('no channel needed'),
            index: new MemoryStoreIndex,
            cache: new MemoryHotCache(ttlSeconds: 600, maxBytes: 1024 * 1024),
            queue: new SyncUploadQueue,
        ));

        $this->app->bind(BackupSupport::DUMPER_KEY, static fn (): DatabaseDumper => new class($onDump) implements DatabaseDumper
        {
            public function __construct(private $onDump) {}

            public function dump(DatabaseTarget $target, DumpWorkspace $workspace): DumpResult
            {
                if ($this->onDump !== null) {
                    ($this->onDump)($workspace);
                }

                throw new BackupException('stop here: this test observes the workspace, not the dump');
            }
        });

        $this->app->forgetInstance(BackupSupport::SERVICE_KEY);
        $this->app['config']->set('teleframe.store.backup.spool_dir', $spoolDir);
    }

    private function tempDir(): string
    {
        $dir = sys_get_temp_dir().'/tf-spool-test-'.bin2hex(random_bytes(6));
        mkdir($dir, 0700, true);

        return $dir;
    }
}
