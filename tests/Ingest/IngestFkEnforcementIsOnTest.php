<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Tests\Ingest\Concerns\HasEndpointFixtures;

/**
 * THE GATE ON THE GATE — FK enforcement must be ON for the ingest suite.
 *
 * ## WHY THIS FILE EXISTS AT ALL
 *
 * `Concerns\RunsMigrations::defineIngestDatabase()` configures the sqlite
 * `:memory:` connection the whole ingest suite runs on. It shipped WITHOUT the
 * `foreign_key_constraints` key. Laravel's
 * `Illuminate\Database\Connectors\SQLiteConnector::configureForeignKeyConstraints()`
 * begins:
 *
 * ```php
 * if (! isset($config['foreign_key_constraints'])) {
 *     return;                       // bare early-out, no warning
 * }
 * $connection->prepare("pragma foreign_keys = {$foreignKeys}")->execute();
 * ```
 *
 * and SQLite's own default is `PRAGMA foreign_keys = 0`. So the omission was
 * SILENT: no exception, no log, no failing assertion — the suite simply ran
 * for its whole life against a database that was not checking. Every "no FK
 * clue" reading in this suite was a reading about a database that could not
 * have produced one.
 *
 * The operator's framing is the whole reason this is a gate and not a config
 * tidy-up: *"the integrity of data in the database means everything"* and
 * *"it can be used as a sign of failure"*. A suite that cannot detect an
 * integrity breach cannot certify integrity. Setting the key is the fix;
 * ASSERTING it is what stops the next edit from silently reverting the fix,
 * which is precisely how the original defect survived.
 *
 * ## WHAT IS ASSERTED, AND WHY IT IS NOT ENOUGH TO ASK ONCE
 *
 * Three separate things, because each can fail while the others pass:
 *
 * 1. **The config key is present and true.** Catches the exact regression.
 * 2. **The live connection reports `PRAGMA foreign_keys = 1`.** Catches a
 *    driver or connector change that ignores the key, or a test that swapped
 *    the connection out from under the suite.
 * 3. **The pragma is LOAD-BEARING on this connection** — an orphan insert is
 *    actually refused. A pragma can be *reported* as 1 by a driver that does
 *    not enforce it; only an observed refusal distinguishes the two. Without
 *    this half the first two assertions are a claim about a number.
 *
 * ## NO CREDENTIALS
 *
 * Every value is synthetic: no session string, no bot token, no real
 * `access_hash`, no database password. Nothing here contacts Telegram.
 */
final class IngestFkEnforcementIsOnTest extends IngestTestCase
{
    use HasEndpointFixtures;

    public function test_the_harness_ships_the_foreign_key_constraints_key(): void
    {
        $config = config('database.connections.ingest');

        self::assertIsArray($config, 'the ingest connection config is missing — every test in this suite would run on the wrong database');
        self::assertArrayHasKey(
            'foreign_key_constraints',
            $config,
            'THE REGRESSION THIS FILE GUARDS. Laravel\'s SQLiteConnector returns early when this key is '
            .'absent, and sqlite defaults PRAGMA foreign_keys to 0, so the whole ingest suite would run '
            .'against a database that is not checking — silently, with no exception and no warning.',
        );
        self::assertTrue(
            $config['foreign_key_constraints'],
            'foreign_key_constraints is present but falsy, which issues PRAGMA foreign_keys = 0 — the same '
            .'silently-unenforced state as omitting it, and harder to spot in review.',
        );
    }

    public function test_the_live_connection_reports_enforcement_on(): void
    {
        $row = DB::selectOne('PRAGMA foreign_keys');

        self::assertNotNull(
            $row,
            'PRAGMA foreign_keys returned no row. A driver that cannot report its own enforcement cannot be '
            .'asked whether it enforces, so nothing downstream in this suite may be trusted.',
        );
        self::assertSame(
            1,
            (int) $row->foreign_keys,
            'the live ingest connection reports FK enforcement OFF. Every "no FK clue" reading in tests/Ingest '
            .'is then a reading about a database that is not checking. Check RunsMigrations::defineIngestDatabase().',
        );
    }

    /**
     * The half that makes the other two load-bearing: a reported pragma is a
     * claim, an observed refusal is a fact.
     */
    public function test_enforcement_actually_refuses_an_orphan_child_row(): void
    {
        $accountId = self::endpointFixtureAccount();

        // Seed ONLY the tenant parent. `tf_updates_pts_count` declares a
        // foreign key to `tf_updates(acct:account_id, seq, position)`, and no
        // `tf_updates` row exists — so this insert is an orphan in the one
        // shape the ingest surface can never legitimately produce.
        DB::table('telegram_accounts')->insert([
            'user_id' => $accountId,
            'label' => 'fk-enforcement-probe-'.$accountId,
            'type' => 'user',
            'dc_id' => 2,
        ]);

        DB::table('tf_updates')->insert([
            'account_id' => $accountId,
            'seq' => 1,
            'position' => 1,
            'constructor' => 'enforcement-probe',
        ]);

        $this->expectException(QueryException::class);
        $this->expectExceptionMessageMatches('/FOREIGN KEY constraint failed/');

        DB::table('tf_updates_pts_count')->insert([
            'account_id' => $accountId,
            'seq' => 1,
            'position' => 1,
            'pts_count' => 1,
        ]);
    }

    /**
     * The control half: with enforcement genuinely off, the very same insert
     * LANDS.
     *
     * Without this, the refusal test above would still pass on a connection
     * where the child insert failed for some unrelated reason (a missing
     * column, a NOT NULL violation) and this file would be a false comfort.
     * Turning the pragma off and observing the row appear is what proves the
     * refusal was the FOREIGN KEY and not a coincidence.
     */
    public function test_the_same_orphan_insert_lands_when_enforcement_is_off(): void
    {
        $accountId = self::endpointFixtureAccount();

        DB::table('telegram_accounts')->insert([
            'user_id' => $accountId,
            'label' => 'fk-enforcement-control-'.$accountId,
            'type' => 'user',
            'dc_id' => 2,
        ]);
        DB::table('tf_updates')->insert([
            'account_id' => $accountId,
            'seq' => 1,
            'position' => 1,
            'constructor' => 'enforcement-control',
        ]);

        DB::statement('PRAGMA foreign_keys = OFF');

        try {
            DB::table('tf_updates_pts_count')->insert([
                'account_id' => $accountId,
                'seq' => 1,
                'position' => 1,
                'pts_count' => 1,
            ]);

            self::assertSame(
                1,
                DB::table('tf_updates_pts_count')
                    ->where('account_id', $accountId)
                    ->where('seq', 1)
                    ->count(),
                'with PRAGMA foreign_keys = OFF the orphan row should land. If it did not, the refusal test '
                .'above is passing for a reason other than FK enforcement and this file certifies nothing.',
            );
        } finally {
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }

    /**
     * The tenant key is `user_id`, NOT `id` — and the difference is the whole
     * disease this file's existence exposes.
     *
     * `telegram_accounts` carries both: an autoincrement `id` (the vault row's
     * own primary key) and a nullable `user_id` (the TELEGRAM user id, made
     * UNSIGNED + UNIQUE by `2026_10_04_000100` precisely so it could carry the
     * tenant FK — `2026_10_04_000700`). Every `tf_*.account_id` is the Telegram
     * user id.
     *
     * A fixture that seeds only `id` therefore satisfies nothing: with
     * enforcement off that is invisible, and with enforcement on every insert
     * under it is refused. Asserted here because the mistake is invisible by
     * construction — `id` and `user_id` are both integers and the row looks
     * seeded either way.
     */
    public function test_a_fixture_seeding_only_the_vault_id_does_not_satisfy_the_tenant_key(): void
    {
        $vaultRowId = 987654;

        DB::table('telegram_accounts')->insert([
            'id' => $vaultRowId,
            'user_id' => self::endpointFixtureAccount(),
            'label' => 'tenant-key-shape',
            'type' => 'user',
            'dc_id' => 2,
        ]);

        self::assertSame(
            self::endpointFixtureAccount(),
            (int) DB::table('telegram_accounts')->where('id', $vaultRowId)->value('user_id'),
            'tf_*.account_id is the Telegram user id, so a child row keyed on account_id = '
            .self::endpointFixtureAccount().' is satisfied by user_id, not by the vault row id. Fixtures that '
            .'plant only `id` were passing only because nothing was checking.',
        );
    }
}
