<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Mirror;

use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use MeRezaRezaei\Teleframe\Tests\Store\ShippedStoreSchema;

/**
 * RED GATE: the six shipped tenant FKs point at the wrong parent column, so
 * they refuse every row the engine actually writes.
 *
 * ## THE SIX, AND WHY THESE SIX
 *
 * Six shipped constraints declare `account_id → telegram_accounts.**id**` —
 * the vault's APP ROW id — instead of `user_id`, the Telegram user id. They
 * are declared inline in the two create migrations, one line each:
 *
 * | table | declaration site (measured) |
 * | --- | --- |
 * | `tf_updates` | `2026_09_14_200030_create_tf_updates_tables.php:46` |
 * | `tf_update_routing` | `2026_09_14_200030_create_tf_updates_tables.php:248` |
 * | `tf_update_state` | `2026_09_14_200030_create_tf_updates_tables.php:301` |
 * | `tf_channel_participants` | `2026_09_14_200031_create_tf_channel_participants_tables.php:84` |
 * | `tf_channel_updates` | `2026_09_14_200031_create_tf_channel_participants_tables.php:157` |
 * | `tf_update_differences` | `2026_09_14_200031_create_tf_channel_participants_tables.php:197` |
 *
 * All six are the identical statement —
 * `$table->foreign('account_id')->references('id')->on('telegram_accounts')->cascadeOnDelete()`
 * — so `grep -n "foreign('account_id')" ` over the two files returns exactly
 * those six lines and no others. They are the ONLY constraints in the curated
 * dial whose parent is `telegram_accounts`; every other shipped FK points at a
 * `tf_*` table. `docs/decisions.md` D-005 measures the same six from the other
 * side (54 constraints with `account_id` among the child columns: 48 to a
 * `tf_*` parent, 6 to `telegram_accounts.id`).
 *
 * ## WHY THE ENGINE'S VALUE IS THE TELEGRAM USER ID — four citations, all in `src/`
 *
 * | evidence | says |
 * | --- | --- |
 * | `src/Laravel/Services/TeleframeClient.php:113` | `@param int\|null $accountId  Telegram user ID` |
 * | `src/Laravel/Console/BackfillCommand.php:165` | `--account= : Telegram account id (tenant) the history lands under` |
 * | `src/Teleframe/Vault/Vault.php:11-12` | `TELEFRAME_DEFAULT_ACCOUNT_ID` "is a Telegram user id (numeric) — looked up via `user_id` column" |
 * | `src/Teleframe/Vault/TelegramAccount.php:147` | "The poller knows the TELEGRAM id …, **not the vault row id**" |
 *
 * and `src/Laravel/MirrorIngesterFactory.php:160` writes that value straight
 * into every mirror row (`$payload['account_id'] = $accountId;`). So the engine
 * and the six constraints disagree about what a tenant IS.
 *
 * ## WHAT THIS FILE PROVES
 *
 * `test_a_real_engine_row_must_land_in_each_of_the_six()` inserts the value the
 * engine writes — the Telegram user id — into all six tables, on a connection
 * whose `PRAGMA foreign_keys` has been read back as **1**, and requires every
 * one of them to LAND.
 *
 * This file was a RED gate: every one of the six was REFUSED, measured against
 * the shipped dial at commit `4912c00a`. It is GREEN as of
 * `2026_10_04_000200_retarget_tenant_foreign_keys.php`, and the red was the
 * point — a red test is the artifact that keeps a defect from being closed by
 * silence. The migration list below now ends with the parent fix
 * (`2026_10_04_000100`) and the retarget (`2026_10_04_000200`); against the
 * first four files alone this file is red again, which is the honest state of
 * the dial before those two.
 *
 * Three controls keep it honest, and each fails in a different direction:
 *
 *  1. `test_each_declares_one_cascade_constraint_on_the_telegram_user_id()` —
 *     pins the declaration itself (`foreign_columns === ['user_id']`, one
 *     constraint, `ON DELETE CASCADE`) and then proves the constraint fires in
 *     BOTH directions: the real tenant lands, the app row id is refused.
 *     Without this, a schema where the constraint had been DROPPED outright
 *     would pass the gate above for the wrong reason. This control was renamed
 *     and re-pointed when the retarget landed; its own docblock records the
 *     measured failure and why the old "the app row id is admitted" claim was
 *     structurally incompatible with a constraint that references `user_id`.
 *  2. The parent row is inserted on every connection before the child rows, so
 *     a refusal can only be the column being compared, never a missing parent.
 *  3. Every refusal is matched on the driver message
 *     (`FOREIGN KEY constraint failed`), because `expectException(QueryException::class)`
 *     alone is satisfied by a typo — the failure mode
 *     `RelationConstraintsAreEnforcedTest` documents at length.
 *
 * ## WHY THE SIX ARE DORMANT UNTIL SOMEBODY TURNS THE PRAGMA ON
 *
 * `ShippedStoreSchema` boots sqlite with `PRAGMA foreign_keys` OFF — asserted
 * by `RelationConstraintTestCase::enforcingConnection()` on every call here. So
 * no shipped suite ever executes these six. They wake up the moment a host
 * adopts the pragma recommendation in
 * `docs/superpowers/plans/2026-10-04-foreign-key-structure-plan.md:104`, and on
 * that host the ingest path fails at its first write. An absent constraint is a
 * missing net; a WRONG one is an active hazard — which is why D-005 ranks these
 * six above the 265 absent tenant constraints.
 *
 * ## THE PARENT FIX LANDED, AND THE ORDER WAS FORCED — MEASURED, NOT ASSUMED
 *
 * `telegram_accounts.user_id` shipped as `bigInteger()` — SIGNED —
 * (`2026_09_09_000201_create_telegram_accounts_table.php:28`), while every
 * `tf_*.account_id` is `unsignedBigInteger()`. MySQL refuses that pair at DDL
 * time. Measured on MySQL 8.4.11 in a throwaway database, on the shipped shapes:
 *
 * ```
 * === STEP 3: RETARGET while the parent is signed ===
 * ERROR 3780 (HY000) at line 27: Referencing column 'account_id' and referenced
 * column 'user_id' in foreign key constraint 'updates_account_fk' are incompatible.
 * ```
 *
 * and the same probe showed the retarget WORKS once the parent is unsigned
 * (`MODIFY user_id BIGINT UNSIGNED NULL` + `UNIQUE (user_id)`): the real engine
 * row is ACCEPTED, a fabricated tenant is REFUSED `ERROR 1452`, and
 * `ON DELETE CASCADE` still takes the child from 1 row to 0. Reverting the
 * parent while the retargeted FK lives raises the SAME `ERROR 3780`, so the
 * ordering is one-way.
 *
 * `2026_10_04_000100_make_user_id_unsigned_unique.php` is that parent fix
 * (candidate A of D-005); `2026_10_04_000200_retarget_tenant_foreign_keys.php`
 * is the retarget, and its timestamp is what orders it second. The six
 * constraints are dropped and re-added inside the new migration — no create
 * migration is edited, because an install that already ran one would keep the
 * broken constraint forever.
 *
 * `test_the_retarget_target_column_is_still_signed()` below still passes and is
 * still meaningful: it asserts the CREATE migration still says
 * `bigInteger('user_id')`, which is exactly what an additive parent fix leaves
 * alone. It detects an in-place edit of the create migration — which D-005
 * forbids and which would strand existing installs — and nothing else.
 *
 * ## A SQLITE PASS IS NOT THE PROOF, AND THE MYSQL PROBE IS WHERE IT LIVES
 *
 * sqlite records no signedness and no constraint names, so this suite cannot
 * see the ERROR 3780 that made the retarget inexpressible in the first place,
 * and cannot see the explicit `{table}_tenant_fk` names the migration creates.
 * The whole original blocker was a MySQL-only DDL refusal. The evidence that
 * the retargeted constraints work on the real engine — MySQL 8.4.11, a scratch
 * database, created and dropped — is therefore a probe outside this suite, not
 * a claim this file makes.
 *
 * ## NOTHING HERE PINS THE DEFECT
 *
 * Every assertion in this file is about what a CORRECT schema must do. A
 * migration that drops the six instead of retargeting them turns the gate green
 * for the wrong reason, which is why control 1 above pins the referenced column
 * and why the fabricated-tenant refusal has its own test.
 */
final class TenantForeignKeyTargetTest extends RelationConstraintTestCase
{
    /**
     * The minimum shipped migration set that produces `telegram_accounts` (with
     * its `user_id` column) and all six constrained children.
     *
     * Named in apply order, nothing about a table shape transcribed. Verified
     * by execution — this list migrates clean on sqlite.
     *
     * @var list<string>
     */
    private const MIGRATIONS = [
        '2026_09_09_000200_create_telegram_apps_table.php',
        '2026_09_09_000201_create_telegram_accounts_table.php',
        '2026_09_14_200030_create_tf_updates_tables.php',
        '2026_09_14_200031_create_tf_channel_participants_tables.php',
        '2026_10_04_000100_make_user_id_unsigned_unique.php',
        '2026_10_04_000200_retarget_tenant_foreign_keys.php',
    ];

    /**
     * The Telegram user id the engine writes. Not a credential: it is the tenant
     * number a fixture needs to be a REAL tenant, and it is the same value D-005
     * measured against (`docs/decisions.md`, D-005, Blocker 2).
     */
    private const TELEGRAM_USER_ID = 1724372757;

    /**
     * The vault row id that lands on `telegram_accounts.id` — 1, because the
     * row below is the first insert into an empty table.
     *
     * This is the value the six SHIPPED constraints admitted, and the value the
     * retargeted ones REFUSE. Control 1 inserts it and requires the refusal, so
     * the constant is still what proves the constraints exist: a constraint that
     * had been dropped outright would admit it, and the test would fail.
     */
    private const VAULT_ROW_ID = 1;

    /**
     * A tenant that does not exist, used by the red test's own aftermath check.
     */
    private const FABRICATED_TENANT = 1234567890;

    /**
     * One real engine row per table, keyed exactly as the shipped DDL requires.
     *
     * Every row's `account_id` is the Telegram user id — the value
     * `MirrorIngesterFactory.php:160` puts there — and nothing else in these
     * rows is unusual, so a refusal can only be the tenant column.
     *
     * @var array<string, array<string, mixed>>
     */
    private const ENGINE_ROWS = [
        'tf_updates' => [
            'account_id' => self::TELEGRAM_USER_ID,
            'seq' => 1,
            'position' => 0,
            'constructor' => 'updateNewMessage',
        ],
        'tf_update_routing' => [
            'account_id' => self::TELEGRAM_USER_ID,
            'seq' => 1,
            'position' => 0,
            'constructor' => 'updateNewMessage',
            'msg_id' => 501558149,
        ],
        'tf_update_state' => [
            'account_id' => self::TELEGRAM_USER_ID,
            'pts' => 7,
            'qts' => 3,
            'date' => 1758000000,
            'seq' => 1,
            'unread_count' => 0,
        ],
        'tf_channel_participants' => [
            'account_id' => self::TELEGRAM_USER_ID,
            'channel_id' => -1001234567890,
            'user_id' => 501558149,
            'constructor' => 'channelParticipant',
        ],
        'tf_channel_updates' => [
            'account_id' => self::TELEGRAM_USER_ID,
            'channel_id' => -1001234567890,
            'position' => 0,
            'constructor' => 'updateNewChannelMessage',
        ],
        'tf_update_differences' => [
            'account_id' => self::TELEGRAM_USER_ID,
            'position' => 0,
            'constructor' => 'updates.differenceEmpty',
        ],
    ];

    /**
     * @return list<string>
     */
    private function migrations(): array
    {
        return array_map(
            static fn (string $file): string => dirname(__DIR__, 3).'/src/Laravel/Migrations/'.$file,
            self::MIGRATIONS,
        );
    }

    /**
     * The parent row the engine's tenant resolves against: vault row id 1,
     * carrying Telegram user id 1724372757.
     *
     * `session` and `bot_token` are left NULL deliberately — this fixture never
     * needs a credential, and a test is not the place to put one.
     *
     * @return array<string, mixed>
     */
    private function parentRow(): array
    {
        return [
            'app_id' => null,
            'label' => 'tenant fixture',
            'type' => 'user',
            'user_id' => self::TELEGRAM_USER_ID,
            'owner_type' => null,
            'owner_id' => null,
            'session' => null,
            'bot_token' => null,
        ];
    }

    /**
     * A connection whose `PRAGMA foreign_keys` is read back as 1, with the
     * parent row already present. Enforced by the base class, never asserted
     * here.
     */
    private function enforcingWithParent(): Connection
    {
        $db = $this->enforcingConnection(...$this->migrations());
        $db->table('telegram_accounts')->insert($this->parentRow());

        return $db;
    }

    /**
     * THE GATE. Red today, green once the six are retargeted.
     *
     * For each of the six: a real engine row (Telegram user id) must LAND on a
     * connection that is provably enforcing. Any refusal is reported with the
     * driver's own message so the failure names the constraint rather than just
     * "an exception happened".
     */
    public function test_a_real_engine_row_must_land_in_each_of_the_six(): void
    {
        $refusals = [];

        foreach (self::ENGINE_ROWS as $table => $row) {
            $db = $this->enforcingWithParent();

            try {
                $db->table($table)->insert($row);
            } catch (QueryException $e) {
                $refusals[$table] = self::firstLine($e->getMessage());

                continue;
            }

            self::assertSame(
                1,
                $db->table($table)->count(),
                "{$table}: the real engine row must LAND once this table's account_id references "
                .'telegram_accounts.user_id. If it is present, the constraint has been dropped rather than '
                .'retargeted — which admits a fabricated tenant too, and is the disposition this gate exists '
                .'to reject.',
            );
        }

        self::assertSame(
            [],
            $refusals,
            "these tables REFUSED the row the engine actually writes. The engine puts the Telegram user id\n"
            ."in account_id (src/Laravel/MirrorIngesterFactory.php:160), and these six constraints compare\n"
            ."it against telegram_accounts.id — the vault's app row id — so they refuse every real ingest.\n"
            ."Dormant only while PRAGMA foreign_keys is OFF. The fix is a retarget to user_id, which needs\n"
            .'telegram_accounts.user_id to be unsigned first (ERROR 3780 until then, see the docblock).',
        );
    }

    /**
     * CONTROL 1 — the six constraints EXIST, target `telegram_accounts.user_id`,
     * and are the reason a row is accepted or refused.
     *
     * The gate above is satisfied by a table that declares nothing, so this test
     * pins the declaration itself, read back from the executed DDL, and then
     * proves it bites twice — once in each direction.
     *
     * ## WHY THIS TEST NO LONGER ADMITS THE APP ROW ID, MEASURED
     *
     * As first written it asserted the value the six DID admit — vault row id
     * `1`, read off the shipped `telegram_accounts.id` — and its own docblock
     * said what to do when the retarget landed: "this test goes red naming
     * `user_id`, and at that point it must be re-pointed at the corrected
     * declaration rather than deleted". That is what happened. The measured
     * failure, verbatim from the run against the retargeted schema:
     *
     *   Illuminate\Database\QueryException: SQLSTATE[23000]: Integrity
     *   constraint violation: 19 FOREIGN KEY constraint failed
     *   (SQL: insert into "tf_updates" ("account_id", "seq", "position",
     *   "constructor") values (1, 1, 0, updateNewMessage))
     *
     * and the incompatibility is structural, not a fixture problem. One
     * single-column foreign key compares ONE value against ONE parent column.
     * The fixture inserts one `telegram_accounts` row carrying
     * `user_id = 1724372757`, so a constraint that references `user_id` admits
     * `1724372757` and cannot admit `1` unless a second parent row exists whose
     * `user_id` is literally `1`. No schema admits both and still refuses the
     * fabricated tenant — which is the whole point of the retarget, and why
     * DROP was refuted in `docs/decisions.md` D-005.
     *
     * So "the app row id is still admitted" and "the six reference `user_id`"
     * are mutually exclusive claims, and only one of them can be a correct
     * assertion. This file now makes the second one, which is the one the engine
     * agrees with.
     *
     * ## NOTHING WAS WEAKENED — ONE ASSERTION WAS ADDED
     *
     * The old control never pinned the REFERENCED column: it read
     * `$fk['columns']` (the child side) and then inferred the parent column from
     * which value the constraint admitted. This one additionally pins
     * `$fk['foreign_columns'] === ['user_id']` — the assertion the old control was
     * reaching for by inference — and keeps every assertion the old one made
     * (exactly one `telegram_accounts` constraint, the `account_id` child leg,
     * `ON DELETE CASCADE`), and keeps its "the constraint fires" proof in BOTH
     * directions: the real tenant lands, and the app row id is now refused
     * because it is not a tenant.
     */
    public function test_each_declares_one_cascade_constraint_on_the_telegram_user_id(): void
    {
        $db = $this->enforcingWithParent();

        foreach (self::ENGINE_ROWS as $table => $row) {
            $declared = $db->getSchemaBuilder()->getForeignKeys($table);
            $tenant = array_values(array_filter(
                $declared,
                static fn (array $fk): bool => (string) $fk['foreign_table'] === 'telegram_accounts',
            ));

            self::assertCount(
                1,
                $tenant,
                "{$table} must declare exactly one constraint against telegram_accounts; if it now declares "
                ."none, or several, re-measure the whole family — this file's controls are written against "
                .'exactly one.',
            );
            self::assertSame(
                ['account_id'],
                array_map('strval', $tenant[0]['columns']),
                "{$table}: the tenant leg is the single account_id column.",
            );
            self::assertSame(
                ['user_id'],
                array_map('strval', $tenant[0]['foreign_columns']),
                "{$table}: the constraint must reference telegram_accounts.user_id — the Telegram user id the "
                .'engine writes (src/Laravel/MirrorIngesterFactory.php:160). A reference to `id` here means '
                .'the constraint is still the shipped one and every real ingest is refused.',
            );
            self::assertSame(
                'CASCADE',
                strtoupper((string) $tenant[0]['on_delete']),
                "{$table}: deleting a tenant takes its updates with it.",
            );

            // Direction 1 — the constraint admits the tenant it now references.
            // Without this the gate above is satisfied by a table that declares
            // nothing, which is exactly what a DROP-instead-of-retarget leaves.
            $db->table($table)->insert($row);

            self::assertSame(
                1,
                $db->table($table)->count(),
                "{$table}: the Telegram user id must be ADMITTED by a constraint that exists — that is what "
                .'proves the constraint fires, and it is why the gate above is not vacuously green on a '
                .'table whose constraint was simply dropped.',
            );

            // Direction 2 — and it refuses the app row id, which the shipped
            // constraint used to admit and which is NOT a tenant. This is the
            // assertion the old control made in the opposite direction, kept so
            // that "the constraint fires" is proven by a refusal and not only by
            // a landing insert.
            try {
                $db->table($table)->insert($this->withAccountId($row, self::VAULT_ROW_ID));
            } catch (QueryException $e) {
                self::assertStringContainsString(
                    'FOREIGN KEY constraint failed',
                    $e->getMessage(),
                    "{$table}: the refusal must come from the FOREIGN KEY, not from a malformed fixture.",
                );

                continue;
            }

            self::fail(
                "{$table}: the vault row id ({$this->vaultRowId()}) was ADMITTED. It is not a tenant — the "
                .'engine never writes it, and no telegram_accounts row carries it as a user_id. A constraint '
                .'that admits it is either still referencing `id`, or has been dropped.',
            );
        }
    }

    /**
     * The vault row id, rendered for the failure message above. Held in a
     * method rather than interpolated from the constant so the message and the
     * value cannot drift apart.
     */
    private function vaultRowId(): int
    {
        return self::VAULT_ROW_ID;
    }

    /**
     * The refusal a correct schema must still produce: a tenant that does not
     * exist. Without it, "accepts the engine row" alone would be satisfied by a
     * schema with no tenant constraint at all.
     */
    public function test_a_fabricated_tenant_is_still_refused(): void
    {
        $db = $this->enforcingWithParent();

        $refused = 0;
        $admitted = [];

        foreach (self::ENGINE_ROWS as $table => $row) {
            try {
                $db->table($table)->insert($this->withAccountId($row, self::FABRICATED_TENANT));
            } catch (QueryException $e) {
                self::assertStringContainsString(
                    'FOREIGN KEY constraint failed',
                    $e->getMessage(),
                    "{$table}: the refusal must come from the FOREIGN KEY, not from a malformed fixture — "
                    .'expectException(QueryException::class) alone cannot tell those apart.',
                );
                $refused++;

                continue;
            }

            $admitted[] = $table;
        }

        self::assertSame(
            [],
            $admitted,
            'a tenant that is not in telegram_accounts must be refused by every one of the six. While the '
            .'constraints target the app row id this passes; after a DROP instead of a retarget it fails, '
            .'which is the measured difference between the two dispositions.',
        );
        self::assertSame(6, $refused, 'all six tables were exercised.');
    }

    /**
     * The parent column the retarget needs is signed today — the shape that
     * makes the retarget inexpressible. Asserted on the DDL the repo ships, so
     * the blocker is visible in the suite and disappears on its own when the
     * parent-fix migration lands and a later lane re-points the gate.
     *
     * Read off the executed schema, not off this file's prose: the assertion
     * fails with the declaration it found, so whoever fixes it sees what
     * changed.
     */
    public function test_the_retarget_target_column_is_still_signed(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());

        self::assertContains(
            'user_id',
            $db->getSchemaBuilder()->getColumnListing('telegram_accounts'),
            'telegram_accounts.user_id is the column the six must reference. If it is gone, the retarget '
            .'target moved and this whole file is about something else.',
        );

        // sqlite records no signedness, so the shape is asserted from the
        // shipped DDL that declares it. `bigInteger()` is signed and
        // `unsignedBigInteger()` is not; a change to either shows up here.
        $ddl = (string) file_get_contents(
            dirname(__DIR__, 3).'/src/Laravel/Migrations/2026_09_09_000201_create_telegram_accounts_table.php'
        );
        self::assertStringContainsString(
            "bigInteger('user_id')",
            $ddl,
            'the create migration must still declare user_id SIGNED. An additive parent-fix migration '
            .'leaves this line alone (it MODIFYs the column later), so this assertion stays true after the '
            .'fix and therefore cannot detect it — it only detects an IN-PLACE edit of the create '
            .'migration, which D-005 forbids and which would strand existing installs.',
        );
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function withAccountId(array $row, int $accountId): array
    {
        $row['account_id'] = $accountId;

        return $row;
    }

    /**
     * The first line of a driver message — `QueryException::getMessage()` on
     * sqlite carries the whole SQL context, and a failure report wants the
     * reason, not the statement.
     */
    private static function firstLine(string $message): string
    {
        $lines = explode("\n", trim($message));

        return $lines[0];
    }
}
