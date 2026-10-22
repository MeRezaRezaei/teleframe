<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Facades\Facade;
use MeRezaRezaei\Teleframe\Core\Exceptions\TelegramException;
use RuntimeException;

/**
 * Builds the store tables by EXECUTING the shipped migration, never by
 * transcribing it.
 *
 * Why this class exists: a test that re-types the migration's columns is a
 * SECOND copy of the DDL, and a second copy silently drifts. The store
 * suite already lived through that failure — tests/Teleframe/
 * StoreVaultDaemonCoverageTest.php hand-built tf_store_objects with
 * primary(['account_id','name']) and no `bucket` column, while the shipped
 * DDL is primary(['account_id','bucket','name']) plus a composite foreign
 * key. Code and test agreed with each other and both disagreed with
 * production, so the suite was green while proving a table nobody ships.
 * A green test that retires a false concern is worse than no test.
 *
 * The cure is structural, not editorial: load the migration file, run
 * `up()` against a real sqlite connection, and let the shipped DDL be the
 * only copy. If the migration changes, every consumer of this helper
 * changes with it — there is nothing left to forget.
 *
 * The migration resolves `Schema::` through the facade, so the facade needs
 * an application whose `db.schema` is this connection. That is what
 * `runStoreMigration()` installs. The previously-resolved facade
 * application and the global container are captured and restored by the
 * caller through `restoreFacadeApplication()`.
 *
 * `capsuleFor()` is the general form and is deliberately not store-specific:
 * the same disease bit the ingest suite too, so any suite that needs a
 * shipped table names the migration FILE and transcribes nothing.
 *
 * Provenance: src/Laravel/Migrations/2026_09_20_000100_create_tf_store_tables.php
 * (tf_store_buckets / tf_store_objects / tf_store_media, composite primary
 * keys, composite FK to tf_store_buckets).
 */
final class ShippedStoreSchema
{
    /**
     * Absolute path of the store-tables migration, relative to this file.
     */
    private const MIGRATION = __DIR__.'/../../src/Laravel/Migrations/2026_09_20_000100_create_tf_store_tables.php';

    /**
     * Boot an in-memory sqlite Capsule whose schema IS the shipped store DDL.
     *
     * Foreign-key enforcement is left OFF (sqlite defaults to off) because
     * the migration declares composite foreign keys; whether a test wants
     * the FK enforced is a per-test decision, not a per-schema one.
     *
     * @return Capsule the booted capsule, already carrying every store table
     *
     * @throws RuntimeException when the migration file is missing or returns a non-migration
     */
    public static function capsule(): Capsule
    {
        return self::capsuleFor(self::MIGRATION);
    }

    /**
     * Boot an in-memory sqlite Capsule whose schema is produced by EXECUTING
     * the given shipped migration files, in order.
     *
     * This is the general form of the same idea, and it exists because the
     * disease is not store-specific: a test that re-types a migration's
     * columns is a second copy of the DDL and a second copy silently drifts.
     * The ingest suite proved it — it hand-built tf_users with three boolean
     * columns (the shipped table has thirty-one) and tf_dialogs with a
     * `top_message_id` column that does not exist (the shipped column is
     * `top_message`), which is how `DialogQuery::ordered()` shipped a
     * guaranteed SQL error behind a green test.
     *
     * The migration FILENAME is the only thing a caller names. Nothing about
     * the table shape is repeated at the call site, so there is no second
     * copy left to forget.
     *
     * @param  list<string>  $migrations  absolute paths to shipped migration files, in apply order
     *
     * @return Capsule the booted capsule, carrying whatever the migrations created
     *
     * @throws RuntimeException when a migration file is missing or returns a non-migration
     */
    public static function capsuleFor(string ...$migrations): Capsule
    {
        if ($migrations === []) {
            throw new RuntimeException('capsuleFor() needs at least one migration file');
        }

        $capsule = new Capsule;
        $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        $app = new Container;
        $app->instance('db', $capsule->getDatabaseManager());
        $app->instance('db.schema', $capsule->getConnection()->getSchemaBuilder());

        Container::setInstance($app);
        Facade::setFacadeApplication($app);
        Facade::clearResolvedInstances();

        foreach ($migrations as $path) {
            if (! is_file($path)) {
                throw new RuntimeException("shipped migration not found at {$path}");
            }

            try {
                $migration = require $path;
            } catch (TelegramException $e) {
                throw new RuntimeException("migration {$path} failed to load", 0, $e);
            }

            if (! is_object($migration) || ! method_exists($migration, 'up')) {
                throw new RuntimeException("migration {$path} did not return a migration object");
            }

            $migration->up();
        }

        return $capsule;
    }

    /**
     * Put back whatever global container and facade application the process
     * had before capsule() ran.
     *
     * Tests here extend Orchestra Testbench, which owns the real facade
     * application; clobbering it and not restoring it leaks sqlite state
     * into unrelated suites.
     *
     * @param  Container|null  $container  container to restore as the global instance
     */
    public static function restoreFacadeApplication(?Container $container): void
    {
        Container::setInstance($container);
        Facade::setFacadeApplication($container);
        Facade::clearResolvedInstances();
    }
}
