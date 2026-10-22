<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store\Backup;

use MeRezaRezaei\Teleframe\Store\Backup\BackupException;
use MeRezaRezaei\Teleframe\Store\Backup\DatabaseRestorer;
use MeRezaRezaei\Teleframe\Store\Backup\DatabaseTarget;
use MeRezaRezaei\Teleframe\Store\Backup\RestoreResult;
use PHPUnit\Framework\TestCase;

/**
 * DatabaseRestorer (2026-09-24 backup-store remainder design §8,
 * wave-2) — object bytes → mysql/psql stdin, offline through the
 * injectable process runner (the fake is a complete process stand-in:
 * it receives argv/env/cwd and returns exit+stderr; it never sees the
 * source bytes).
 *
 * Coverage: the mysql argv/env shape (--socket= wins over --host=,
 * --port=, --user=, --database=; MYSQL_PWD present in the child env —
 * env-at-run only, never argv), the pgsql shape (--host=, --port=,
 * --username=, --dbname=; PGPASSWORD), non-zero exit → BackupException
 * carrying the capped ≤4 KiB stderr tail, unknown driver → BackupException
 * loud, a raw-bytes STRING source is REJECTED (BackupException — dump
 * objects can be GBs, never a PHP string), path + stream sources are both
 * accepted, and the RestoreResult fields on success.
 *
 * Zero regex, no live credentials, no live MySQL/Postgres.
 */
final class DatabaseRestorerTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/tf-restore-'.bin2hex(random_bytes(4));
        mkdir($this->dir, 0700);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir.'/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->dir);
    }

    private function sqlPath(string $bytes): string
    {
        $path = $this->dir.'/dump.sql';
        file_put_contents($path, $bytes);

        return $path;
    }

    public function test_mysql_restore_shapes_argv_env_and_result_fields(): void
    {
        $sql = "-- MySQL dump 8.0\nCREATE TABLE t (id int);\n";
        $path = $this->sqlPath($sql);

        $argvSeen = null;
        $envSeen = null;
        $runner = function (array $argv, array $env, string $cwd) use (&$argvSeen, &$envSeen): array {
            $argvSeen = $argv;
            $envSeen = $env;

            return ['exit' => 0, 'stderr' => ''];
        };

        $target = new DatabaseTarget(
            'mysql',
            'appdb',
            host: 'db.internal',
            port: 3307,
            user: 'backup',
            socket: '/run/mysqld/mysqld.sock',
        );
        $result = (new DatabaseRestorer($runner))->restore($target, $path);

        // argv = client binary + the target's non-secret connection args.
        self::assertSame('mysql', $argvSeen[0]);
        self::assertContains('--socket=/run/mysqld/mysqld.sock', $argvSeen);
        self::assertNotContains('--host=db.internal', $argvSeen);
        self::assertContains('--port=3307', $argvSeen);
        self::assertContains('--user=backup', $argvSeen);
        self::assertContains('--database=appdb', $argvSeen);

        // env = target env + the password key from the HOST env — the key
        // is ALWAYS present ('' when the host env is unset), never argv.
        self::assertArrayHasKey('MYSQL_PWD', $envSeen);
        foreach ($argvSeen as $arg) {
            self::assertStringNotContainsString('MYSQL_PWD', $arg);
            self::assertStringNotContainsString('PWD=', strtoupper($arg));
        }

        // RestoreResult field truth.
        self::assertInstanceOf(RestoreResult::class, $result);
        self::assertSame('mysql', $result->driver);
        self::assertSame('appdb', $result->database);
        self::assertSame(0, $result->exitCode);
        self::assertSame('', $result->stderrTail);
        self::assertSame(strlen($sql), $result->bytesRestored);
        self::assertLessThanOrEqual($result->finishedAt, $result->startedAt);
        self::assertGreaterThan(0, $result->startedAt);
        self::assertSame([
            'driver' => 'mysql',
            'database' => 'appdb',
            'startedAt' => $result->startedAt,
            'finishedAt' => $result->finishedAt,
            'exitCode' => 0,
            'stderrTail' => '',
            'bytesRestored' => strlen($sql),
        ], $result->toArray());
    }

    public function test_pgsql_restore_shapes_username_and_dbname_with_pgpassword_env(): void
    {
        $path = $this->sqlPath("CREATE TABLE t (id int);\n");

        $argvSeen = null;
        $envSeen = null;
        $runner = function (array $argv, array $env, string $cwd) use (&$argvSeen, &$envSeen): array {
            $argvSeen = $argv;
            $envSeen = $env;

            return ['exit' => 0, 'stderr' => ''];
        };

        $target = new DatabaseTarget('pgsql', 'appdb', host: 'pg.internal', port: 5433, user: 'backup');
        $result = (new DatabaseRestorer($runner))->restore($target, $path);

        self::assertSame('psql', $argvSeen[0]);
        self::assertContains('--host=pg.internal', $argvSeen);
        self::assertContains('--port=5433', $argvSeen);
        self::assertContains('--username=backup', $argvSeen);
        self::assertContains('--dbname=appdb', $argvSeen);
        self::assertArrayHasKey('PGPASSWORD', $envSeen);
        self::assertSame('pgsql', $result->driver);
        self::assertSame(0, $result->exitCode);
    }

    public function test_non_zero_exit_throws_backup_exception_with_the_capped_stderr_tail(): void
    {
        $path = $this->sqlPath('CREATE TABLE t (id int);');
        $runner = static fn (array $argv, array $env, string $cwd): array => [
            'exit' => 3,
            'stderr' => str_repeat('E', 8192).'TAIL-MARK',
        ];

        try {
            (new DatabaseRestorer($runner))->restore(new DatabaseTarget('mysql', 'appdb'), $path);
            self::fail('expected BackupException on a non-zero exit');
        } catch (BackupException $e) {
            // Last 4 KiB of stderr, byte-safe — exactly the §3 cap.
            self::assertStringStartsWith('mysql failed (exit 3): ', $e->getMessage());
            self::assertStringEndsWith('TAIL-MARK', $e->getMessage());
            $tail = substr($e->getMessage(), strlen('mysql failed (exit 3): '));
            self::assertSame(4096, strlen($tail));
        }
    }

    public function test_unknown_driver_throws_backup_exception_loud(): void
    {
        $path = $this->sqlPath('CREATE TABLE t (id int);');

        try {
            (new DatabaseRestorer)->restore(new DatabaseTarget('oracle', 'appdb'), $path);
            self::fail('expected BackupException for an unknown driver');
        } catch (BackupException $e) {
            self::assertStringContainsString('oracle', $e->getMessage());
        }
    }

    public function test_raw_bytes_string_source_is_rejected(): void
    {
        try {
            (new DatabaseRestorer)->restore(
                new DatabaseTarget('mysql', 'appdb'),
                "-- MySQL dump\nCREATE TABLE t (id int);\n", // raw bytes, NOT a path
            );
            self::fail('expected BackupException for a raw-bytes string source');
        } catch (BackupException $e) {
            self::assertStringContainsString('never raw bytes', $e->getMessage());
        }
    }

    public function test_path_source_is_accepted(): void
    {
        $sql = "CREATE TABLE t (id int);\n";
        $path = $this->sqlPath($sql);
        $runner = static fn (array $argv, array $env, string $cwd): array => ['exit' => 0, 'stderr' => ''];

        $result = (new DatabaseRestorer($runner))->restore(new DatabaseTarget('mysql', 'appdb'), $path);

        self::assertSame(strlen($sql), $result->bytesRestored);
    }

    public function test_stream_source_is_accepted(): void
    {
        $sql = "CREATE TABLE t (id int);\n";
        $stream = fopen('php://memory', 'r+');
        self::assertIsResource($stream);
        fwrite($stream, $sql);
        rewind($stream);

        $runner = static fn (array $argv, array $env, string $cwd): array => ['exit' => 0, 'stderr' => ''];
        $result = (new DatabaseRestorer($runner))->restore(new DatabaseTarget('pgsql', 'appdb'), $stream);
        fclose($stream);

        self::assertSame('pgsql', $result->driver);
        self::assertSame(strlen($sql), $result->bytesRestored);
    }

    public function test_non_string_non_resource_source_is_rejected(): void
    {
        try {
            (new DatabaseRestorer)->restore(new DatabaseTarget('mysql', 'appdb'), 42);
            self::fail('expected BackupException for a non-string non-resource source');
        } catch (BackupException $e) {
            self::assertStringContainsString('readable file path or an open stream', $e->getMessage());
        }
    }
}
