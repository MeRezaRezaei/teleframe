<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store\Backup;

use MeRezaRezaei\Teleframe\Store\Backup\BackupException;
use MeRezaRezaei\Teleframe\Store\Backup\DatabaseTarget;
use MeRezaRezaei\Teleframe\Store\Backup\DumpWorkspace;
use MeRezaRezaei\Teleframe\Store\Backup\PostgresDumper;
use PHPUnit\Framework\TestCase;

/**
 * PostgresDumper (2026-09-24 backup-store remainder design §2/§3/§9) —
 * offline mirror of MysqlDumperTest through the injectable proc runner.
 * The fake child writes its plain-SQL dump bytes into the tfbk-* spool
 * file the dumper created in $cwd and reports exit+stderr through the
 * contract. argv carries the pgsql driver shape --format=plain exactly
 * once (DatabaseTarget::argc() owns the flag shape; the dumper does not
 * duplicate it). Non-zero exit → BackupException with the capped 4 KiB
 * stderr tail; missing binary → probe() null.
 *
 * Zero regex, no live credentials, no live PostgreSQL.
 */
final class PostgresDumperTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/tf-pgdump-'.bin2hex(random_bytes(4));
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

        $runner = function (array $argv, array $env, string $cwd) use (&$argvSeen, &$envSeen): array {
            $argvSeen = $argv;
            $envSeen = $env;
            $files = glob($cwd.'/tfbk-*') ?: [];
            if (count($files) === 1) {
                file_put_contents($files[0], "-- PostgreSQL database dump\nCREATE TABLE t (id int);\n");
            }

            return ['exit' => 0, 'stderr' => ''];
        };

        $target = new DatabaseTarget('pgsql', 'appdb', host: 'pg.internal', port: 5433, user: 'backup');
        $result = (new PostgresDumper($runner))->dump($target, $this->workspace());

        // The pgsql driver shape, exactly once — the scope flag lives in
        // DatabaseTarget::argc(), not duplicated by the dumper.
        self::assertSame('pg_dump', $argvSeen[0]);
        self::assertSame(1, count(array_keys($argvSeen, '--format=plain', true)));
        self::assertContains('--host=pg.internal', $argvSeen);
        self::assertContains('--port=5433', $argvSeen);
        self::assertContains('--username=backup', $argvSeen);
        self::assertSame('appdb', $argvSeen[count($argvSeen) - 1]);

        self::assertArrayHasKey('PGPASSWORD', $envSeen);

        $dumpBytes = strlen("-- PostgreSQL database dump\nCREATE TABLE t (id int);\n");
        self::assertSame($this->spoolPath(), $result->path);
        self::assertSame($dumpBytes, $result->bytes);
        self::assertSame('pgsql', $result->driver);
        self::assertSame('appdb', $result->database);
        self::assertSame(0, $result->exitCode);
        self::assertSame('appdb-'.gmdate('Y-m-d-His', $result->startedAt).'.sql', $result->objectName());
    }

    public function test_non_zero_exit_throws_backup_exception_with_the_capped_stderr_tail(): void
    {
        $runner = static function (array $argv, array $env, string $cwd): array {
            return ['exit' => 1, 'stderr' => str_repeat('E', 8192).'TAIL-MARK'];
        };

        try {
            (new PostgresDumper($runner))->dump(new DatabaseTarget('pgsql', 'appdb'), $this->workspace());
            self::fail('expected BackupException');
        } catch (BackupException $e) {
            self::assertStringStartsWith('pg_dump failed (exit 1): ', $e->getMessage());
            self::assertStringEndsWith('TAIL-MARK', $e->getMessage());
            $tail = substr($e->getMessage(), strlen('pg_dump failed (exit 1): '));
            self::assertSame(4096, strlen($tail));
        }
    }

    public function test_missing_binary_probe_returns_null(): void
    {
        $runner = static fn (array $argv, array $env, string $cwd): array => ['exit' => 127, 'stderr' => 'pg_dump: not found'];

        self::assertNull((new PostgresDumper($runner))->probe());
        self::assertNull((new PostgresDumper(static fn (): array => ['exit' => 127, 'stderr' => '']))->probe());
    }

    public function test_probe_returns_the_version_line_from_a_successful_probe(): void
    {
        $runner = static fn (array $argv, array $env, string $cwd): array => ['exit' => 0, 'stderr' => "pg_dump (PostgreSQL) 16.3\nmore"];

        self::assertSame('pg_dump (PostgreSQL) 16.3', (new PostgresDumper($runner))->probe());
    }

    /**
     * probe() must not resolve or execute a binary through the CURRENT
     * WORKING DIRECTORY.
     *
     * is_executable('pg_dump') checks the RELATIVE name against the CWD, so a
     * pg_dump dropped in the process CWD was preferred over the system one and
     * then executed — with PGPASSWORD/MYSQL_PWD in its child env. With a
     * relative PATH entry ('.') the exploit is complete: posix_spawn resolves
     * the bare name through that entry and runs the decoy. The lookup is now a
     * PATH walk over ABSOLUTE entries only.
     */
    public function test_a_working_directory_binary_is_never_resolved_or_executed(): void
    {
        $cwd = getcwd();
        $previousPath = getenv('PATH');
        $decoy = $cwd.DIRECTORY_SEPARATOR.'pg_dump';
        $marker = $cwd.DIRECTORY_SEPARATOR.'tf-decoy-was-executed';

        file_put_contents($decoy, "#!/bin/sh\ntouch ".$marker."\necho 'attacker 9.9.9'\n");
        chmod($decoy, 0o755);
        putenv('PATH=.'); // the relative entry that makes the CWD resolvable

        try {
            // No runner injected: this is the raw path, the one that resolves
            // and executes the binary itself.
            self::assertNull((new PostgresDumper)->probe(), 'a CWD pg_dump must not be found');
            self::assertFileDoesNotExist($marker, 'the decoy must never have been executed');
        } finally {
            @unlink($decoy);
            @unlink($marker);
            putenv($previousPath === false ? 'PATH' : 'PATH='.$previousPath);
        }
    }
}
