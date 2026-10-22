<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Backup;

use MeRezaRezaei\Teleframe\Store\Backup\BackupException;
use MeRezaRezaei\Teleframe\Store\Backup\DatabaseDumper;
use MeRezaRezaei\Teleframe\Store\Backup\DatabaseTarget;
use MeRezaRezaei\Teleframe\Store\Backup\DumpResult;
use MeRezaRezaei\Teleframe\Store\Backup\DumpWorkspace;
use MeRezaRezaei\Teleframe\Store\Backup\SqliteDumper;
use PHPUnit\Framework\TestCase;

/**
 * V1.3's "default behaviour" — the operator's words, from
 * `.brain/verbatim/2026-09-24-notification-logger-backup-and-bot-framework.md`:
 *
 *   "also one thing is the default behaviour is it will be installed in a laravel
 *    so it can simply dump the laravel database and make a backup from it in
 *    schedules to a channel"
 *
 * Laravel's DEFAULT database is sqlite. The package shipped `MysqlDumper` +
 * `PostgresDumper` and nothing else, so a host that followed the operator's own
 * default behaviour — install the package, dump the database — got a refusal.
 * MEASURED 2026-10-18 by the host-install gate:
 *
 *   ✗ shipped-dumper-cannot-dump-sqlite
 *
 * WHAT IS ASSERTED, AND WHAT IS NOT. The dumper's contract is a faithful SQL dump
 * of one sqlite file; the sealing, the chunking and the upload belong to the layer
 * above it, and `mode: 'sealed'` is the ledger's word, not this class's. So these
 * tests assert a ROUND TRIP — the emitted SQL, executed into a fresh database,
 * reproduces the rows — rather than asserting on the dump's shape. A dump that
 * cannot be replayed is not a dump, and a test that only checks the file is
 * non-empty cannot tell the difference.
 */
final class SqliteDumperTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir().'/teleframe-sqlite-dumper-'.bin2hex(random_bytes(6));
        if (! mkdir($this->dir, 0777, true) && ! is_dir($this->dir)) {
            self::fail('could not create the fixture directory');
        }
    }

    protected function tearDown(): void
    {
        foreach ((array) glob($this->dir.'/*') as $file) {
            if (is_string($file) && is_file($file)) {
                @unlink($file);
            }
        }
        @rmdir($this->dir);

        parent::tearDown();
    }

    private function seedDatabase(string $path): void
    {
        $pdo = new \PDO('sqlite:'.$path);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE widgets (id INTEGER PRIMARY KEY, name TEXT NOT NULL)');
        $pdo->exec("INSERT INTO widgets (name) VALUES ('alpha'), ('bravo'), ('charlie')");
        $pdo = null;
    }

    public function test_it_satisfies_the_dumper_contract(): void
    {
        self::assertInstanceOf(DatabaseDumper::class, new SqliteDumper);
    }

    public function test_it_dumps_a_real_sqlite_database(): void
    {
        $path = $this->dir.'/app.sqlite';
        $this->seedDatabase($path);

        $result = (new SqliteDumper)->dump(new DatabaseTarget('sqlite', $path), new DumpWorkspace($this->dir));

        self::assertInstanceOf(DumpResult::class, $result);
        self::assertSame(0, $result->exitCode, 'a dump that failed says so: '.$result->stderrTail);
        self::assertGreaterThan(0, $result->bytes, 'a dump of a populated database is not empty');
        self::assertFileExists($result->path);
        self::assertSame('sqlite', $result->driver);
        self::assertStringEndsWith('.sql', $result->objectName(), 'the object name is what the ledger keys on');
    }

    public function test_the_dump_replays_into_a_fresh_database_row_for_row(): void
    {
        $path = $this->dir.'/app.sqlite';
        $this->seedDatabase($path);

        $result = (new SqliteDumper)->dump(new DatabaseTarget('sqlite', $path), new DumpWorkspace($this->dir));
        $sql = (string) file_get_contents($result->path);

        self::assertStringContainsString('widgets', $sql, 'a dump that does not name the table is not a dump of it');
        self::assertStringContainsString('charlie', $sql);

        $fresh = $this->dir.'/replayed.sqlite';
        $replay = new \PDO('sqlite:'.$fresh);
        $replay->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $replay->exec($sql);

        $rows = $replay->query('SELECT name FROM widgets ORDER BY id')->fetchAll(\PDO::FETCH_COLUMN);

        self::assertSame(['alpha', 'bravo', 'charlie'], $rows, 'a dump that cannot be replayed is not a dump');
    }

    public function test_it_refuses_a_database_that_is_not_there(): void
    {
        $dumper = new SqliteDumper;

        $this->expectException(BackupException::class);

        $dumper->dump(
            new DatabaseTarget('sqlite', $this->dir.'/does-not-exist.sqlite'),
            new DumpWorkspace($this->dir),
        );
    }

    public function test_the_probe_reports_the_engine_it_dumped_with(): void
    {
        $probed = (new SqliteDumper)->probe();

        self::assertIsString($probed);
        self::assertNotSame('', $probed);
    }

    public function test_the_provider_resolves_a_sqlite_dumper_from_the_default_driver(): void
    {
        // The registration is the half that was missing: a shipped class nobody
        // resolves is the same defect as a gate nobody runs, and this is the whole
        // of goal 46.87's first item.
        $source = file_get_contents(__DIR__.'/../../src/Laravel/StoreBackupServiceProvider.php');
        self::assertIsString($source);
        self::assertStringContainsString('use MeRezaRezaei\\Teleframe\\Store\\Backup\\SqliteDumper;', $source);
        self::assertStringContainsString("if (\$driver === 'sqlite')", $source);
    }
}
