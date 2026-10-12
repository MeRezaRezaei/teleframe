<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Mirror;

use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use MeRezaRezaei\Teleframe\Tests\Store\ShippedStoreSchema;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

/**
 * Every `account_id`-bearing mirror table is a TENANT, and the database says so.
 *
 * ## WHAT IS BEING PROVED
 *
 * `tf_*.account_id` IS the tenant. Four independent places in `src/` say the
 * value written into that column is the Telegram user id, not the vault's app
 * row id:
 *
 *  - `src/Laravel/Services/TeleframeClient.php:113` — `@param int|null $accountId  Telegram user ID`
 *  - `src/Laravel/Console/BackfillCommand.php:165` — `--account= : Telegram account id (tenant) the history lands under`
 *  - `src/Teleframe/Vault/Vault.php:11-12` — "`TELEFRAME_DEFAULT_ACCOUNT_ID` is a Telegram user id (numeric) — looked up via `user_id` column"
 *  - `src/Teleframe/Vault/TelegramAccount.php:147` — "The poller knows the TELEGRAM id … **not the vault row id**"
 *
 * and `src/Laravel/MirrorIngesterFactory.php:160` writes it straight through:
 * `$payload['account_id'] = $accountId;`.
 *
 * `docs/decisions.md` D-005 resolved which parent column that value means:
 * `telegram_accounts.user_id`, NOT `.id`. Six shipped constraints pointed at
 * `.id` and were retargeted by `2026_10_04_000200_retarget_tenant_foreign_keys.php`.
 * Those six are the only tenant FKs in the dial. This file covers the REST —
 * the tables that carry a tenant and had no constraint saying so at all.
 *
 * ## WHY A ROOT IS IN SCOPE, AND WHY THAT IS NOT A SURPRISE
 *
 * The relation graph calls 45 tables `root` — a child of no other table. A root
 * is a fact with no parent WITHIN THE MIRROR; it still belongs to a tenant. The
 * tenant is the boundary ABOVE the mirror, so "no parent fact" says nothing
 * about "no parent tenant". A mirror row naming a tenant that does not exist is
 * the exact orphan this constraint exists to refuse, and it is most likely on a
 * root: a root has nothing else to be wrong.
 *
 * ## WHY THE COUNT IS DISCOVERED, NOT TRANSCRIBED
 *
 * The table list is read from the BUILT schema at run time — never written into
 * this file. A transcribed list is a second copy of the migration set, and it
 * drifts silently: the graph of this branch already records three defects that
 * shipped green, and a hand-maintained table list is the same disease in a
 * different file. `test_the_discovered_tenant_table_count_is_the_measured_one()`
 * pins the DISCOVERED count against the number measured on MySQL 8.4.11, so the
 * list cannot silently change length — but the tables themselves come from the
 * engine, so adding a 266th `tf_*` table is a one-line constant bump, not a
 * 266-line edit.
 *
 * That number, and the command that produced it, are recorded in
 * `2026_10_04_000700_add_tenant_foreign_keys_to_mirror_tables.php`'s docblock.
 * The same migration re-measures it independently on the engine it is applying
 * to, and refuses to proceed on a mismatch — so the two cannot drift apart
 * without one of them going red.
 *
 * ## WHY EVERY BEHAVIOURAL ASSERTION RUNS ON AN ENFORCING CONNECTION
 *
 * `ShippedStoreSchema` boots sqlite with `PRAGMA foreign_keys` OFF, where a
 * declared constraint is INERT: the DDL is in `sqlite_master`, and nothing
 * raises. A negative test written on that default is green because no check
 * happened. Every insert below therefore goes through
 * `RelationConstraintTestCase::enforcingConnection()`, which turns the pragma on
 * and READS IT BACK before handing the connection over — and every refusal is
 * asserted on the driver's FOREIGN KEY message, never on
 * `QueryException` alone, which "no such column" satisfies just as well.
 *
 * ## WHAT WOMAKE THESE TESTS WRONG
 *
 * If the engine ever starts writing the vault ROW id into `tf_*.account_id`,
 * every row here becomes a fabricated tenant and the constraint is right while
 * the code is wrong. That is the falsifier D-005 already records; if
 * `MirrorIngesterFactory.php:160` changes, re-decide rather than amend.
 */
final class TenantAccountForeignKeyTest extends RelationConstraintTestCase
{
    /**
     * Every shipped migration file, in apply order, NAMED BY PATH ONLY.
     *
     * Nothing about any table shape is written here. The tenant constraint
     * migration must be in this list or the suite is testing the dial without
     * it — which is the point of the migration-absent control below.
     *
     * @var list<string>
     */
    private const DIAL = 'all';

    /**
     * The number of `tf_*` tables carrying `account_id`, MEASURED on MySQL
     * 8.4.11 (see the migration docblock for the exact command), and measured
     * independently on sqlite by booting the same dial.
     *
     * It was 265 when `2026_10_04_000700` shipped. `da926d87 fix(mirror):
     * delete the dead tf_message_entities table` dropped one `tf_*` table that
     * carried an `account_id`, so this is 264 — re-measured on BOTH engines,
     * not decremented by arithmetic, and `tf_message_entities` is absent from
     * both measurements. That commit carries the writer-side evidence: ten
     * decoded payload shapes emit 0 rows into the deleted table and 18 into
     * `tf_messages_entities`, which stores every entity without loss because it
     * carries `position` in its key.
     *
     * This constant and `EXPECTED_TENANT_TABLES` in the migration are two
     * INDEPENDENT implementations of one discovery. They exist to be compared:
     * if they ever disagree, one of the two assertions here goes red naming the
     * difference. Bump BOTH, and never one to reach a green run.
     *
     * `tf_accounts` is not among them and cannot be: its own primary key IS
     * the Telegram id (`2026_09_22_000100_create_tf_accounts_table.php:25`, "One
     * row per Telegram account_id — NOT auto-increment, ids come from
     * Telegram"), so it has no `account_id` column to constrain.
     */
    private const MEASURED_TENANT_TABLES = 264;

    /**
     * The suffix `2026_10_04_000700_add_tenant_foreign_keys_to_mirror_tables.php`
     * names its constraints under. Asserted against the migration's own
     * constant below rather than transcribed from the file, because a test that
     * hardcodes the spelling it is checking cannot notice the spelling change
     * that broke the rollback.
     */
    private const CONSTRAINT_SUFFIX = '_acc_tenant_fk';

    /** The suffix `2026_10_04_000200_retarget_tenant_foreign_keys.php` uses. */
    private const RETARGET_SUFFIX = '_tenant_fk';

    /**
     * The six tables `2026_10_04_000200_retarget_tenant_foreign_keys.php`
     * already carries a tenant FK for. They are inside the 264 and are asserted
     * here too — a constraint that exists is a constraint that can be deleted
     * by a later migration, and only an assertion notices.
     *
     * @var list<string>
     */
    private const RETARGETED = [
        'tf_updates',
        'tf_update_routing',
        'tf_update_state',
        'tf_channel_participants',
        'tf_channel_updates',
        'tf_update_differences',
    ];

    /** A `telegram_accounts.user_id` that is NOT any vault row id. */
    private const TENANT = 1724372757;

    /**
     * A root mirror table, used for the behavioural proofs.
     *
     * `tf_admin_log_events` is chosen because it is a graph `root` AND has
     * exactly four columns, all NOT NULL — so the fixture cannot accidentally
     * satisfy the constraint through some other declared FK, and there is
     * nothing else to build. A child with its own composite FK to a `tf_*`
     * parent would need that parent built first, and an orphan in THAT table
     * would be refused by the WRONG constraint.
     *
     * @var array<string, mixed>
     */
    private const ROOT_CHILD = [
        'account_id' => self::TENANT,
        'id' => 1,
        'date' => 1700000000,
        'user_id' => 501558149,
    ];

    /** The same table, one tenant that `telegram_accounts` does not carry. */
    private const FABRICATED_TENANT = 999999999999;

    /**
     * The 264, discovered from the BUILT schema — the same discovery the
     * migration performs, re-implemented here on purpose.
     *
     * Two implementations of one discovery is not duplication to be removed; it
     * is the only way the migration's discovery is CHECKED rather than trusted.
     * If this method and the migration's `tenantTables()` ever disagree, one of
     * the two assertions below goes red naming the difference.
     *
     * @return list<string>
     */
    private function tenantTables(Connection $db): array
    {
        $schema = $db->getSchemaBuilder();

        // `getTableListing()` hands back SCHEMA-QUALIFIED, PREFIXED names
        // ("main.PFX_tf_users") while `hasColumn()`/`getForeignKeys()` want the
        // BARE name. Both halves of that normalisation are measured, not
        // assumed — see the class docblock of ShippedStoreSchema for the same
        // shape.
        $prefix = $db->getTablePrefix();
        $tables = [];
        foreach ($schema->getTableListing() as $listed) {
            $bare = str_contains($listed, '.') ? substr($listed, (int) strrpos($listed, '.') + 1) : $listed;
            if ($prefix !== '' && str_starts_with($bare, $prefix)) {
                $bare = substr($bare, strlen($prefix));
            }
            $tables[] = $bare;
        }

        $tenant = [];
        foreach ($tables as $table) {
            if (! str_starts_with($table, 'tf_')) {
                continue;
            }
            if (! in_array('account_id', $schema->getColumnListing($table), true)) {
                continue;
            }
            $tenant[] = $table;
        }

        sort($tenant);

        return $tenant;
    }

    /**
     * @return list<string>
     */
    /**
     * Migrations up to and INCLUDING the tenant-FK pin.
     *
     * `000700` pins the table count it sees when IT runs. Replaying the full dial puts
     * the schema LATER than that moment, so anything asking what the migration itself
     * does — such as the down()-refusal proof — must stop at the pin.
     */
    private function migrationsUpToThePin(): array
    {
        $pinned = '2026_10_04_000700_add_tenant_foreign_keys_to_mirror_tables.php';

        $files = $this->migrations();
        $out = [];
        foreach ($files as $f) {
            if (basename($f) <= $pinned) {
                $out[] = $f;
            }
        }

        return $out;
    }

    /**
     * How many tenant-bearing tables a migration AFTER the pinning one retires.
     *
     * Scans migration source for `Schema::drop…` of a `tf_*` table, resolving the
     * `Schema::dropIfExists(self::CONST)` form — the C5 fold drops its table by constant,
     * so matching the literal alone finds nothing and silently returns 0.
     *
     * Only migrations later than `2026_10_04_000700` count, because only those ran after
     * it and can therefore retire part of what it counted.
     */
    private function tenantTablesRetiredAfterThePin(): int
    {
        $pinned = '2026_10_04_000700_add_tenant_foreign_keys_to_mirror_tables.php';
        $retired = 0;

        foreach ($this->migrations() as $file) {
            if (basename($file) <= $pinned) {
                continue;
            }

            $src = (string) file_get_contents($file);

            if (preg_match_all("/Schema::drop(?:IfExists)?\('(tf_[a-z0-9_]+)'\)/", $src, $m)) {
                foreach ($m[1] as $name) {
                    $retired++;
                }
            }

            // `Schema::dropIfExists(self::CHILD)` names the table by CONSTANT — the C5 fold
            // does it that way — so the literal form above matches nothing. But a migration
            // declares MANY `private const` names, and only ONE of them is the dropped
            // table: the others are a column and two constraint names. Counting every
            // `tf_*` constant therefore over-counts (264 - 4 = 260 instead of 263).
            //
            // So resolve the constants actually passed to a drop, by name.
            if (preg_match_all('/Schema::drop(?:IfExists)?\(self::([A-Z_]+)\)/', $src, $d)) {
                foreach ($d[1] as $constName) {
                    if (preg_match("/private const {$constName} = '(tf_[a-z0-9_]+)'/", $src, $v)) {
                        $retired++;
                    }
                }
            }
        }

        return $retired;
    }

    private function migrations(): array
    {
        $dir = dirname(__DIR__, 3).'/src/Laravel/Migrations';
        $files = glob($dir.'/*.php') ?: [];
        sort($files);

        return $files;
    }

    /**
     * PROOF 1 — the discovered list is the MEASURED length.
     *
     * Read on an ENFORCING connection, so a discovery bug that only shows up
     * once the constraint exists is visible here rather than three tests later.
     */
    public function test_the_discovered_tenant_table_count_is_the_measured_one(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());

        $discovered = $this->tenantTables($db);

        // 🔴 TIMING, NOT DRIFT — and the same trap the MySQL lane hit on this dial.
        //
        // `2026_10_04_000700` pins the count it SEES at its own point in the dial, when
        // `tf_saved_star_gifts_gift_num` still exists and still carries a tenant leg: 264.
        // The C5 fold (`2026_10_05_000100_fold_saved_star_gift_num_onto_the_saved_gift_fact`)
        // runs LATER and drops that table, so migrating the FULL dial — which is what this
        // gate does — yields 263.
        //
        //     Failed asserting that actual size 263 matches expected size 264.
        //
        // Both numbers are true at the moment they are true. Decrementing the pin to 263
        // would break the MIGRATION, which really does see 264 and really must refuse on a
        // mismatch; bumping this gate to 264 would be a lie about the shipped schema.
        //
        // So the expectation is the pin MINUS the tables a later migration retires, derived
        // from the migration files rather than typed here — the same arithmetic the MySQL
        // lane applied, and the reason the C5 fold needed the same treatment twice is that
        // "how many tenant tables exist" has TWO honest answers depending on WHEN you ask.
        $retired = $this->tenantTablesRetiredAfterThePin();
        $expected = self::MEASURED_TENANT_TABLES - $retired;

        self::assertGreaterThanOrEqual(
            0,
            $retired,
            'sanity: the retirement count cannot be negative',
        );

        self::assertCount(
            $expected,
            $discovered,
            'the number of tf_* tables carrying account_id moved. That is expected when a family gains '
            .'or loses a table — but then the number in this file AND the number in '
            .'2026_10_04_000700_add_tenant_foreign_keys_to_mirror_tables.php are both stale, and the '
            .'migration refuses to run on a mismatch, so this failing means the constant was bumped in one '
            .'place only. Re-measure on the target engine and bump BOTH. Never edit this count to make a '
            .'green run: it is the only thing standing between a deleted mirror table and a silent green.',
        );

        self::assertNotContains(
            'tf_accounts',
            $discovered,
            'tf_accounts has no account_id column — its own primary key is the Telegram id. If it has grown '
            .'one, this file\'s claim that it "cannot be in the list" is stale.',
        );
    }

    /**
     * PROOF 2 — the DECLARATION, on every one of the 264.
     *
     * Asserted before the behaviour for the reason
     * `RelationConstraintsAreEnforcedTest` sets out: a negative test cannot
     * tell "the constraint bit" from "this table never had one". Reading the
     * executed DDL first closes that gap — deleting the migration fails HERE
     * even if every behavioural test below were deleted with it.
     */
    public function test_every_tenant_table_declares_the_tenant_foreign_key(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());

        $missing = [];
        $wrong = [];

        foreach ($this->tenantTables($db) as $table) {
            $found = null;
            foreach ($db->getSchemaBuilder()->getForeignKeys($table) as $foreign) {
                if ((string) ($foreign['foreign_table'] ?? '') !== 'telegram_accounts') {
                    continue;
                }
                $found = $foreign;
            }

            if ($found === null) {
                $missing[] = $table;

                continue;
            }

            $legs = array_map('strval', $found['foreign_columns'] ?? []);
            $rule = strtoupper((string) ($found['on_delete'] ?? ''));
            if ($legs !== ['user_id'] || $rule !== 'CASCADE') {
                $wrong[] = "{$table}: legs=[".implode(',', $legs)."] on_delete={$rule}";
            }
        }

        self::assertSame(
            [],
            $missing,
            'these tf_* tables carry a tenant in account_id and declare NO foreign key to telegram_accounts, '
            .'so a mirror row naming a tenant that does not exist is admitted. Delete '
            .'2026_10_04_000700_add_tenant_foreign_keys_to_mirror_tables.php and this is the failure you get — '
            .'which is why it is asserted, not assumed.',
        );

        self::assertSame(
            [],
            $wrong,
            'a tenant foreign key exists but points somewhere other than telegram_accounts.user_id, or does not '
            .'CASCADE. D-005 measured that retargeting is the only disposition that is both correct and '
            .'enforced, and that dropping the constraint admits orphans — so a wrong leg here is an active '
            .'hazard, not a missing safety net.',
        );
    }

    /**
     * PROOF 2c — every constraint NAME fits the engine's identifier budget.
     *
     * MEASURED on MySQL 8.4.11: the limit is exactly 64 characters accepted, 65
     * refused with `ERROR 1059 ... Identifier name is too long`. The longest
     * `tf_*` table name is 49, so the budget for a suffix is 15 characters.
     *
     * This test exists because the first spelling tried, `_account_tenant_fk`
     * (18), was 3 over — and sqlite, which has NO identifier limit at all,
     * accepted all 259 constraints with it. The suite was green and the real
     * engine refused the first ALTER. A constraint name is therefore checked
     * here against the engine's OWN longest table name rather than trusted, so
     * a family that adds a longer table fails here instead of on an install.
     */
    public function test_every_constraint_name_fits_the_engine_identifier_budget(): void
    {
        // MySQL's documented and MEASURED cap. Not a round number of convenience.
        $limit = 64;

        $db = $this->enforcingConnection(...$this->migrations());
        $tables = $this->tenantTables($db);

        self::assertNotSame([], $tables, 'discovery found no tenant tables, so nothing below is proven');

        $suffix = self::CONSTRAINT_SUFFIX;
        self::assertNotSame(
            self::RETARGET_SUFFIX,
            $suffix,
            'this migration must NOT reuse the suffix 2026_10_04_000200 uses. Sharing it makes down() unable to '
            .'tell whose constraint it is dropping: MEASURED, with the suffix shared, down() removed all '
            .'271 and took the six owned by the other migration with it, leaving that install claiming a '
            .'clean rollback while holding no tenant constraint on those six tables.',
        );

        $longest = '';
        foreach ($tables as $table) {
            $name = $table.$suffix;
            self::assertLessThanOrEqual(
                $limit,
                strlen($name),
                "the constraint name for {$table} is ".strlen($name)." characters, over MySQL's "
                ."{$limit}-character identifier limit, which it refuses with ERROR 1059. sqlite has no such "
                .'limit, so a name that is too long here passes every sqlite test and fails on the first real '
                .'ALTER. Shorten CONSTRAINT_SUFFIX in the migration — do not shorten it here, because the '
                .'test and the migration must agree on the same spelling.',
            );
            if (strlen($table) > strlen($longest)) {
                $longest = $table;
            }
        }

        // Recorded so a reader can see the budget this run actually had, rather
        // than inferring it from the assertion passing.
        self::assertLessThanOrEqual(
            $limit,
            strlen($longest.$suffix),
            "the longest tenant table ({$longest}) plus the suffix is over the budget.",
        );
    }

    /**
     * PROOF 2d — `down()` REFUSES on a driver that cannot express ownership.
     *
     * This is the honest half of the rollback story, and it is the half that was
     * a defect first: `down()` began as a silent NO-OP on sqlite. It identified
     * a table's tenant constraint by NAME, sqlite records no constraint names at
     * all, so every table failed the check and nothing was dropped — while the
     * migration reported a clean rollback. A rollback that does not roll back is
     * invisible until somebody needs it.
     *
     * The alternative — dropping the constraint from every table that has one —
     * is worse in the other direction: it takes the six constraints owned by
     * `2026_10_04_000200_retarget_tenant_foreign_keys.php` along with this
     * migration's 259, leaving that install with no tenant constraint on those
     * six tables while its migration log claims a clean rollback of this file.
     *
     * So the ambiguity is refused and named. Asserted here because "it throws"
     * is a behaviour a later refactor would happily turn back into "it silently
     * does the wrong thing", and because the MESSAGE is what tells an operator
     * what to do about it.
     */
    public function test_down_refuses_rather_than_guessing_at_constraint_ownership(): void
    {
        $migration = require dirname(__DIR__, 3)
            .'/src/Laravel/Migrations/2026_10_04_000700_add_tenant_foreign_keys_to_mirror_tables.php';

        // 🔴 The dial must stop AT the pinning migration.
        //
        // This test proves `down()` REFUSES on SQLite because the driver records no foreign
        // key constraint names. But `000700`'s own count guard runs FIRST, and the full
        // dial now migrates 263 tenant tables (the C5 fold drops one AFTER the pin), so
        // `down()` was failing the count guard and the proof never reached the guard it is
        // actually about:
        //
        //     Failed asserting that 'discovered 263 … written against 264 …' contains
        //     'no foreign key constraint names'
        //
        // Migrating up to and including the pin puts the schema back at the moment the
        // migration was written for — which is also the honest way to ask "what does THIS
        // migration do on its own?", rather than asking it about a dial that had not
        // finished when it ran.
        $db = $this->enforcingConnection(...$this->migrationsUpToThePin());

        $witness = 'tf_admin_log_events';

        self::assertContains(
            $witness,
            $this->tenantTables($db),
            "{$witness} must be a tenant table for this proof to mean anything.",
        );
        self::assertCount(
            1,
            array_filter(
                $db->getSchemaBuilder()->getForeignKeys($witness),
                static fn (array $fk): bool => (string) ($fk['foreign_table'] ?? '') === 'telegram_accounts',
            ),
            "{$witness} must carry exactly one tenant constraint before down() is attempted.",
        );

        $refused = null;
        try {
            $migration->down();
        } catch (RuntimeException $e) {
            $refused = $e;
        }

        self::assertNotNull(
            $refused,
            'down() completed on sqlite, which records no foreign key constraint names. It therefore cannot '
            .'distinguish the 259 constraints this migration created from the six owned by '
            .'2026_10_04_000200_retarget_tenant_foreign_keys.php — so completing means it either dropped '
            .'constraints owned by another migration, or dropped nothing at all while reporting success. Both '
            .'are defects; refusing is the only correct outcome on this driver.',
        );
        self::assertStringContainsString(
            'no foreign key constraint names',
            $refused->getMessage(),
            'down() must refuse for the MEASURED reason — the driver reports no constraint names — so the '
            .'message tells an operator what to do instead of only that something went wrong.',
        );

        // And it refused BEFORE changing anything: a refusal that has already
        // dropped four of the 264 is not a refusal.
        self::assertCount(
            1,
            array_filter(
                $db->getSchemaBuilder()->getForeignKeys($witness),
                static fn (array $fk): bool => (string) ($fk['foreign_table'] ?? '') === 'telegram_accounts',
            ),
            "{$witness} lost its tenant constraint even though down() refused. The check must happen before "
            .'any table is altered, or the refusal leaves the schema half-rolled-back.',
        );
    }

    /**
     * PROOF 2b — the six retargeted tables are in the list and still constrained.
     *
     * Named explicitly rather than left to the sweep above, because they are the
     * only tenant FKs the dial shipped with: if a later migration drops one of
     * THEM, the sweep still passes (it only checks "some constraint to
     * telegram_accounts exists" — and it would not, so this is belt and braces
     * against the shape where a partial sweep hides a specific regression).
     */
    #[DataProvider('retargetedTables')]
    public function test_a_retargeted_table_still_carries_its_tenant_foreign_key(string $table): void
    {
        $db = $this->enforcingConnection(...$this->migrations());

        $declared = array_values(array_filter(
            $db->getSchemaBuilder()->getForeignKeys($table),
            static fn (array $fk): bool => (string) ($fk['foreign_table'] ?? '') === 'telegram_accounts',
        ));

        self::assertCount(
            1,
            $declared,
            "{$table} is one of the six D-005 found aiming at telegram_accounts.**id**. "
            .'2026_10_04_000200_retarget_tenant_foreign_keys.php retargeted it to user_id; a count other than 1 '
            .'means a later migration dropped or duplicated it.',
        );
        self::assertSame(
            ['user_id'],
            array_map('strval', $declared[0]['foreign_columns'] ?? []),
            "{$table}: the tenant IS the Telegram user id (D-005, four citations in src/). A leg naming `.id` "
            .'refuses every real engine row and admits only the fabricated app row id.',
        );
        self::assertSame('CASCADE', strtoupper((string) ($declared[0]['on_delete'] ?? '')));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function retargetedTables(): array
    {
        $cases = [];
        foreach (self::RETARGETED as $table) {
            $cases[$table] = [$table];
        }

        return $cases;
    }

    /**
     * PROOF 3 — a CONTROL: the same orphan is ADMITTED when the tenant
     * constraint is not in the dial.
     *
     * This is what makes proofs 4 and 5 mean anything. Without it they could be
     * green for a reason that has nothing to do with this migration — and the
     * pragma-off default this repo ships is exactly such a reason. The orphan
     * below is the SAME ROW, inserted into the SAME table, on the SAME engine.
     */
    public function test_the_orphan_is_admitted_without_the_tenant_migration(): void
    {
        $withheld = array_values(array_filter(
            $this->migrations(),
            static fn (string $file): bool => ! str_contains(basename($file), '2026_10_04_000700'),
        ));

        // The control withholds exactly one file, named by substring. If the
        // migration is absent the filter is a no-op — and then this control is
        // not a control. Asserted rather than assumed, because "it passed"
        // means opposite things in the two cases.
        self::assertSame(
            1,
            count($this->migrations()) - count($withheld),
            'the control withholds exactly one file, and it must be the tenant migration. If the filter '
            .'matched nothing, this control is not a control and proof 4 would be proving something other '
            .'than what it claims.',
        );

        // Deliberately the shipped default, NOT enforcingConnection(): the point
        // is what the schema the dial ships WITHOUT the migration permits. The
        // pragma state is asserted so a future default cannot quietly change
        // what this control means.
        $permissive = ShippedStoreSchema::capsuleFor(...$withheld)
            ->getConnection();
        self::assertSame(
            0,
            $this->foreignKeysPragma($permissive),
            'this control relies on the shipped pragma-off default. If that changed, the difference it '
            .'demonstrates would come from the pragma rather than from the missing migration.',
        );

        $permissive->table('tf_admin_log_events')->insert([
            'account_id' => self::FABRICATED_TENANT,
            'id' => 1,
            'date' => 1700000000,
            'user_id' => 501558149,
        ]);

        self::assertSame(
            1,
            $permissive->table('tf_admin_log_events')->count(),
            'the fabricated tenant must actually LAND on the dial without the tenant migration. If this row '
            .'were refused, proofs 4 and 5 would be proving something other than what they claim.',
        );
    }

    /**
     * PROOF 4 — the SAME orphan is REFUSED once the tenant constraint ships.
     *
     * On an ENFORCING connection, and asserted on the driver's FOREIGN KEY
     * message rather than on `QueryException`, which a wrong fixture satisfies
     * just as happily.
     */
    public function test_the_fabricated_tenant_is_refused(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());

        try {
            $db->table('tf_admin_log_events')->insert([
                'account_id' => self::FABRICATED_TENANT,
                'id' => 1,
                'date' => 1700000000,
                'user_id' => 501558149,
            ]);
            self::fail(
                'a mirror row naming tenant '.self::FABRICATED_TENANT.' — which telegram_accounts does not '
                .'carry — was ACCEPTED on a connection whose PRAGMA foreign_keys was read back as 1. Either '
                .'the tenant constraint is not declared on tf_admin_log_events, or enforcement is not on, and '
                .'both mean this suite is not a gate.',
            );
        } catch (QueryException $e) {
            self::assertStringContainsString(
                'FOREIGN KEY constraint failed',
                $e->getMessage(),
                'the insert must be refused BY THE FOREIGN KEY. Any other QueryException means the fixture is '
                .'wrong — it is not evidence the constraint bit.',
            );
        }

        self::assertSame(
            0,
            $db->table('tf_admin_log_events')->count(),
            'a refused insert must leave nothing behind.',
        );
    }

    /**
     * PROOF 5 — the legitimate row LANDS, and deleting the tenant CASCADES.
     *
     * The half that is easy to forget: a constraint that refuses everything is
     * not a tenant FK, it is an outage. So a row carrying the real Telegram id
     * must insert, and removing the parent must take the child with it — which
     * is what "a child row must never outlive its tenant" means in practice.
     */
    public function test_a_real_tenant_row_lands_and_deleting_the_tenant_cascades(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());

        $db->table('telegram_accounts')->insert([
            'label' => 'probe',
            'type' => 'user',
            'user_id' => self::TENANT,
        ]);

        $db->table('tf_admin_log_events')->insert(self::ROOT_CHILD);

        self::assertSame(
            1,
            $db->table('tf_admin_log_events')->count(),
            'a row carrying a tenant telegram_accounts actually carries must be ADMITTED. A constraint that '
            .'refuses real engine rows is worse than no constraint: six shipped FKs aimed at telegram_accounts.'
            .'id did exactly that, and D-005 measured it refusing every row the engine writes.',
        );

        $db->table('telegram_accounts')->where('user_id', self::TENANT)->delete();

        self::assertSame(
            0,
            $db->table('tf_admin_log_events')->count(),
            'deleting a tenant must take its mirror rows with it (ON DELETE CASCADE). A child row outliving '
            .'its tenant is the state the whole mirror is tenant-scoped to prevent.',
        );
    }

    /**
     * The harness must be the thing doing the refusing, not the fixture.
     *
     * One assertion so a reader does not have to infer it from the two proofs
     * above: both insert the SAME row into the SAME table, and the only
     * difference between them is this connection.
     */
    public function test_the_two_proofs_differ_only_by_the_connection(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());

        Assert::assertSame(
            1,
            $this->foreignKeysPragma($db),
            'proofs 4 and 5 are only meaningful on an enforcing connection; if this reads 0 the harness '
            .'stopped working and every refusal above is unproven.',
        );

        $db->table('telegram_accounts')->insert([
            'label' => 'probe',
            'type' => 'user',
            'user_id' => self::TENANT,
        ]);
        $db->table('tf_admin_log_events')->insert(self::ROOT_CHILD);

        self::assertSame(1, $db->table('tf_admin_log_events')->count());
    }
}
