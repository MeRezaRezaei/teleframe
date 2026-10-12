<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store\Backup;

use MeRezaRezaei\Teleframe\Store\Backup\DatabaseTarget;
use PHPUnit\Framework\TestCase;

/**
 * DatabaseTarget (2026-09-24 backup-store remainder design §2/§3) — the
 * immutable DB descriptor whose argc() is the driver-shaped NON-SECRET
 * dump argument vector and whose env() is deliberately EMPTY (credentials
 * are env-at-run only: the dumper reads MYSQL_PWD/PGPASSWORD from the
 * host environment, never the target).
 *
 * Coverage: the mysql argv shape (the three scope flags exactly once,
 * socket-wins-over-host, port/user position, the `mysqldump [OPTIONS] db
 * [tbl ...]` canonical tail), the pgsql shape (--format=plain exactly
 * once as a standalone flag, --table= per whitelist entry), the
 * unknown-driver fallback, the empty env() base, and a STRICT zero-secret
 * scan: no 'pwd'/'password' substring in ANY argv element or env
 * key/value — with a planted-secret self-check proving the detector is
 * sharp.
 *
 * Zero regex, no live credentials, pure data.
 */
final class DatabaseTargetTest extends TestCase
{
    /** @var list<string> lowercase secret substrings the scan forbids */
    private const SECRET_MARKERS = ['pwd', 'password'];

    public function test_mysql_argc_has_scope_flags_once_and_the_canonical_tail(): void
    {
        $target = new DatabaseTarget(
            'mysql',
            'appdb',
            host: 'db.internal',
            port: 3307,
            user: 'backup',
            socket: '/run/mysqld/mysqld.sock',
            tables: ['users', 'orders'],
        );

        $argv = $target->argc();

        self::assertSame(1, count(array_keys($argv, '--single-transaction', true)));
        self::assertSame(1, count(array_keys($argv, '--quick', true)));
        self::assertSame(1, count(array_keys($argv, '--no-tablespaces', true)));

        // socket wins over host (mutually exclusive connection opts).
        self::assertContains('--socket=/run/mysqld/mysqld.sock', $argv);
        self::assertNotContains('--host=db.internal', $argv);
        self::assertContains('--port=3307', $argv);
        self::assertContains('--user=backup', $argv);
        self::assertContains('appdb', $argv);

        // Canonical tail: `mysqldump [OPTIONS] db [tbl ...]` — the table
        // whitelist is positional after the database name.
        self::assertSame(['users', 'orders'], array_slice($argv, -2));
    }

    public function test_mysql_uses_host_when_no_socket_is_given(): void
    {
        $argv = (new DatabaseTarget('mysql', 'appdb', host: 'db.internal', port: 3306, user: 'r'))->argc();

        self::assertContains('--host=db.internal', $argv);
        self::assertSame([], self::socketArgs($argv));
    }

    public function test_pgsql_argc_has_format_plain_once_and_table_flags_after_the_database(): void
    {
        $target = new DatabaseTarget(
            'pgsql',
            'appdb',
            host: 'pg.internal',
            port: 5433,
            user: 'backup',
            tables: ['users', 'orders'],
        );

        $argv = $target->argc();

        // The ONLY slice format — plain SQL, exactly once, standalone.
        self::assertSame(1, count(array_keys($argv, '--format=plain', true)));
        self::assertContains('--host=pg.internal', $argv);
        self::assertContains('--port=5433', $argv);
        self::assertContains('--username=backup', $argv);

        $dbPos = array_search('appdb', $argv, true);
        self::assertNotFalse($dbPos, 'database name must appear positionally');
        self::assertLessThan(array_search('--table=users', $argv, true), $dbPos);
        self::assertSame(['--table=users', '--table=orders'], array_slice($argv, -2));
    }

    public function test_pgsql_env_is_empty_just_like_mysql(): void
    {
        // Credentials are env-at-run only; the target carries NO env base.
        self::assertSame([], (new DatabaseTarget('pgsql', 'appdb', host: 'pg.internal', user: 'backup'))->env());
        self::assertSame([], (new DatabaseTarget('mysql', 'appdb', host: 'db.internal', user: 'backup'))->env());
    }

    public function test_unknown_driver_shapes_connection_opts_only_no_scope_flags(): void
    {
        $argv = (new DatabaseTarget('oracle', 'appdb', host: 'db.corp'))->argc();

        self::assertSame(['--host=db.corp'], $argv);
        self::assertNotContains('--format=plain', $argv);
        self::assertNotContains('--single-transaction', $argv);
    }

    public function test_strict_zero_secret_scan_over_argv_and_env_for_both_drivers(): void
    {
        $targets = [
            new DatabaseTarget('mysql', 'appdb', host: 'db.internal', port: 3307, user: 'backup', socket: '/run/mysqld/mysqld.sock', tables: ['users', 'orders']),
            new DatabaseTarget('pgsql', 'appdb', host: 'pg.internal', port: 5433, user: 'backup', tables: ['users', 'orders']),
        ];

        foreach ($targets as $target) {
            $argv = $target->argc();
            $env = $target->env();

            // env is empty by construction — no secret key/value can exist.
            self::assertSame([], $env);
            foreach ($env as $key => $value) {
                self::assertFalse(
                    self::containsSecret([$key, $value]),
                    "secret substring leaked into env: {$key}",
                );
            }

            // No 'pwd'/'password' substring in ANY argv element — case-insensitive.
            foreach ($argv as $arg) {
                self::assertFalse(
                    self::containsSecret([$arg]),
                    "secret substring leaked into argv: {$arg}",
                );
            }
        }
    }

    public function test_zero_secret_scan_detector_is_sharp(): void
    {
        // The scan must catch a planted secret — the rule is only as good
        // as its detector.
        self::assertTrue(self::containsSecret(['--password=hunter2']));
        self::assertTrue(self::containsSecret(['MYSQL_PWD=topsecret']));
        self::assertFalse(self::containsSecret(['--user=backup', '--port=3307']));
    }

    /** @param  list<string>  $words */
    private static function containsSecret(array $words): bool
    {
        foreach ($words as $word) {
            $lower = strtolower($word);
            foreach (self::SECRET_MARKERS as $marker) {
                if (str_contains($lower, $marker)) {
                    return true;
                }
            }
        }

        return false;
    }

    /** @param  list<string>  $argv
     *  @return list<string> */
    private static function socketArgs(array $argv): array
    {
        return array_values(array_filter(
            $argv,
            static fn (string $arg): bool => str_starts_with($arg, '--socket='),
        ));
    }
}
