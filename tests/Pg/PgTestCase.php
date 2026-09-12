<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Pg;

use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Tests\Concerns\RunsPostgresMigrations;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;

/**
 * TDLib domain-schema Postgres track base: testbench app on the pg
 * connection with a disposable schema per test class (search_path).
 *
 * The mirror is the 13-file domain set (12 tf_* entity tables +
 * the routes migration), migrated via UpdateIngestor::migrationPaths().
 * There are no per-constructor tables and no foreign keys (spec §8).
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

    /**
     * The 12 shipped domain tables (spec §§3-5).
     *
     * @var list<string>
     */
    protected const DOMAIN_TABLES = [
        'tf_users',
        'tf_chats',
        'tf_channels',
        'tf_messages',
        'tf_dialogs',
        'tf_updates',
        'tf_documents',
        'tf_photos',
        'tf_sticker_sets',
        'tf_stories',
        'tf_wallpapers',
        'tf_channel_participants',
    ];

    protected function getEnvironmentSetUp($app): void
    {
        $this->definePostgresDatabase($app);
    }

    protected function setUp(): void
    {
        if (!static::postgresTrackEnabled()) {
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

    /**
     * Migrate the TDLib domain set (UpdateIngestor::migrationPaths():
     * shipped migrations/ dir + the 12 tf_* domain tables) on pg.
     * Idempotent within a class schema: repeat runs are no-ops.
     */
    protected function migrateDomainSet(): void
    {
        $this->artisan('migrate', [
            '--force' => true,
            '--realpath' => true,
            '--path' => UpdateIngestor::migrationPaths(),
        ]);
    }
}
