<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store\Backup;

use MeRezaRezaei\Teleframe\Store\Backup\BackupException;
use MeRezaRezaei\Teleframe\Store\Backup\DatabaseTarget;
use MeRezaRezaei\Teleframe\Store\Backup\DumpWorkspace;
use MeRezaRezaei\Teleframe\Store\Backup\MysqlDumper;
use PHPUnit\Framework\TestCase;

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
