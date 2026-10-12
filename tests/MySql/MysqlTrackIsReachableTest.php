<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\MySql;

use MeRezaRezaei\Teleframe\Tests\Concerns\RunsMysqlMigrations;
use PHPUnit\Framework\TestCase;

/**
 * The MySQL track exists at all. Phase 1's RED step.
 *
 * ## WHY THE SKIP GUARD, given the assertion looks redundant
 *
 * `assertTrue(static::mysqlTrackEnabled())` on its own reads like the right
 * shape — "the track must be reachable; if it is not, that is a finding". Run
 * verbatim, that is what it did, and it is WRONG here, measurably:
 *
 *   $ TELEFRAME_MYSQL_PORT=3999 vendor/bin/phpunit --no-coverage tests/MySql
 *   Tests: 1, Assertions: 1, Failures: 1.
 *
 * Because `composer verify` runs `@test` = `phpunit` over EVERY registered
 * testsuite, and the `verify` CI stage has no MySQL service, that assertion
 * turns the ordinary `verify` gate RED on every push from every machine
 * without a local MySQL. The plan's own Global Constraints require the
 * opposite: "a local `composer verify` on a machine without MySQL must stay
 * green". The plan's Task 1.1 Step 5 agrees — "Expected: skipped with the
 * reason printed" — so the literal Step 1 snippet contradicts Step 5 and the
 * Global Constraints, and Step 5 is the one that matches the track's design.
 *
 * So the skip is conditional on the ABSENCE of the server AND the absence of
 * the force flag. Under `TELEFRAME_MYSQL=1` there is no skip path at all and an
 * unreachable server surfaces the real PDO connection error — which is the
 * failure mode this trait exists to prevent, and which Step 5's second command
 * requires. Neither assertion below was weakened: the driver check and the
 * `^8\.4\.` version check still run and still fail the test when the server
 * that answers is not MySQL 8.4.
 *
 * See `.brain/decisions/D-015-mysql-track-reachability-skip.md`.
 */
final class MysqlTrackIsReachableTest extends TestCase
{
    use RunsMysqlMigrations;

    public function test_the_track_connects_to_a_real_mysql_and_reports_its_version(): void
    {
        if (! self::mysqlTrackEnabled() && ! $this->mysqlTrackIsForced()) {
            self::markTestSkipped(
                'MySQL track: no server answers on the configured host, and TELEFRAME_MYSQL=1 '
                .'was not set to force it. Set TELEFRAME_MYSQL=1 (with TELEFRAME_MYSQL_* '
                .'connection env) to make an unreachable database a FAILURE instead of a skip.',
            );
        }

        self::assertTrue(
            self::mysqlTrackEnabled(),
            'the MySQL track must be reachable in this environment; if it is not, the whole '
            .'phase cannot be verified and that is a finding, not a skip',
        );

        $pdo = self::mysqlLifecyclePdo();
        self::assertSame('mysql', $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME));

        $version = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
        self::assertMatchesRegularExpression('/^8\.4\./', $version, "expected MySQL 8.4.x, got {$version}");
    }

    /** Mirrors RunsMysqlMigrations' force rule: a forced run must never skip. */
    private function mysqlTrackIsForced(): bool
    {
        return (string) (getenv('TELEFRAME_MYSQL') ?: '') === '1';
    }
}
