<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Pg;

use MeRezaRezaei\Teleframe\Tests\Concerns\RunsPostgresMigrations;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;

/**
 * Night W3 Postgres track base: testbench app on the pg connection with a
 * disposable schema per test class (search_path).
 *
 * Env gate — default SKIP so the sqlite CI matrix stays green:
 *  - TELEFRAME_PG=1 forces the track ON (unreachable DB = failure,
 *    not a skip — opt-in runs must not silently pass);
 *  - otherwise the track runs only when the configured Postgres answers
 *    (local dev: peer-auth socket, db teleframe_night_test).
 */
abstract class PgTestCase extends TestCase
{
    use RunsPostgresMigrations;

    protected function getEnvironmentSetUp($app): void
    {
        $this->definePostgresDatabase($app);
    }

    protected function setUp(): void
    {
        if (!$this->postgresTrackEnabled()) {
            self::markTestSkipped(
                'Postgres track: set TELEFRAME_PG=1 (and TELEFRAME_PG_* connection env) '
                . 'or expose database teleframe_night_test on the local Postgres to run it',
            );
        }
        parent::setUp();
        static::ensurePostgresSchema();
    }

    /** Env-forced ON, or the configured server is reachable. */
    final public static function postgresTrackEnabled(): bool
    {
        if (getenv('TELEFRAME_PG') === '1') {
            return true;
        }

        return self::pgReachable();
    }
}
