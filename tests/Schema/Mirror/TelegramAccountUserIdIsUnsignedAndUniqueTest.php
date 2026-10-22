<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Mirror;

use Illuminate\Database\Connection;
use Illuminate\Database\UniqueConstraintViolationException;
use MeRezaRezaei\Teleframe\Tests\Store\ShippedStoreSchema;
use RuntimeException;

/**
 * `telegram_accounts.user_id` is the TENANT key, and it is the parent of the
 * 265 `tf_*.account_id` columns the foreign-key plan wants to constrain.
 *
 * ## WHY THIS LANE EXISTS — THE 3780 THE PLAN CANNOT GET PAST
 *
 * The plan wants every `tf_*.account_id` (`unsignedBigInteger` → `BIGINT
 * UNSIGNED`) to carry a CASCADE FOREIGN KEY to `telegram_accounts(user_id)`.
 * As shipped, `user_id` is `bigInteger` → signed `BIGINT`, and MySQL refuses
 * the constraint outright. Measured on MySQL 8.4.11 by this lane, verbatim:
 *
 * ```
 * ERROR 3780 (HY000): Referencing column 'account_id' and referenced column
 * 'user_id' in foreign key constraint 'account_tenant_fk' are incompatible.
 * ```
 *
 * This lane is therefore the PARENT HALF of the unblocking: it moves
 * `user_id` to `BIGINT UNSIGNED` and gives it the `UNIQUE` the constraint
 * needs, as ONE additive migration. It adds none of the 265 child
 * constraints — a later wave does, and cannot start until this lands.
 *
 * The decision record is `docs/decisions.md` **D-005** (Candidate A), and this
 * migration implements exactly the `up()` shape D-005 recommends. Nothing
 * here re-argues that decision.
 *
 * ## WHAT SQLITE CAN AND CANNOT SEE — STATED, NOT GLOSSED
 *
 * **sqlite has no unsigned integers.** This is not a caveat about this test,
 * it is a property of the engine, and it bounds what any sqlite test can
 * honestly assert. Measured through `ShippedStoreSchema::capsuleFor()` against
 * Laravel 13.31.0:
 *
 *   before `->change()`:  PRAGMA table_info  user_id type = INTEGER
 *   after  `->change()`:  PRAGMA table_info  user_id type = INTEGER
 *
 * Identical. So this suite asserts UNSIGNEDNESS FROM THE MIGRATION SOURCE —
 * the declaration is the only sqlite-visible carrier — and asserts UNIQUENESS
 * BEHAVIOURALLY, because a UNIQUE index is enforced by sqlite exactly as it
 * is by MySQL. The unsigned half is proved where it can actually be observed:
 * on MySQL. Measured by this lane on 8.4.11 after running this migration's DDL
 * verbatim:
 *
 * ```
 * | h6b_parent_type |            (before)  |
 * | bigint           |                      |
 * +----------------------------------+
 * | h6c_parent_type_after |           (after)   |
 * | bigint unsigned      |                      |
 * +----------------------------------+
 * ERROR 6125 (HY000): Failed to add the foreign key constraint. Missing unique
 *   key for constraint 'account_tenant_fk' in the referenced table
 *   'telegram_accounts'          <- without the UNIQUE
 * ERROR 3780 (HY000): Referencing column 'account_id' and referenced column
 *   'user_id' ... are incompatible. <- with the UNIQUE, parent still signed
 * ... then, after this migration's DDL, the very constraint the plan wants:
 * | account_tenant_fk | user_id |  and a valid row inserts, an orphan is refused
 * ERROR 1452 (23000): Cannot add or update a child row: a foreign key constraint fails
 * ```
 *
 * So the three states are all measured, in order: 6125 without the UNIQUE,
 * 3780 with it but signed, and success after both. That is the whole reason
 * this migration carries TWO changes and not one.
 *
 * ## WHY THE ORDER INSIDE `up()` IS UNIQUE-FIRST, NOT TYPE-FIRST
 *
 * `telegram_accounts.user_id` carries no uniqueness today, so an existing
 * install can hold duplicates, and the `UNIQUE` will then raise `ERROR 1062`
 * **mid-`ALTER`**. Which statement runs first decides whether that failure
 * leaves a half-applied schema. Both orders measured on 8.4.11 from the same
 * starting point (three rows: two with `user_id` = 111, one `NULL`):
 *
 * | order | outcome | type after | index count after |
 * | --- | --- | --- | --- |
 * | TYPE then UNIQUE | `ERROR 1062` on the SECOND statement | `bigint unsigned` | 0 |
 * | UNIQUE then TYPE | `ERROR 1062` on the FIRST statement | `bigint` | 0 |
 *
 * The type-first order COMMITS the type change and then fails, which is
 * precisely the half-applied schema D-005 warns about. UNIQUE-first fails on
 * its first statement and changes nothing. This migration is UNIQUE-first for
 * that reason, and `test_the_migration_refuses_to_run_when_duplicates_already_exist()`
 * pins the consequence rather than the mere presence of a guard.
 *
 * ## THE NEGATIVE-ID HAZARD IS GUARDED, NOT DELEGATED TO sql_mode
 *
 * D-005 measured that a negative `user_id` fails the `ALTER` safely under the
 * shipped strict `sql_mode` — and **silently clamps to 0** under a non-strict
 * one. Re-measured by this lane on 8.4.11, same row (`user_id` =
 * -1001234567890), same statement:
 *
 * ```
 * -- sql_mode = ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,... (shipped default)
 * ERROR 1264 (22003): Out of range value for column 'user_id' at row 1
 * | h5a_value_after_failed_alter |
 * |               -1001234567890 |   <- row UNCHANGED
 *
 * -- sql_mode = ''
 * (no error)
 * | h5b_column_type              |
 * | bigint unsigned              |
 * | label | h5b_value_after_successful_alter |
 * | neg   | 0                        |   <- SILENTLY DESTROYED
 * ```
 *
 * A `user_id` of `0` is not a Telegram account — it destroys the credential
 * mapping and reports success. So this migration does NOT depend on strict
 * mode: it pre-checks for negatives itself and throws. The docblock on
 * `up()` says the same thing, and
 * `test_the_migration_refuses_to_run_when_a_negative_user_id_exists()` proves
 * the check fires.
 *
 * ## TELEGRAM IDS ARE NON-NEGATIVE — A REPO COMMENT, NOT A SCHEMA PROOF
 *
 * The unsigned change is only sound if `user_id` is genuinely never negative.
 * That is asserted in this repository's own words, not proved by the wire:
 *
 *  - `2026_09_14_200001_create_tf_users_tables.php:15-17` — "telegram user
 *    ids are positive".
 *  - and the peers are the counter-example: chat and channel ids are
 *    **negative** (`2026_09_14_200002_create_tf_chats_tables.php:17`,
 *    `2026_09_14_200003_create_tf_channels_tables.php:16`), which is why the
 *    peer tables are signed and `account_id` — only ever the tenant — is
 *    already unsigned.
 *
 * The TL itself proves NOTHING here: `user_id:long`
 * (`src/Schema/schema/sources/mtproto.tl:3-4`, `api_full.tl:22`) is signed, and
 * so is the column being changed. **The justification for this migration is
 * the repo's own stated platform knowledge, and the guard below is the belt to
 * that braces** — if a host ever stored a negative, the migration refuses
 * rather than clamping.
 *
 * ## WHY THE HARNESS, WHEN UNIQUENESS DOES NOT DEPEND ON IT
 *
 * These tests go through `enforcingConnection()` so they sit on the same
 * instrumented connection as every other relation gate — and, honestly, so
 * nobody has to ask. But a `UNIQUE` index is enforced by sqlite with or
 * without `PRAGMA foreign_keys`: **the pragma is not load-bearing here.** That
 * is why the bite is proved the other way round, by
 * `test_the_same_duplicate_lands_on_a_connection_migrated_without_this_migration()`
 * — the identical insert, on the identical schema minus this one migration,
 * must SUCCEED. A harness that could not fail would be caught by that control,
 * not by the pragma.
 */
final class TelegramAccountUserIdIsUnsignedAndUniqueTest extends RelationConstraintTestCase
{
    /**
     * The minimum shipped migration set that yields a real `telegram_accounts`
     * with the tenant column, plus the migration under test.
     *
     * Named in apply order; no table shape is transcribed anywhere in this
     * file, so nothing here can drift from the DDL the way a hand-built
     * fixture would. `telegram_apps` is first because the accounts create
     * migration declares `app_id -> telegram_apps(id)`.
     *
     * @var list<string>
     */
    private const MIGRATIONS = [
        '2026_09_09_000200_create_telegram_apps_table.php',
        '2026_09_09_000201_create_telegram_accounts_table.php',
        '2026_09_14_000000_add_user_id_to_telegram_accounts_table.php',
        '2026_10_04_000100_make_user_id_unsigned_unique.php',
    ];

    /** Everything above except the migration under test — the CONTROL set. */
    private const CONTROL_MIGRATIONS = [
        '2026_09_09_000200_create_telegram_apps_table.php',
        '2026_09_09_000201_create_telegram_accounts_table.php',
        '2026_09_14_000000_add_user_id_to_telegram_accounts_table.php',
    ];

    /** The exact constraint name the migration declares — quoted, not derived. */
    private const UNIQUE_INDEX = 'telegram_accounts_user_id_unique';

    /** The tenant id used throughout; a real-shaped Telegram user id. */
    private const TENANT = 1724372757;

    /**
     * PROOF THE DECLARATION IS THERE — read back from the EXECUTED DDL, not
     * from the source. Asserted before the behaviour below for the reason
     * `RelationConstraintsAreEnforcedTest` gives: a negative test alone cannot
     * distinguish "the constraint bites" from "the column was never unique".
     */
    public function test_the_built_schema_declares_the_named_unique_on_user_id(): void
    {
        $db = $this->enforcingConnection(...$this->migrations(self::MIGRATIONS));

        $indexes = $db->getSchemaBuilder()->getIndexes('telegram_accounts');
        $named = array_values(array_filter(
            $indexes,
            static fn (array $i): bool => $i['name'] === self::UNIQUE_INDEX,
        ));

        self::assertCount(
            1,
            $named,
            'telegram_accounts must carry exactly one index named '.self::UNIQUE_INDEX.' — the name is '
            .'quoted rather than derived so a rename shows up as a failure here instead of as a mystery in '
            .'a MySQL error message. Found: '.implode(', ', array_map(
                static fn (array $i): string => (string) $i['name'],
                $indexes,
            )),
        );

        self::assertTrue(
            (bool) $named[0]['unique'],
            self::UNIQUE_INDEX.' must be a UNIQUE index. A plain index on user_id would satisfy every other '
            .'assertion in this file while refusing nothing.',
        );
        self::assertSame(
            ['user_id'],
            array_map('strval', $named[0]['columns']),
            'the unique key must cover user_id alone. A composite unique would not be the prerequisite the '
            .'tenant foreign key needs — MySQL demands a unique index whose LEADING column is the referenced '
            .'one (ERROR 6125 otherwise).',
        );
    }

    /**
     * THE BEHAVIOURAL BITE — a duplicate `user_id` is refused, by the unique
     * index and nothing else.
     *
     * The message is matched for the same reason
     * `RelationConstraintsAreEnforcedTest` matches it: a bare
     * `expectException(QueryException::class)` accepts "no such column", which
     * is a fixture bug wearing a gate's clothes.
     */
    public function test_a_duplicate_user_id_is_refused_by_the_unique_index(): void
    {
        $db = $this->enforcingConnection(...$this->migrations(self::MIGRATIONS));

        $this->insertAccount($db, 'first', self::TENANT);
        $this->insertAccount($db, 'second', self::TENANT + 1);

        try {
            $this->insertAccount($db, 'duplicate', self::TENANT);
            self::fail(
                'a second account row carrying user_id '.self::TENANT.' was ACCEPTED. telegram_accounts '
                .'carried no uniqueness before this migration, so this is the pre-migration state — and it is '
                .'the state in which the tenant foreign key cannot be created at all (MySQL ERROR 6125, '
                .'measured).',
            );
        } catch (UniqueConstraintViolationException $e) {
            self::assertStringContainsString(
                'UNIQUE constraint failed',
                $e->getMessage(),
                'the refusal must come from the UNIQUE index. Any other violation means the fixture is wrong, '
                .'not that the tenant key is enforced.',
            );
        }

        self::assertSame(
            2,
            $db->table('telegram_accounts')->count(),
            'the refused insert must leave nothing behind — an account row with a duplicate tenant would make '
            .'the UNIQUE index useless and the cascade ambiguous.',
        );
        self::assertSame(
            0,
            $db->table('telegram_accounts')->where('label', 'duplicate')->count(),
            'no row named "duplicate" may exist. The exception must abort the insert, not merely warn.',
        );
    }

    /**
     * THE BITE PROOF — the SAME duplicate, on the SAME schema, minus this one
     * migration, must SUCCEED.
     *
     * This is what makes the test above a gate rather than a decoration. If
     * the unique index were missing, or named differently, or the fixture were
     * wrong in a way that failed for another reason, the control would land
     * the row and the two halves could not both be true. It is also why the
     * `PRAGMA foreign_keys` harness is not what is being trusted here: a
     * UNIQUE index is enforced by sqlite regardless of that pragma.
     */
    public function test_the_same_duplicate_lands_on_a_connection_migrated_without_this_migration(): void
    {
        $control = ShippedStoreSchema::capsuleFor(...$this->migrations(self::CONTROL_MIGRATIONS))
            ->getConnection();

        $this->insertAccount($control, 'first', self::TENANT);
        $this->insertAccount($control, 'duplicate', self::TENANT);

        self::assertSame(
            2,
            $control->table('telegram_accounts')->count(),
            'CONTROL: without '.basename(self::MIGRATIONS[3]).', two rows sharing a user_id must both land. '
            .'If this ever fails, some OTHER shipped migration has gained uniqueness on this column, and the '
            .'refusal asserted elsewhere in this file is no longer evidence about this one. Re-measure which '
            .'migration is doing it — do not delete the control.',
        );
    }

    /**
     * NULL tenants must still coexist.
     *
     * `user_id` is `nullable()` because bot accounts have none, and a `UNIQUE`
     * index over a nullable column admits any number of NULLs on every engine
     * this repo ships. Measured on MySQL 8.4.11 by this lane: two bot rows
     * with `user_id IS NULL` coexisted under `telegram_accounts_user_id_unique`,
     * and deleting one did not touch the other's children.
     *
     * If this ever fails, bot accounts can no longer be vaulted — which is the
     * plan's own unresolved "bot and non-user account types may carry a null
     * user_id" risk (`docs/decisions.md` D-005), turning into a schema refusal.
     */
    public function test_several_null_tenant_rows_coexist(): void
    {
        $db = $this->enforcingConnection(...$this->migrations(self::MIGRATIONS));

        $this->insertAccount($db, 'bot-a', null);
        $this->insertAccount($db, 'bot-b', null);
        $this->insertAccount($db, 'user', self::TENANT);

        self::assertSame(
            3,
            $db->table('telegram_accounts')->count(),
            'two bot rows with a NULL user_id plus one user row must all land. A unique index treats every '
            .'NULL as distinct, so this is what keeps bot accounts vaultable.',
        );
        self::assertSame(
            2,
            $db->table('telegram_accounts')->whereNull('user_id')->count(),
            'both bot rows must be genuinely NULL rather than coerced to 0. A 0 here would be the '
            .'non-strict-mode clamp this migration exists to prevent.',
        );
    }

    /**
     * THE LOUD GATE, ON DUPLICATES — the real gate on this migration is not
     * the type, it is data this repo does not constrain.
     *
     * No `UNIQUE` exists today, so an existing install CAN hold duplicates, and
     * the `UNIQUE` raises `ERROR 1062` when it meets them. This migration must
     * detect that FIRST and refuse with a message an operator can act on —
     * never discover it mid-`ALTER`.
     *
     * The fixture is built the way an existing install looks: the pre-migration
     * schema, two rows already sharing a tenant, and then `up()` invoked by
     * hand so the refusal can be observed rather than swallowed by a runner.
     */
    public function test_the_migration_refuses_to_run_when_duplicates_already_exist(): void
    {
        $db = ShippedStoreSchema::capsuleFor(...$this->migrations(self::CONTROL_MIGRATIONS))
            ->getConnection();

        $this->insertAccount($db, 'first', self::TENANT);
        $this->insertAccount($db, 'second', self::TENANT);

        $e = $this->refusal($db, 'duplicate');

        self::assertStringContainsString(
            'duplicate',
            strtolower($e->getMessage()),
            'the refusal must NAME the problem in words an operator can act on. A bare "1062" or a '
            .'generic SQL error leaves the host no idea which rows to reconcile.',
        );
        self::assertStringContainsString(
            (string) self::TENANT,
            $e->getMessage(),
            'the refusal must name the offending VALUE, or the operator has to go and re-derive the whole '
            .'duplicate set by hand before they can fix anything.',
        );

        // And nothing may have been half-applied: the UNIQUE-first order
        // exists precisely so a refusal leaves the schema untouched.
        self::assertSame(
            [],
            $this->uniqueIndexes($db),
            'the schema must be UNTOUCHED after the refusal. Measured on MySQL 8.4.11: running the type '
            .'change BEFORE the unique leaves the column at bigint unsigned with no index when the unique '
            .'then raises 1062 — a half-applied parent. This migration is unique-first so that cannot happen.',
        );
        self::assertSame(
            2,
            $db->table('telegram_accounts')->count(),
            'the pre-existing duplicate rows must still both be there — a migration that deleted data on its '
            .'way to refusing would be worse than one that refuses.',
        );
    }

    /**
     * THE LOUD GATE, ON NEGATIVES — and this is the one that does NOT depend
     * on `sql_mode`.
     *
     * Measured on MySQL 8.4.11 with this migration's exact DDL: the same
     * negative row makes the statement raise `ERROR 1264` under the shipped
     * strict mode, and **succeed while silently becoming 0** under
     * `sql_mode = ''`. A `user_id` of 0 is not a Telegram account; it is a
     * destroyed credential mapping reported as success.
     *
     * sqlite stores the negative happily (measured: it round-trips as -5), so
     * this fixture is honest on this engine — the refusal below is the
     * migration's own guard, not a storage limitation being mistaken for one.
     */
    public function test_the_migration_refuses_to_run_when_a_negative_user_id_exists(): void
    {
        $db = ShippedStoreSchema::capsuleFor(...$this->migrations(self::CONTROL_MIGRATIONS))
            ->getConnection();

        $negative = -1001234567890;
        $this->insertAccount($db, 'negative', $negative);

        // The fixture itself must be real, or the refusal proves nothing.
        self::assertSame(
            $negative,
            (int) $db->table('telegram_accounts')->where('label', 'negative')->value('user_id'),
            'sqlite has no unsigned integers, so the negative must round-trip intact. If it does not, this '
            .'test is proving a storage clamp rather than the migration\'s guard, and the docblock is wrong.',
        );

        $e = $this->refusal($db, 'negative');

        self::assertStringContainsString(
            'negative',
            strtolower($e->getMessage()),
            'the refusal must say the value is negative. Silently clamping it to 0 — which is exactly what a '
            .'non-strict MySQL does with this ALTER — would destroy the credential mapping without erroring.',
        );
        self::assertSame(
            $negative,
            (int) $db->table('telegram_accounts')->where('label', 'negative')->value('user_id'),
            'the row must be UNCHANGED by the refusal. A refused migration that quietly rewrote the value '
            .'would be the very failure this guard exists to prevent.',
        );
        self::assertSame(
            [],
            $this->uniqueIndexes($db),
            'nothing may have been applied before the refusal.',
        );
    }

    /**
     * UNSIGNEDNESS and the STATEMENT ORDER, asserted from the only carriers
     * sqlite can see.
     *
     * Two things here are invisible to a sqlite schema and would otherwise be
     * a comment and nothing more:
     *
     *  - **Unsignedness.** The engine has no unsigned integers — measured:
     *    `PRAGMA table_info` reports `INTEGER` for `user_id` both before and
     *    after this migration's `->change()` — so the DECLARATION is all a
     *    sqlite suite can honestly assert. The proof that it lands as
     *    `BIGINT UNSIGNED` on a real engine is the MySQL measurement quoted in
     *    the file docblock.
     *
     *  - **The order inside `up()`.** UNIQUE must come before the type change,
     *    because a duplicate raises 1062 and a type-first order would already
     *    have committed the type change when it does (measured, both orders —
     *    the table is in the file docblock). Nothing in a sqlite schema can
     *    observe that, since neither statement is observable there.
     *
     * Reading the migration as text is this repo's own established pattern for
     * exactly this problem: `MySqlDialPortabilityTest` guards four MySQL
     * portability invariants no sqlite track can surface, from source alone.
     */
    public function test_the_migration_declares_user_id_unsigned_and_changed(): void
    {
        $source = (string) file_get_contents($this->migrationPath(self::MIGRATIONS[3]));

        self::assertMatchesRegularExpression(
            '/->unsignedBigInteger\(\s*\'user_id\'\s*\)(.*?)->change\(\)/s',
            $source,
            'the migration must declare `unsignedBigInteger(\'user_id\')` and CHANGE the existing column. '
            .'This is the whole deliverable: the parent column is signed as shipped, and the 265 unsigned '
            .'child columns cannot reference it (MySQL ERROR 3780, measured).',
        );
        self::assertDoesNotMatchRegularExpression(
            '/Schema::create\(\s*\'telegram_accounts\'/',
            $source,
            'this migration must not create the table. Existing installs already have it, and a create here '
            .'would either no-op or strand them — which is why this is a new additive migration rather than '
            .'an edit to 2026_09_09_000201.',
        );

        $unique = strpos($source, '->unique(');
        $change = strpos($source, 'unsignedBigInteger(\'user_id\')');

        self::assertIsInt($unique, 'the UNIQUE must be declared — MySQL raises ERROR 6125 without it.');
        self::assertIsInt($change, 'the type change must be declared.');
        self::assertLessThan(
            $change,
            $unique,
            'the UNIQUE must be added BEFORE the type change. Measured on MySQL 8.4.11 from the same '
            .'starting point with two rows sharing a user_id: type-first raises ERROR 1062 on its SECOND '
            .'statement and leaves the column at bigint unsigned with no index (half-applied), while '
            .'unique-first raises it on its FIRST statement and changes nothing at all.',
        );
    }

    /**
     * @param  list<string>  $migrations
     * @return list<string>
     */
    private function migrations(array $migrations): array
    {
        return array_map(
            static fn (string $file): string => dirname(__DIR__, 3).'/src/Laravel/Migrations/'.$file,
            $migrations,
        );
    }

    private function migrationPath(string $file): string
    {
        return dirname(__DIR__, 3).'/src/Laravel/Migrations/'.$file;
    }

    private function insertAccount(Connection $db, string $label, ?int $userId): void
    {
        $db->table('telegram_accounts')->insert([
            'label' => $label,
            'type' => $userId === null ? 'bot' : 'user',
            'user_id' => $userId,
        ]);
    }

    /**
     * Run the migration's `up()` against an already-populated pre-migration
     * schema and return the refusal it is required to raise.
     *
     * The migration is `require`d rather than run through a migrator, because
     * the point is to observe the refusal, not to have a runner swallow it.
     */
    private function refusal(Connection $db, string $what): RuntimeException
    {
        $path = $this->migrationPath(self::MIGRATIONS[3]);
        self::assertFileExists($path, 'the migration under test does not exist at '.$path);

        // `capsuleFor()` has already installed the facade application these
        // two variables re-read, so this is the same instrumented connection
        // the fixture wrote to — stated explicitly because a migration
        // resolving `Schema::` against a DIFFERENT connection would make the
        // guard queries read an empty table and pass for the wrong reason.
        self::assertSame(
            'sqlite',
            $db->getDriverName(),
            'this fixture is sqlite by construction; a driver change here invalidates the negative-value '
            .'round-trip assertion in the docblock and must be re-measured, not assumed.',
        );

        $migration = require $path;

        try {
            $migration->up();
        } catch (RuntimeException $e) {
            return $e;
        }

        self::fail(
            "the migration ran to completion against a schema carrying {$what} data it must refuse to "
            .'proceed past. Either the guard is missing or it does not cover this case.',
        );
    }

    /**
     * The UNIQUE indexes on `telegram_accounts` that cover `user_id` — this
     * migration's index and nothing else.
     *
     * Scoped to `user_id` on purpose. An unscoped "any unique index" filter
     * also reads `telegram_accounts_app_id_label_unique`, which the create
     * migration declares, and so reports a schema as changed whether or not
     * this migration ran — which is exactly how this helper failed first: the
     * after-refusal assertion was measuring a constant, not the migration.
     *
     * @return list<string>
     */
    private function uniqueIndexes(Connection $db): array
    {
        $names = [];
        foreach ($db->getSchemaBuilder()->getIndexes('telegram_accounts') as $index) {
            if ((bool) $index['unique'] && in_array('user_id', array_map('strval', $index['columns']), true)) {
                $names[] = (string) $index['name'];
            }
        }

        return $names;
    }
}
