<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Pg;

use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Tests\Concerns\RunsPostgresMigrations;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;

/**
 * Curated-dial Postgres track base: testbench app on the pg connection
 * with a disposable schema per test class (search_path).
 *
 * The shipped mirror is the hand-authored NF5 curated dial in
 * src/Laravel/Migrations: 16 create_tf_* migrations + 5 app-owned
 * migrations (tl_user_bindings, telegram_apps, telegram_accounts,
 * add_user_id, tg_update_routing) + the Task-8 FK migration
 * (2026_09_14_299999), migrated whole via UpdateIngestor::migrationPaths().
 * No tl_data JSONB, no constructor_id ints, no per-constructor tables.
 *
 * Env gate — default SKIP so the sqlite CI matrix stays green:
 *  - TELEFRAME_PG=1 forces the track ON (unreachable DB = failure,
 *    not a skip — opt-in runs must not silently pass);
 *  - otherwise the track runs only when the configured Postgres answers
 *    (local dev: peer-auth socket, db teleproto_night_test).
 */
abstract class PgTestCase extends TestCase
{
    use RunsPostgresMigrations;

    /**
     * One root parent table per create_tf_* migration in the curated dial
     * (16 create-tf files). Children ride in the same migration files.
     *
     * @var list<string>
     */
    protected const DOMAIN_TABLES = [
        'tf_users',
        'tf_chats',
        'tf_channels',
        'tf_dialogs',
        'tf_messages',
        'tf_messages_entities',
        'tf_messages_media',
        'tf_documents',
        'tf_photos',
        'tf_web_pages',
        'tf_sticker_sets',
        'tf_updates',
        'tf_channel_participants',
        'tf_stars_transactions',
        'tf_bot_infos',
        'tf_folders',
    ];

    protected function getEnvironmentSetUp($app): void
    {
        $this->definePostgresDatabase($app);
    }

    protected function setUp(): void
    {
        if (! static::postgresTrackEnabled()) {
            self::markTestSkipped(
                'Postgres track: set TELEFRAME_PG=1 (and TELEFRAME_PG_* connection env) '
                .'or expose database teleproto_night_test on the local Postgres to run it',
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
     * Migrate the curated dial (UpdateIngestor::migrationPaths(): the whole
     * shipped src/Laravel/Migrations dir — 16 create_tf_* + 5 app-owned +
     * the Task-8 FK migration — plus the off-dial updates/entities anchors)
     * on pg. Idempotent within a class schema: repeat runs are no-ops.
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
