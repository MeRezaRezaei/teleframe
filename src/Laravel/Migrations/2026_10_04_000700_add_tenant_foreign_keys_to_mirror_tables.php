<?php

declare(strict_types=1);

use Illuminate\Database\Connection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Give every tenant-bearing mirror table a CASCADE foreign key to
 * `telegram_accounts.user_id`.
 *
 * ## WHAT A TENANT IS, IN THIS SCHEMA'S OWN WORDS
 *
 * `tf_*.account_id` is the TELEGRAM USER id, not the vault's app row id. Four
 * independent places in `src/` say so:
 *
 *   src/Laravel/Services/TeleframeClient.php:113
 *     "@param int|null $accountId  Telegram user ID"
 *   src/Laravel/Console/BackfillCommand.php:165
 *     "--account= : Telegram account id (tenant) the history lands under"
 *   src/Teleframe/Vault/Vault.php:11-12
 *     "TELEFRAME_DEFAULT_ACCOUNT_ID is a Telegram user id (numeric) — looked up via `user_id` column"
 *   src/Teleframe/Vault/TelegramAccount.php:147
 *     "The poller knows the TELEGRAM id (that is the only identity a session carries),
 *      not the vault row id"
 *
 * and `src/Laravel/MirrorIngesterFactory.php:160` writes that value straight
 * into every mirror row: `$payload['account_id'] = $accountId;`.
 *
 * `docs/decisions.md` D-005 settled which parent column it means — `user_id`,
 * not `.id` — and measured every alternative. This file is the last step of that
 * decision's recommended order: retarget the six (done, `2026_10_04_000200`),
 * make the parent unsigned + UNIQUE (done, `2026_10_04_000100`), then constrain
 * the rest. The first two were prerequisites; they are measured, not assumed:
 *
 *   ERROR 3780: Referencing column 'account_id' and referenced column 'user_id'
 *               in foreign key constraint '...' are incompatible.
 *   ERROR 6125: Missing unique key for constraint '...'
 *
 * A signed parent cannot carry an unsigned child at all, so this file's
 * timestamp is 000700 for a reason: it is ordered after both prerequisites, and
 * the ordering is one-way.
 *
 * ## 264 TABLES, AND WHERE THE NUMBER COMES FROM
 *
 * MEASURED on MySQL 8.4.11 (8.4.11-0ubuntu0.26.04.1) against a scratch
 * database created from the shipped dial, and measured independently on sqlite
 * by booting the same dial in memory. Both report the same count:
 *
 *   SELECT COUNT(DISTINCT TABLE_NAME) AS tf_with_account_id
 *     FROM information_schema.COLUMNS
 *    WHERE TABLE_SCHEMA = DATABASE()
 *      AND COLUMN_NAME = 'account_id'
 *      AND TABLE_NAME LIKE 'tf\_%';
 *
 *     tf_with_account_id = 264
 *
 * Two adjacent counts, so the number is not mistaken for a coincidence:
 *
 *   all tables carrying an account_id column ....... 266
 *     -> tf_* ...................................... 264   <- THIS FILE
 *     -> tg_update_routing, tl_user_bindings .......   2   (not mirror; out of scope)
 *   tf_* tables in total ........................... 265
 *     -> tf_accounts ...............................   1   (no account_id column at all)
 *
 * ## 265 -> 264, AND WHY THIS FILE'S PIN MOVED WITH A DELETION
 *
 * This file was written against 265. `da926d87 fix(mirror): delete the dead
 * tf_message_entities table` dropped one `tf_*` table that carried an
 * `account_id`, so the honest count on this tree is 264 and the constant below
 * refuses to run against 265.
 *
 * That refusal is the mechanism working, not failing: it turned one sibling
 * lane's table deletion into a red gate on THIS migration instead of a silent
 * 264-table install whose migration log claims 265. The alternative — leaving
 * the pin at 265 — would have recorded this migration as applied against a set
 * nobody measured.
 *
 * The count was re-measured on BOTH engines rather than decremented by
 * arithmetic:
 *
 *   sqlite, booting the shipped dial in memory  -> 264
 *   MySQL 8.4.11, the same dial on a scratch db -> 264
 *
 * and the deletion is independently attested: `tf_message_entities` is absent
 * from both lists. Its own commit message carries the writer-side measurement
 * (ten decoded payload shapes emit 0 rows into it and 18 into
 * `tf_messages_entities`, which carries `position` in its key and therefore
 * loses nothing), so this is a DELETION OF A TABLE NOTHING WROTE, not a silent
 * drop of a live one.
 *
 * `EXPECTED_TENANT_TABLES` here and `MEASURED_TENANT_TABLES` in
 * `tests/Schema/Mirror/TenantAccountForeignKeyTest.php` are two independent
 * implementations of one discovery, and BOTH moved to 264.
 *
 * `tf_accounts` cannot be in the list and that is not an omission. Its own
 * primary key IS the Telegram id — `2026_09_22_000100_create_tf_accounts_table.php:25`
 * reads "One row per Telegram account_id — NOT auto-increment, ids come from
 * Telegram" — so it has no `account_id` column to constrain. D-005 also records
 * that `tf_accounts` is the eventual FK TARGET rather than a child: nothing in
 * `src/` ever creates a `TfAccount` row, so constraining children to it today
 * would refuse legitimate mirror rows on any install whose host has not
 * populated the registry by hand.
 *
 * ## THE LIST IS DISCOVERED, NEVER TRANSCRIBED
 *
 * `tenantTables()` reads it from the engine this migration is applying to —
 * `information_schema` on MySQL, the schema builder's own table listing
 * elsewhere. Nothing in this file names a mirror table. A transcribed list is a
 * second copy of the dial, and it drifts silently; the branch this work belongs
 * to already has three defects that shipped green, and D-005 records a fourth
 * class of the same disease when a count was adjusted by hand to match a schema
 * that had itself drifted.
 *
 * So the discovery is CHECKED instead of trusted, twice, and by two independent
 * implementations:
 *
 *  - `EXPECTED_TENANT_TABLES` below is asserted against what was discovered, so
 *    a table cannot silently join or leave the set;
 *  - `tests/Schema/Mirror/TenantAccountForeignKeyTest.php` re-implements the
 *    discovery independently and asserts its own count, so the migration's
 *    discovery is checked rather than believed.
 *
 * When a family legitimately gains or loses a table, bump BOTH numbers. Never
 * adjust one to make a run green: the count is the only thing standing between
 * a deleted mirror table and a silent pass.
 *
 * ## A ROOT IS STILL A TENANT
 *
 * The relation graph calls 45 tables `root` — children of no other table. A root
 * is a fact with no parent WITHIN THE MIRROR; the tenant boundary sits ABOVE
 * the mirror, so "no parent fact" says nothing about "no parent tenant". A root
 * naming a tenant that does not exist is precisely the orphan this constraint
 * exists to refuse, and it is likeliest on a root: a root has nothing else it
 * could be wrong about. All 264 are constrained, roots included.
 *
 * ## THE SIX ALREADY CARRY IT, AND ARE LEFT ALONE
 *
 * `2026_10_04_000200_retarget_tenant_foreign_keys.php` retargeted six
 * mis-aimed constraints to `user_id`, naming each `{table}_tenant_fk`. They are
 * inside the 264 and this file leaves them exactly as are — detected by READING
 * the executed schema, not by a hardcoded list, so a re-run is a no-op rather
 * than a duplicate-constraint error. That also makes the migration safe on an
 * install that already applied it.
 *
 * `down()` draws the same line from the other side: it drops only constraints
 * whose name carries THIS file's suffix, so the six owned by `2026_10_04_000200`
 * survive a rollback of this file. Sharing that suffix would make the two
 * indistinguishable — MEASURED, and it is why `CONSTRAINT_SUFFIX` is
 * `_acc_tenant_fk` rather than the shorter, prettier `_tenant_fk`.
 *
 * ## CONSTRAINT NAMES ARE EXPLICIT, AND FINDABLE BY `dropForeign`
 *
 * Every constraint is named `{table}_acc_tenant_fk` — see `CONSTRAINT_SUFFIX` for
 * why that exact length and that `acc`. The 48 mirror-to-mirror
 * FKs that already exist are named by their family helpers
 * (`fk_participant_link_*`, `fk_diff_link_*`, …); the six retargeted ones are
 * `{table}_tenant_fk`. Naming here means a later `dropForeign('…_account_tenant_fk')`
 * finds it by a name a reader can guess rather than by Laravel's implicit
 * `{table}_{column}_foreign`, which would be indistinguishable from the
 * composite mirror FK that leads with the same column.
 *
 * ## ON DELETE CASCADE IS DERIVED, NOT CHOSEN PER TABLE
 *
 * The tenant link goes through ONE helper, `tenantLink()`, the same shape as the
 * family's existing `linkChild()` / `participantLink()` / `channelUpdateLink()` /
 * `differenceLink()`. Those are the shared Blueprint helpers the mirror already
 * routes every child link through, and routing this one the same way is what
 * keeps the join columns and the on-delete rule derived from a single place
 * instead of transcribed into 264 call sites. No join column list is written by
 * hand anywhere in this file.
 *
 * CASCADE because the tenant is the outermost boundary: a mirror row must never
 * outlive its tenant. Measured on MySQL 8.4.11 — a valid row lands, an orphan is
 * refused `ERROR 1452`, and deleting the parent takes the child from 1 row to 0.
 *
 * ## ADDITIVE, AND NO EXISTING MIGRATION IS EDITED
 *
 * Editing the 34 create migrations in place would be a defect, not a shortcut: an
 * install that already ran them would keep the unconstrained schema forever,
 * because those migrations are long since recorded as applied. The add happens
 * HERE, in a new file — the same convention as `2026_10_04_000100` and the four
 * additive drift-fix migrations before it.
 *
 * ## WHY sqlite IS NOT THE GATE
 *
 * sqlite has no `ALTER TABLE … ADD CONSTRAINT`: Laravel's sqlite grammar
 * achieves the add by rebuilding the table, and the rebuild PRESERVES a
 * composite FK the table already had (measured — `tf_messages_entities` keeps its
 * four-column link to `tf_messages` and gains the tenant leg). What sqlite
 * cannot do is refuse `dropForeign()` BY NAME ("This database driver does not
 * support dropping foreign keys by name"), and it records neither constraint
 * names nor column signedness. So `down()` drops by the column list on a driver
 * that reports no name and by the explicit name on one that does, and the gate
 * that proves this migration works on the target engine is the MySQL probe
 * recorded in `tests/Schema/Mirror/TenantAccountForeignKeyTest.php`, not this
 * file's own sqlite exercise.
 *
 * ## THE COST, STATED RATHER THAN DISCOVERED
 *
 * Each `ALTER TABLE … ADD CONSTRAINT` is a separate, non-atomic rebuild on
 * InnoDB, and D-005 measured 265 of them at a 23.35 s floor on empty tables. On a
 * populated `tf_messages` that is a long rebuild needing free disk for a
 * shadow copy. The mirror is nearly empty right now — the plan
 * (`docs/superpowers/plans/2026-10-04-foreign-key-structure-plan.md`, §3) records
 * that this is the cheapest window that will ever exist, and that after the
 * history fill this becomes an online-DDL problem. The alternative — a trigger,
 * or a generated-column carrier — is strictly more machinery for a strictly
 * weaker guarantee, and D-005 measured and refuted both.
 *
 * ## THE COUNT PIN IS `up()`-ONLY, AND NEVER MOVES TO `down()`
 *
 * `EXPECTED_TENANT_TABLES` is 264 because that is what the engine reports at
 * THIS file's point in the dial — the point `up()` runs at. `down()` runs at a
 * different point in the same dial, and reverse-order rollback makes that point
 * hold a DIFFERENT number. `2026_10_10_000100`'s own `down()` runs BEFORE this
 * file's (its timestamp is later, so `migrate:rollback` reaches it first) and
 * restores `tf_updates_channel` / `_chat` / `_user`, each carrying `account_id`.
 * So by the time this `down()` runs, discovery returns exactly THREE tables more
 * than `up()` ever saw — not because anything is wrong, but because a rollback
 * is the exact inverse of a sequence in which those three tables did not yet
 * exist. MEASURED on both engines: MySQL 8.4.11, full dial `up()` then
 * `2026_10_10_000100->down()` then this `down()`, discovery went 256 -> 259 and
 * this `down()` completed. The ABSOLUTE count at that point is not written here
 * on purpose: it drifts with every family that adds or retires a `tf_*` table,
 * and the one number in this file that must stay a measurement is the pin.
 *
 * The pin therefore GUARDS `up()` ONLY, and `down()` discovers without it.
 * Bumping the constant to the rollback-time count would NOT be a fix and is
 * refused for the reason the docblock above gives: it would record a number
 * nobody measured in the direction the constant exists to protect, and it would
 * make the constant silently direction-dependent — a pin that reads 264 forwards
 * and 267 backwards is not a measurement, it is two unverified numbers sharing a
 * name.
 *
 * What `down()` still refuses on is the thing that cannot be recovered by
 * reordering: an unnamed driver, below. The count is the guard against a table
 * silently joining or leaving the SET; `down()` does not act on the set's size,
 * it acts on each table's own constraint, so an unmeasured count costs it nothing.
 */
return new class extends Migration
{
    /** The parent table. A tenant is one of its rows. */
    private const TENANT_TABLE = 'telegram_accounts';

    /** The column a tenant IS — D-005, and four citations in `src/`. */
    private const TENANT_KEY = 'user_id';

    /** The child column every one of the 264 carries. */
    private const TENANT_LEG = 'account_id';

    /** The UNIQUE index on the parent column, from `2026_10_04_000100`. */
    private const PARENT_UNIQUE = 'telegram_accounts_user_id_unique';

    /**
     * How many `tf_*` tables carry an `account_id`, MEASURED on MySQL 8.4.11
     * and independently on sqlite by booting the same dial.
     *
     * The command is in the block comment above, verbatim. If this ever reads
     * as a number this file could have guessed: it is the count of tables the
     * engine reports, asserted against the count the engine reports.
     */
    private const EXPECTED_TENANT_TABLES = 264;

    /** The mirror prefix. `telegram_accounts` and `telegram_apps` are not mirror tables. */
    private const MIRROR_PREFIX = 'tf_';

    /**
     * The suffix every constraint this file creates carries.
     *
     * The exact spelling is load-bearing twice over, and both halves were found
     * by MEASUREMENT on MySQL 8.4.11 rather than by choosing a nice name.
     *
     * **Length.** MySQL caps an identifier at 64 characters and sqlite caps it at
     * none. The longest `tf_*` table name in the shipped dial is 49 characters:
     *
     *   SELECT MAX(CHAR_LENGTH(TABLE_NAME)) FROM information_schema.TABLES
     *    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME LIKE 'tf\\_%';
     *     -> 49   (tf_stars_transactions_starref_commission_permille)
     *
     * and MySQL's own limit, probed one character at a time on a scratch
     * database, is exactly 64 accepted / 65 refused:
     *
     *   ERROR 1059 (42000): Identifier name '...' is too long
     *
     * So the budget for the suffix is 64 - 49 = 15 characters. The first
     * spelling tried, `_account_tenant_fk` (18), is 3 over and REFUSED THE FIRST
     * REAL ALTER on the target engine — while sqlite, which has no such limit,
     * accepted all 259 without complaint. That is the whole reason this constant
     * is measured rather than chosen: a longer, clearer suffix would have shipped
     * green in the suite and failed on every install.
     *
     * `TenantAccountForeignKeyTest` re-checks this budget against the engine's OWN
     * longest table name, so a future family that adds a longer table fails the
     * suite instead of the migration.
     *
     * **Uniqueness.** `_tenant_fk` is what `2026_10_04_000200_retarget_tenant_foreign_keys.php`
     * names the six retargeted constraints, and reusing it here would make
     * `down()` unable to tell whose constraint it is looking at — MEASURED: with
     * the suffix shared, `down()` dropped all 271, taking six constraints owned by
     * another migration with it, and left that install claiming a clean rollback
     * while holding no tenant constraint at all on those six tables. The `acc`
     * distinguishes them at a cost of 4 characters, and `_acc_tenant_fk` totals 63
     * — inside the budget, with one character to spare.
     */
    private const CONSTRAINT_SUFFIX = '_acc_tenant_fk';

    /**
     * How many constraints this run actually added.
     *
     * Recorded rather than returned so a reader can see the loop is not
     * silently skipping tables; `TenantAccountForeignKeyTest` is what proves it
     * skipped none.
     */
    private int $added = 0;

    public function up(): void
    {
        if (! Schema::hasTable(self::TENANT_TABLE)) {
            return;
        }

        $this->refuseWithoutParentKey();

        $added = 0;
        foreach ($this->tenantTables() as $table) {
            if ($this->tenantConstraintOn($table) !== null) {
                continue;
            }

            $name = $table.self::CONSTRAINT_SUFFIX;
            Schema::table($table, function (Blueprint $blueprint) use ($name): void {
                self::tenantLink($blueprint, $name);
            });
            $added++;
        }

        // A run that added nothing is either a re-run or a host where every
        // table already carries the constraint; both are legitimate, so this
        // is deliberately not an error. What WOULD be a defect is adding fewer
        // than the discovered set minus the tables already constrained — that
        // is caught by `TenantAccountForeignKeyTest`, which asserts the
        // declaration on every one of the 264 from the executed schema rather
        // than trusting this counter.
        $this->added = $added;
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::TENANT_TABLE)) {
            return;
        }

        // NO PIN. See `tenantTables()`'s second argument below for why `down()` is
        // discovered WITHOUT the count assertion. A legitimate rollback reaches
        // this line with MORE tables than `up()` ever saw, because
        // `2026_10_10_000100`'s own `down()` restores three `tf_*` tables that
        // carry `account_id`, and reverse-order rollback runs it FIRST.
        foreach ($this->tenantTables(false) as $table) {
            $declared = $this->tenantConstraintOn($table);

            if ($declared === null) {
                continue;
            }

            // Only constraints THIS migration created are dropped. A table
            // carrying one of the six retargeted constraints keeps it — that
            // constraint belongs to `2026_10_04_000200`, whose own down()
            // restores the `.id` form, and dropping it here would leave the
            // install with no tenant constraint at all while claiming to be a
            // rollback of this file.
            //
            // The ownership test is the NAME, and on a driver that reports no
            // name it cannot be made at all: sqlite records neither constraint
            // names nor any other provenance, so `name` is null for every
            // constraint it holds. Guessing there would drop another
            // migration's constraints; assuming otherwise would make `down()`
            // a silent NO-OP that reports success while removing nothing — the
            // worse of the two, because a rollback that does not roll back is
            // invisible until someone needs it.
            //
            // So the ambiguity is REFUSED rather than resolved by coin-flip.
            // An unnamed driver cannot roll this migration back safely, and
            // saying so is the difference between a diagnosable failure and an
            // install quietly missing 259 constraints it believes it has.
            if (! is_string($declared['name'] ?? null) || $declared['name'] === '') {
                throw new RuntimeException(sprintf(
                    'this driver records no foreign key constraint names, so down() cannot tell which of %s\'s '
                    .'tenant constraints belong to this migration and which are the six owned by '
                    .'2026_10_04_000200_retarget_tenant_foreign_keys.php. Refusing rather than guessing: '
                    .'dropping them all would silently remove another migration\'s constraints, and dropping '
                    .'none would report a successful rollback that changed nothing. Roll back on MySQL, where '
                    .'constraints are named, or remove the constraints by hand.',
                    $table,
                ));
            }

            if ($declared['name'] !== $table.self::CONSTRAINT_SUFFIX) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($declared): void {
                self::dropTenantConstraint($blueprint, $declared);
            });
        }
    }

    /**
     * THE SHARED LINK HELPER — the one place the tenant legs and the on-delete
     * rule are written.
     *
     * The same shape as the mirror's existing `linkChild()`,
     * `participantLink()`, `channelUpdateLink()` and `differenceLink()`: a
     * static method that emits the whole constraint, called by every table
     * instead of the join columns being restated per call site. Both halves
     * that could drift — which columns form the link, and what happens when the
     * parent goes — are constants, so there is nothing to drift.
     */
    private static function tenantLink(Blueprint $blueprint, string $name): void
    {
        $blueprint->foreign(self::TENANT_LEG, $name)
            ->references(self::TENANT_KEY)
            ->on(self::TENANT_TABLE)
            ->cascadeOnDelete();
    }

    /**
     * Drop the constraint that is actually there.
     *
     * The name is read from the schema rather than assumed. Guessing it would
     * compile to `drop foreign key <name>` and raise `ERROR 3941` on any
     * install whose constraint was renamed — and on sqlite, where no name is
     * reported at all, `dropForeign()` BY NAME refuses outright with "This
     * database driver does not support dropping foreign keys by name", so the
     * column list is the only spelling that works there. Measured: on a table
     * that also carries a four-column composite link to a `tf_*` parent,
     * `dropForeign(['account_id'])` removes ONLY the single-column tenant leg and
     * leaves the composite one and every column intact.
     *
     * @param  array<string, mixed>  $declared
     */
    private static function dropTenantConstraint(Blueprint $blueprint, array $declared): void
    {
        $name = $declared['name'] ?? null;

        if (is_string($name) && $name !== '') {
            $blueprint->dropForeign($name);

            return;
        }

        $blueprint->dropForeign([self::TENANT_LEG]);
    }

    /**
     * Every `tf_*` table that carries an `account_id`, read from the engine
     * this migration is running against.
     *
     * TWO BRANCHES, because the two engines answer the question differently and
     * both were MEASURED rather than assumed:
     *
     *  - MySQL: `information_schema`, which is the authoritative catalog on the
     *    engine the constraints actually have to work on. `DATABASE()` rather
     *    than a hardcoded name, because a scratch database, a tenant schema and
     *    a test schema are all legitimate homes for this migration.
     *  - Everything else (sqlite in the suite): the schema builder's own table
     *    listing plus a column check per table, which is what
     *    `bin/generate-relation-graph.php` already does for the same reason.
     *
     * `getTableListing()` hands back SCHEMA-QUALIFIED, PREFIXED names
     * ("main.PFX_tf_users") while the column and foreign-key lookups want the
     * BARE name, so both halves of that normalisation are done here. Measured on
     * a prefixed sqlite capsule: without stripping, every column lookup returns
     * empty and the discovery silently finds nothing.
     *
     * @param  bool  $pin  assert the discovered count against EXPECTED_TENANT_TABLES
     * @return list<string>
     */
    private function tenantTables(bool $pin = true): array
    {
        $connection = Schema::getConnection();

        $tables = $connection->getDriverName() === 'mysql'
            ? $this->tenantTablesFromInformationSchema($connection)
            : $this->tenantTablesFromSchemaBuilder($connection);

        sort($tables);

        if ($pin) {
            $this->refuseOnUnexpectedCount($tables);
        }

        return $tables;
    }

    /**
     * MySQL: ask the catalog.
     *
     * @param  Connection  $connection
     * @return list<string>
     */
    private function tenantTablesFromInformationSchema($connection): array
    {
        /** @var list<string> $rows */
        $rows = $connection->select(
            'SELECT DISTINCT TABLE_NAME AS name FROM information_schema.COLUMNS '
            .'WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = ? AND TABLE_NAME LIKE ?',
            [self::TENANT_LEG, self::MIRROR_PREFIX.'%'],
        );

        $tables = [];
        foreach ($rows as $row) {
            $name = (string) ($row->name ?? $row->TABLE_NAME ?? '');

            // `LIKE 'tf_%'` treats `_` as a single-character wildcard, so it
            // also matches `tfX…`. The prefix is therefore re-checked literally.
            // One character of sloppiness here would constrain a table outside
            // the mirror on a name that happens to look like one.
            if (str_starts_with($name, self::MIRROR_PREFIX)) {
                $tables[] = $name;
            }
        }

        return $tables;
    }

    /**
     * Any other driver: ask the schema builder, the way
     * `bin/generate-relation-graph.php` already does.
     *
     * @param  Connection  $connection
     * @return list<string>
     */
    private function tenantTablesFromSchemaBuilder($connection): array
    {
        $schema = $connection->getSchemaBuilder();
        $prefix = $connection->getTablePrefix();

        $tables = [];
        foreach ($schema->getTableListing() as $listed) {
            $bare = str_contains($listed, '.')
                ? substr($listed, (int) strrpos($listed, '.') + 1)
                : $listed;

            if ($prefix !== '' && str_starts_with($bare, $prefix)) {
                $bare = substr($bare, strlen($prefix));
            }

            if (! str_starts_with($bare, self::MIRROR_PREFIX)) {
                continue;
            }

            if (! in_array(self::TENANT_LEG, $schema->getColumnListing($bare), true)) {
                continue;
            }

            $tables[] = $bare;
        }

        return $tables;
    }

    /**
     * Refuse to run against a set of tables nobody measured.
     *
     * A silent no-op here would be the worst outcome available: the migration
     * would record itself applied, every table would look migrated, and the
     * 265 would quietly be some other number. Naming the discovered count and
     * the expectation in the error is the difference between a diagnosable
     * failure and a mystery three migrations later.
     *
     * @param  list<string>  $tables
     */
    private function refuseOnUnexpectedCount(array $tables): void
    {
        if (count($tables) === self::EXPECTED_TENANT_TABLES) {
            return;
        }

        throw new RuntimeException(sprintf(
            'discovered %d tf_* tables carrying an account_id column, but this migration was written '
            .'against %d (measured on MySQL 8.4.11 and on sqlite by booting the shipped dial; the command '
            .'is in this file\'s docblock). Refusing rather than constraining an unmeasured set: a silent '
            .'mismatch would record this migration as applied while leaving tables unconstrained, and the '
            .'next reader would have no way to tell. If a family legitimately gained or lost a table, '
            .'re-measure on the target engine, bump EXPECTED_TENANT_TABLES here, and bump '
            .'MEASURED_TENANT_TABLES in tests/Schema/Mirror/TenantAccountForeignKeyTest.php to match. '
            .'Never adjust either number to make a run green.',
            count($tables),
            self::EXPECTED_TENANT_TABLES,
        ));
    }

    /**
     * The single constraint on this table whose parent is `telegram_accounts`,
     * read back from the executed schema — or null when there is none.
     *
     * Reading beats assuming twice over: `up()` skips a table that already
     * carries the constraint (the six `2026_10_04_000200` retargeted, and any
     * install where this migration already ran), and `down()` leaves alone a
     * table whose constraint belongs to a different migration.
     *
     * @return array<string, mixed>|null
     */
    private function tenantConstraintOn(string $table): ?array
    {
        foreach (Schema::getConnection()->getSchemaBuilder()->getForeignKeys($table) as $foreign) {
            if ((string) ($foreign['foreign_table'] ?? '') === self::TENANT_TABLE) {
                return $foreign;
            }
        }

        return null;
    }

    /**
     * Refuse to proceed unless the parent column carries the unique index a
     * foreign key needs.
     *
     * MySQL will not reference a non-unique column and reports that from inside
     * a compound `ALTER` as `ERROR 6125 … Missing unique key for constraint
     * '<name this migration just invented>'` — which names the constraint and
     * leaves the operator no idea which PREREQUISITE is missing. The index comes
     * from `2026_10_04_000100_make_user_id_unsigned_unique.php`, and that file's
     * timestamp is what orders it first. The check costs one catalog read against
     * a table with one row per logged-in account.
     */
    private function refuseWithoutParentKey(): void
    {
        if (Schema::getConnection()->getSchemaBuilder()->hasIndex(self::TENANT_TABLE, self::PARENT_UNIQUE)) {
            return;
        }

        throw new RuntimeException(sprintf(
            'telegram_accounts.%s carries no UNIQUE index named %s, so MySQL will refuse these tenant '
            .'foreign keys with ERROR 6125 (Missing unique key). That index is created by '
            .'2026_10_04_000100_make_user_id_unsigned_unique.php, which must run FIRST — this migration also '
            .'depends on that file for the UNSIGNED half, because a signed parent and an unsigned account_id '
            .'cannot be constrained together at all (ERROR 3780, measured). Run the parent fix, then re-run '
            .'this one.',
            self::TENANT_KEY,
            self::PARENT_UNIQUE,
        ));
    }
};
