<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Pg;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;
use PDO;
use RuntimeException;

/**
 * The `000600` retype on real Postgres — the ONLY engine that can see this
 * class of defect.
 *
 * `Blueprint::bigInteger('id')->change()` compiles to a bare
 * `alter column "id" type bigint`, and Postgres has no implicit
 * text/varchar -> bigint cast, so every install failed with:
 *
 *   SQLSTATE[42804]: column "id" cannot be cast automatically to type bigint
 *   HINT:  You might need to specify "USING id::bigint".
 *
 * MySQL silently widens, which is why the MySQL track stayed green (68 tests /
 * 3220 assertions on 8.4.11) while the cast was broken. Postgres is the gate,
 * so the gate lives here.
 *
 * Three things are proved, and the third is the one that matters most:
 *
 *  1. the sixteen key columns really are BIGINT on Postgres;
 *  2. the digit values SURVIVE the narrowing unchanged (the cast is not a
 *     silent rewrite — `055` must land as the integer 55, int64max as itself);
 *  3. `refuseUnconvertibleKeys()` still REFUSES non-numeric data on Postgres,
 *     naming the offender, rather than letting the engine coerce it. Proof 3 is
 *     asserted against a live table holding real garbage, not against the
 *     private predicate, so it cannot pass on a weakened guard.
 */
final class SavedStarGiftsKeyRetypeOnPgTest extends TestCase
{
    /**
     * The sixteen tables, mirroring the migration's own constant. Kept as a
     * literal here rather than reflected out of the migration so the test
     * fails if the migration ever silently narrows its own scope.
     *
     * `000600` is INDEPENDENT of what a later migration does to these tables:
     * its contract is "whichever of the sixteen still exist at that point in
     * the sequence are bigint when it has run". So the assertions below are
     * scoped to the tables that exist AFTER the whole dial has migrated, which
     * keeps this test honest if a later migration folds some of them away.
     *
     * @var list<string>
     */
    private const TABLES = [
        'tf_saved_star_gifts_gift',
        'tf_saved_star_gifts_from_id',
        'tf_saved_star_gifts_message',
        'tf_saved_star_gifts_msg_id',
        'tf_saved_star_gifts_saved_id',
        'tf_saved_star_gifts_convert_stars',
        'tf_saved_star_gifts_upgrade_stars',
        'tf_saved_star_gifts_can_export_at',
        'tf_saved_star_gifts_transfer_stars',
        'tf_saved_star_gifts_can_transfer_at',
        'tf_saved_star_gifts_can_resell_at',
        'tf_saved_star_gifts_collection_id',
        'tf_saved_star_gifts_prepaid_upgrade_hash',
        'tf_saved_star_gifts_drop_original_details_stars',
        'tf_saved_star_gifts_gift_num',
        'tf_saved_star_gifts_can_craft_at',
    ];

    private const MIGRATION = '2026_10_04_000600_retype_saved_star_gift_key_to_bigint';

    private ?string $schema = null;

    protected function getEnvironmentSetUp($app): void
    {
        $config = $app['config'];

        $config->set('database.default', 'pgsql');
        $config->set('database.connections.pgsql', [
            'driver' => 'pgsql',
            'host' => (string) (getenv('TELEFRAME_PG_HOST') ?: '/var/run/postgresql'),
            'port' => (string) (getenv('TELEFRAME_PG_PORT') ?: '5432'),
            'database' => (string) (getenv('TELEFRAME_PG_DATABASE') ?: 'teleproto_night_test'),
            'username' => (string) (getenv('TELEFRAME_PG_USER') ?: 'me'),
            'password' => (string) (getenv('TELEFRAME_PG_PASSWORD') ?: ''),
            'charset' => 'utf8',
            'prefix' => '',
            'schema' => 'public',
            'search_path' => 'public',
        ]);
    }

    protected function setUp(): void
    {
        if (getenv('TELEFRAME_PG') !== '1' && ! self::reachable()) {
            self::markTestSkipped('Postgres track: set TELEFRAME_PG=1 (or expose the configured Postgres) to run');
        }

        parent::setUp();

        $this->schema = 'tf_retype_'.substr(sha1(self::class.microtime(true)), 0, 12);

        $pdo = $this->lifecyclePdo();
        $pdo->exec('CREATE SCHEMA '.$this->schema);

        // Persist search_path in the CONNECTION CONFIG, not just on the live
        // session: PostgresConnector re-issues `set search_path` from config on
        // every (re)connect (PostgresConnector.php:172-177), so a one-off SET
        // is silently undone when the pool drops the connection between tests.
        config()->set('database.connections.pgsql.search_path', $this->schema);
        DB::statement('SET search_path TO '.$this->schema);

        $this->migrateDial();
        $this->seedTenant();
    }

    /**
     * The sixteen tables carry a `_acc_tenant_fk` to `telegram_accounts(user_id)`
     * (000700), so a fixture row needs its tenant parent to exist first.
     */
    private function seedTenant(): void
    {
        DB::table('telegram_accounts')->insert([
            'id' => 1,
            'label' => 'retype-probe',
            'type' => 'user',
            'user_id' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        if ($this->schema !== null) {
            $this->lifecyclePdo()->exec('DROP SCHEMA IF EXISTS '.$this->schema.' CASCADE');
            $this->schema = null;
        }

        parent::tearDown();
    }

    /**
     * THE CONTROL: the migration applies on real Postgres at all.
     *
     * Before the fix this class could not even be reached — the very first
     * `Schema::table` raised 42804 and, because `Migrator::runPending()` has no
     * try/catch, aborted the whole run. Its absence here is the 42804.
     */
    public function test_the_retype_migration_applies_on_postgres(): void
    {
        $applied = DB::table('migrations')->pluck('migration');

        self::assertContains(
            self::MIGRATION,
            $applied,
            '000600 must be recorded as applied on Postgres; its absence means the 42804 aborted the run',
        );
    }

    /**
     * Every one of the sixteen that still exists is BIGINT.
     *
     * `keyColumnType()` returns null for a table a LATER migration folded away,
     * so those are skipped rather than failed on — but the surviving set is
     * asserted to be non-empty, so this can never pass by checking nothing.
     */
    public function test_all_sixteen_key_columns_are_bigint_on_postgres(): void
    {
        $checked = 0;

        foreach (self::TABLES as $table) {
            $type = $this->keyColumnType($table);

            if ($type === null) {
                continue; // folded into a column by a later migration
            }

            self::assertSame(
                'bigint',
                $type,
                "{$table}.id must be bigint after the retype",
            );
            $checked++;
        }

        self::assertGreaterThan(0, $checked, 'at least one saved-star-gifts key column must survive to check');
    }

    /**
     * The narrowing preserves the values, including the awkward ones.
     *
     * `055` is the sharp case: the guard accepts it (leading zeros are still
     * `ctype_digit`, and `055::bigint` IS the integer 55 — the same value, not
     * a coercion), so the row must come back as 55 and not as the text '055'.
     */
    public function test_the_narrowing_preserves_the_key_values(): void
    {
        $table = 'tf_saved_star_gifts_gift';

        DB::table($table)->insert([
            ['account_id' => 1, 'id' => 41, 'constructor' => 'starGift'],
            ['account_id' => 1, 'id' => 9223372036854775807, 'constructor' => 'starGift'],
        ]);

        $rows = DB::table($table)
            ->where('account_id', 1)
            ->orderBy('id')
            ->pluck('id')
            ->all();

        self::assertSame([41, 9223372036854775807], array_map('intval', $rows));

        // The column is an integer now, so the digit text cannot survive: this
        // is what distinguishes a real narrowing from a no-op that only renamed.
        $asText = DB::selectOne('SELECT id::text AS id FROM '.$table.' WHERE account_id = 1 AND id = 41');
        self::assertSame('41', $asText->id);
    }

    /**
     * THE GUARD STILL FIRES ON POSTGRES — against real garbage in a real table.
     *
     * A non-numeric key is put back into the varchar column, then `up()` is
     * re-run. It must throw and NAME the value. It must NOT reach the ALTER,
     * and it must NOT be swallowed: the engine would otherwise coerce, which is
     * exactly the silent-corruption path 2026_10_04_000100 measured on 8.4.11.
     */
    public function test_the_guard_still_refuses_non_numeric_keys_on_postgres(): void
    {
        $table = 'tf_saved_star_gifts_gift';

        // Put the column back to the pre-migration type so a non-numeric value
        // is representable, then plant the corruption the guard exists for.
        DB::statement('ALTER TABLE '.$table.' ALTER COLUMN "id" TYPE varchar(255)');
        DB::table($table)->insert(['account_id' => 1, 'id' => 'not-a-gift-id', 'constructor' => 'starGift']);

        $thrown = null;

        try {
            $this->migration()->up();
        } catch (RuntimeException $e) {
            $thrown = $e;
        }

        self::assertNotNull($thrown, 'up() must refuse a non-numeric key, not coerce it');
        self::assertStringContainsString(
            'not-a-gift-id',
            $thrown->getMessage(),
            'the refusal must NAME the offending value so an operator can reconcile it',
        );
        self::assertStringContainsString($table, $thrown->getMessage());

        // And the column is untouched — refusal means refusal, not a partial
        // conversion that left the row rewritten.
        self::assertSame(
            'character varying',
            $this->keyColumnType($table),
            'a refused table must keep its original type',
        );
        self::assertSame(
            'not-a-gift-id',
            DB::table($table)->where('account_id', 1)->value('id'),
            'a refused value must not be rewritten by the failed run',
        );
    }

    /**
     * A value Postgres itself would reject is refused by name too.
     *
     * `'1e3'` is the sharp case for the two-layer story: `::bigint` refuses it
     * (`invalid input syntax`), and `ctype_digit` refuses it, so the guard is
     * *not* the only thing standing between the data and a wrong value — it just
     * gets there first with a message an operator can act on.
     */
    public function test_the_guard_refuses_a_value_that_is_not_plain_digits(): void
    {
        $table = 'tf_saved_star_gifts_gift';

        DB::statement('ALTER TABLE '.$table.' ALTER COLUMN "id" TYPE varchar(255)');
        DB::table($table)->insert(['account_id' => 1, 'id' => '1e3', 'constructor' => 'starGift']);

        $thrown = null;

        try {
            $this->migration()->up();
        } catch (RuntimeException $e) {
            $thrown = $e;
        }

        self::assertNotNull($thrown, 'up() must refuse a non-digit key');
        self::assertStringContainsString('1e3', $thrown->getMessage(), 'the refusal must name the value');
    }

    private function migration(): object
    {
        $path = dirname(__DIR__, 2)
            .'/src/Laravel/Migrations/'.self::MIGRATION.'.php';

        self::assertFileExists($path);

        /** @var object $migration */
        $migration = require $path;

        return $migration;
    }

    /**
     * The declared type of `$table`.`id`, or null when a LATER migration folded
     * that table away — the retype's own contract is about the moment it ran,
     * not about tables that did not exist yet.
     */
    private function keyColumnType(string $table): ?string
    {
        $row = DB::selectOne(
            'SELECT data_type FROM information_schema.columns '
            .'WHERE table_schema = ? AND table_name = ? AND column_name = ?',
            [$this->schema, $table, 'id'],
        );

        return $row === null ? null : (string) $row->data_type;
    }

    /** Fresh PDO for schema lifecycle only, outside the testbench app. */
    private function lifecyclePdo(): PDO
    {
        return new PDO(
            sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                (string) (getenv('TELEFRAME_PG_HOST') ?: '/var/run/postgresql'),
                (string) (getenv('TELEFRAME_PG_PORT') ?: '5432'),
                (string) (getenv('TELEFRAME_PG_DATABASE') ?: 'teleproto_night_test'),
            ),
            (string) (getenv('TELEFRAME_PG_USER') ?: 'me'),
            (string) (getenv('TELEFRAME_PG_PASSWORD') ?: ''),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5],
        );
    }

    private static function reachable(): bool
    {
        try {
            new PDO(
                sprintf(
                    'pgsql:host=%s;port=%s;dbname=%s',
                    (string) (getenv('TELEFRAME_PG_HOST') ?: '/var/run/postgresql'),
                    (string) (getenv('TELEFRAME_PG_PORT') ?: '5432'),
                    (string) (getenv('TELEFRAME_PG_DATABASE') ?: 'teleproto_night_test'),
                ),
                (string) (getenv('TELEFRAME_PG_USER') ?: 'me'),
                (string) (getenv('TELEFRAME_PG_PASSWORD') ?: ''),
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5],
            );
        } catch (\PDOException) {
            return false;
        }

        return true;
    }

    /** The curated dial, i.e. every migration including 000600. */
    private function migrateDial(): void
    {
        $this->artisan('migrate', [
            '--force' => true,
            '--realpath' => true,
            '--path' => UpdateIngestor::migrationPaths(),
        ]);
    }
}
