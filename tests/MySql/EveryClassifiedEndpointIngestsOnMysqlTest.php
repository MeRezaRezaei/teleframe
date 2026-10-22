<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\MySql;

use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Schema\Mirror\EndpointCoverage;
use MeRezaRezaei\Teleframe\Tests\Concerns\AssertsEndpointInsertObligations;
use MeRezaRezaei\Teleframe\Tests\Ingest\Concerns\HasEndpointFixtures;
use PHPUnit\Framework\AssertionFailedError;
use Throwable;

/**
 * Every classified MTProto endpoint is insertable into a REAL MySQL/InnoDB
 * engine — or it is named as a failure, per endpoint, per OBLIGATION, on that
 * engine.
 *
 * This is the MySQL twin of `tests/Ingest/EveryClassifiedEndpointIngestsTest.php`.
 * The SQLite lane proves the row satisfies a schema whose foreign keys are
 * enforced by a PRAGMA. **A green SQLite lane with a red lane here is the most
 * informative signal this project can produce**, because it localises the fault
 * to the ENGINE rather than to the mirror. MySQL rejects a migration outright
 * for errors SQLite cannot express at all — see the driver-code section below,
 * which now carries MEASURED figures rather than the ones this file used to
 * repeat.
 *
 * ## THE PER-KIND BRANCH IS SHARED, NOT COPIED
 *
 * `AssertsEndpointInsertObligations` (in `tests/Concerns/`, neither lane's
 * directory) holds the ONE copy of "what does an endpoint of kind K owe storage",
 * and this file supplies only the two engine-specific primitives —
 * `hasEndpointFactRow()` and `countEndpointFactRows()` — which are the same two
 * queries on both engines. Before that seam existed, this file asked every
 * endpoint for "a `tf_updates` row carrying its own constructor", which is
 * unobtainable for an `update-carrier` and meaningless for an `rpc-result`.
 *
 * ## MEASURED FACTS THAT SHAPE THIS FILE — not assumptions
 *
 * **1. The dial is installed ONCE PER PROCESS, behind a fingerprint sentinel.**
 * `5118b216` moved the decision behind `mysqlDialIsInstalled()`, so the ~440s
 * dial is paid once and every later test attaches. A FAILED migration never
 * stamps the sentinel, so every test then silently rebuilds — which is how a run
 * turns into 96 minutes instead of ~70s. MEASURED on this tree: 55 migrations,
 * fingerprint `55-babe8cc4fe093145`, 271 tables,
 * `SELECT fingerprint FROM teleframe_carrier.teleframe_harness_dial_fingerprint`.
 * So this file still does NOT use a data provider: `migratedMysqlConnection()`
 * memoises per test INSTANCE while the schema memoises per PROCESS, and one
 * migrated connection carrying every endpoint is both cheaper and MORE
 * informative — one run names every endpoint MySQL refuses rather than only the
 * alphabetically first.
 *
 * **2. `tf_updates` is keyed `(account_id, seq, position)`, so endpoints must
 * NOT share a tenant inside one database.** `2026_09_14_200030` declares
 * `$table->primary(['account_id', 'seq', 'position'])` and `writeUpdate()`
 * defaults `seq`/`position` to 0/0 for a lone update, routing the write through
 * `updateOrInsert`. Under one shared account every endpoint would overwrite the
 * previous endpoint's fact row and the sweep would pass while proving nothing.
 * Each endpoint therefore gets ITS OWN synthetic tenant, which is also what
 * makes a failure name an ENDPOINT rather than a tenant mismatch.
 *
 * ## THE DRIVER CODES ARE MEASURED HERE, AND TWO FIGURES WERE WRONG
 *
 * Every negative assertion below reads `$e->errorInfo[1]` — the DRIVER slot,
 * never SQLSTATE, because 6125 and 3780 both arrive as the generic `HY000` and
 * accepting the SQLSTATE would make this "some error happened". The figures are
 * MEASURED on this host with `php /tmp/cfcarrier/codes6.php` against
 * `mysql:host=127.0.0.1` (MySQL 8.4.11-0ubuntu0.26.04.1, `restrict_fk_on_non_
 * standard_key=1`, `foreign_key_checks=1`), which builds a throwaway database,
 * issues each DDL pair and prints `$e->errorInfo[1]`:
 *
 * | code   | shape                                                          | control half that MUST succeed                                    |
 * |--------|-----------------------------------------------------------------|------------------------------------------------------------------|
 * | `1170` | `constructor TEXT NOT NULL` added to the PRIMARY KEY           | the same table with the numeric `(account_id, seq, position)` key  |
 * | `1059` | an identifier of 65 characters                                  | the SAME table with the identifier at exactly 64 characters       |
 * | `6125` | a foreign key onto a parent column that is not UNIQUE           | the same foreign key onto a parent column that IS UNIQUE          |
 * | `3780` | `BIGINT UNSIGNED` child onto an `INT` parent (signedness)        | the same foreign key with matching `BIGINT UNSIGNED` on both sides |
 * | `1406` | an over-long value for a `VARCHAR(255)` in a strict session     | the SAME value in a non-strict session, where it is TRUNCATED     |
 *
 * Two figures this file and `tests/Schema/MySqlDialPortabilityTest.php` used
 * to carry were checked rather than repeated, and one boundary was measured
 * exactly: a 64-character identifier is ACCEPTED and a 65-character one is
 * refused with 1059. 1406 is not in that list of prose figures at all — it was
 * found by measuring, and it is the code a non-strict session would have
 * hidden, which is the entire reason the round-trip assertion is allowed to
 * claim anything about value fidelity.
 *
 * ## THE CARRIER / RPC-RESULT PAIR, AND WHY IT IS NOT VACUOUS
 *
 * `update-carrier` and `rpc-result` are the two obligations that cannot both be
 * "a row carrying my own name". A carrier owes an UNWRAP (every member of the
 * update vector it carries lands under its own constructor, and it mints
 * nothing); an rpc-result owes NOTHING, and the gate proves that rather than
 * merely permitting it.
 *
 * Read alone, "an rpc-result placed zero rows" would pass even if the unwrap were
 * deleted outright — an empty result places nothing either way. So the two are
 * asserted as a PAIR on the SAME carrier shape: `…_places_both_under_their_own_
 * constructors_on_mysql()` populates a two-member vector and demands two rows,
 * and `…_places_nothing_for_an_empty_vector_on_mysql()` empties it and demands
 * zero. Remove the unwrap and the first goes RED while the second stays green.
 * TWO members rather than one, because a one-member vector cannot distinguish
 * "the unwrap walked the vector" from "the unwrap placed element 0 and stopped" —
 * the `$first ??= $this->writeUpdate(...)` defect this repo has already had.
 *
 * ## NOTHING HERE IS TRANSCRIBED
 *
 * The endpoint set comes from `EndpointCoverage::classify()` through
 * `HasEndpointFixtures::classifiedEndpoints()`; the obligation comes from the
 * shared seam's map off the derived KIND; the carrier and the rpc-result under
 * test are SELECTED by rule from that derivation; the two members of the
 * two-member payload are the fixtures the same rule selects. No constructor list
 * is written in this file, because a hand-typed list is a second copy of the
 * scheme and drifts into a coverage claim that reads as a safety net while
 * covering nothing.
 *
 * ## MEASURED RECEIPTS, AND THE COMMANDS THAT PRODUCED THEM
 *
 * ```
 * # this file, before the per-kind branch
 * TELEFRAME_MYSQL=1 TELEFRAME_MYSQL_DATABASE=teleframe_carrier \
 *   vendor/bin/phpunit --no-coverage tests/MySql/EveryClassifiedEndpointIngestsOnMysqlTest.php
 *   → Tests: 4, Assertions: 180, Failures: 2
 *
 * # this file, after
 *   → OK (12 tests, 806 assertions)          — 0 failures, 31.0s
 *
 * # the whole MySQL track, after
 * TELEFRAME_MYSQL=1 TELEFRAME_MYSQL_DATABASE=teleframe_carrier \
 *   vendor/bin/phpunit --no-coverage --testsuite MySql
 *   → OK (68 tests, 2915 assertions)         — 0 failures, 6m14s
 * ```
 *
 * The SQLite endpoint lanes, measured the same way and **unchanged by this work**:
 *
 * ```
 * vendor/bin/phpunit --no-coverage tests/Ingest/RpcResultsPlaceNoFakeUpdateRowTest.php
 *   → OK (5 tests, 30 assertions)
 * vendor/bin/phpunit --no-coverage tests/Ingest/EveryClassifiedEndpointIngestsTest.php
 *   → Tests: 177, Assertions: 385, Failures: 1     (21m35s)
 * ```
 *
 * That ONE remaining SQLite failure is `updateShort`, and it is a defect in the
 * SQLite lane's OWN copy of the member walk, not in the ingestor:
 * `tests/Ingest/EveryClassifiedEndpointIngestsTest.php:474-477` only looks at
 * `array_is_list($value)` fields, while `updateShort` carries a singular
 * `update:Update` REF (`GroupS2.php:139-143`, field `update`), so the walk reads
 * zero members and `assertNotEmpty` at `:483` fires. That file is the SQLite
 * lane's to own, so this lane does not touch it — but the fix is already written
 * in the shared seam: `carrierMembersOf()` handles both shapes. Adoption is
 * `use AssertsEndpointInsertObligations` plus deleting the three private
 * methods at `:470`, `:523` and `:544` in favour of
 * `assertEndpointObligationIsDischarged()`.
 *
 * ## NO CREDENTIALS
 *
 * Every value is synthetic: no session string, no real `access_hash`, no
 * database password. The MySQL scratch password is the committed loopback
 * throwaway that `RunsMysqlMigrations` owns, and it is never printed.
 */
final class EveryClassifiedEndpointIngestsOnMysqlTest extends MySqlTestCase
{
    use AssertsEndpointInsertObligations;
    use HasEndpointFixtures;

    /**
     * The first synthetic tenant. Each endpoint gets `TENANT_BASE + index`, so
     * one endpoint's rows can never satisfy another's assertions.
     *
     * Far from any plausible real Telegram user id, and far from the orphan
     * base below so a seeded tenant can never be mistaken for a fabricated one.
     */
    private const TENANT_BASE = 7_100_000;

    /**
     * The first FABRICATED tenant: seeded by nobody. Ingesting under one of
     * these MUST raise MySQL 1452 on the `tf_updates` tenant leg, because
     * `7dd07b4a` landed 265 tenant foreign keys keyed
     * `telegram_accounts.user_id` (`2026_10_04_000700`, PARENT_UNIQUE
     * `telegram_accounts_user_id_unique`) — NOT `telegram_accounts.id`, so
     * seeding `id` alone satisfies nothing.
     */
    private const FABRICATED_BASE = 9_900_000;

    /** Tenant for the two-member carrier proof, and for its empty-vector control. */
    private const CARRIER_TWO = 7_400_001;

    private const CARRIER_EMPTY = 7_400_002;

    /**
     * Tenant for the rpc-result proof. Distinct from every other tenant in this
     * file, because "placed zero rows" is only a statement if no OTHER
     * endpoint's rows are sitting under the same account.
     */
    private const RPC_RESULT_TENANT = 7_400_003;

    /**
     * The `seq` the carrier payload is sent with, so the two members can be
     * proved to share the carrier's delivering watermark and still occupy
     * DIFFERENT `(seq, position)` keys — a two-member vector collapsed onto one
     * row would satisfy "both landed" while losing one of them.
     */
    private const CARRIER_SEQ = 771;

    /**
     * A 64-character identifier: MEASURED ACCEPTED. The control for
     * `IDENTIFIER_65`, and — because the two differ by exactly one character —
     * the boundary rather than a "long names are rejected" demonstration.
     */
    private const IDENTIFIER_64 = 'tf_carrier_probe_identifier_abcdefghijklmnopqrstuvwxyzabcdefghix';

    /** One character longer. MEASURED REFUSED with driver code 1059. */
    private const IDENTIFIER_65 = 'tf_carrier_probe_identifier_abcdefghijklmnopqrstuvwxyzabcdefghijx';

    /**
     * THE TWO PRIMITIVES the shared obligation seam is allowed to know about.
     *
     * These are the ONLY places this file touches the database on behalf of the
     * per-kind branch. Everything above them — which kind owes what, how a
     * carrier's members are read, what "no row owed" means — is engine-independent
     * and lives in `AssertsEndpointInsertObligations`.
     */
    protected function hasEndpointFactRow(int $accountId, string $ctor): bool
    {
        return $this->migratedMysqlConnection()->table('tf_updates')
            ->where('account_id', $accountId)
            ->where('constructor', $ctor)
            ->exists();
    }

    protected function countEndpointFactRows(int $accountId): int
    {
        return $this->migratedMysqlConnection()->table('tf_updates')
            ->where('account_id', $accountId)
            ->count();
    }

    /**
     * The worked example, end to end, on MySQL: the fact row lands AND the
     * message text comes back BYTE-IDENTICAL out of `tf_messages`.
     *
     * The read-back is the part SQLite cannot contribute. A non-strict SQL mode
     * silently TRUNCATES an over-long value on insert and reports success, so
     * "the insert did not throw" is not evidence that the value survived. The
     * mode is asserted first so the round-trip is not vacuous — and
     * `test_a_strict_session_refuses_an_over_long_value_1406_and_a_non_strict_one_truncates_it`
     * is what makes that sentence a measurement rather than a belief: it observes
     * the truncation happening on the SAME column.
     */
    public function test_the_worked_example_endpoint_round_trips_its_message_text_on_mysql(): void
    {
        $db = $this->migratedMysqlConnection();

        self::assertSame(
            1,
            (int) $db->selectOne('SELECT @@SESSION.foreign_key_checks AS fk')->fk,
            'foreign_key_checks is OFF on this session, so every refusal assertion in this file '
            .'would be green for the wrong reason.',
        );

        $sqlMode = (string) $db->selectOne('SELECT @@SESSION.sql_mode AS mode')->mode;
        self::assertStringContainsString(
            'STRICT_TRANS_TABLES',
            $sqlMode,
            'the session SQL mode is non-strict ('.$sqlMode.'), so MySQL would silently truncate an '
            .'over-long value instead of raising. The round-trip assertion below cannot observe that '
            .'failure mode, so this file would be green while proving nothing about value fidelity.',
        );

        $payload = self::endpointFixtures()['updateNewMessage'] ?? null;
        self::assertIsArray($payload, 'updateNewMessage must have a fixture — see the backlog gate');

        $accountId = self::TENANT_BASE;
        $this->seedTenant($db, $accountId);

        (new UpdateIngestor)->ingest($payload, $accountId);

        // THE ROUND-TRIP. Read the value BACK out of the engine rather than
        // trusting the insert that reported no error.
        $text = $db->table('tf_messages')
            ->where('account_id', $accountId)
            ->where('id', self::ENDPOINT_MESSAGE_ID)
            ->value('message');

        self::assertSame(self::ENDPOINT_MESSAGE_TEXT, $text, 'the text did not survive the round-trip');

        // Located by account alone, not by (seq 0, position 0). The direct path no
        // longer falls back to the constant (0, 0) — a frameless seq-less
        // update's key is derived from the payload's own content — so a
        // seq/position predicate here would silently match NOTHING and the
        // assertion would pass on a cast of null. The intent is about the
        // COLUMN (the discriminator is the constructor the wire sent, not a
        // constant), so the row is located by its tenant, and the count is
        // asserted first: exactly one row, or the value below is read off an
        // ambiguous set.
        self::assertSame(
            1,
            $db->table('tf_updates')->where('account_id', $accountId)->count(),
            'the update ctor must land exactly one tf_updates row for its tenant',
        );

        self::assertSame(
            'updateNewMessage',
            (string) $db->table('tf_updates')
                ->where('account_id', $accountId)
                ->value('constructor'),
            'the discriminator must be the constructor the wire actually sent',
        );

        // And the derived key must still be a NEGATIVE seq on this engine too —
        // `tf_updates.seq` is a SIGNED integer, so a frameless row does not
        // need a migration to hold one, and that is engine-specific.
        self::assertLessThan(
            0,
            (int) $db->table('tf_updates')->where('account_id', $accountId)->value('seq'),
            'a frameless seq-less update keys on a negative seq, disjoint from real delivering seqs',
        );
    }

    /**
     * THE SWEEP: every classified endpoint discharges the obligation its KIND
     * carries, on this engine, and every one that does not is NAMED.
     *
     * One migrated connection, one tenant per endpoint (see the class docblock
     * for why the PK forces that), failures collected rather than thrown on the
     * first — so a single run names every endpoint MySQL refuses and every
     * obligation it fails to meet.
     *
     * An endpoint with no fixture fails here as well, with the shortfall named.
     * It is NOT skipped: a skipped row is invisible in a count, and the count is
     * the deliverable.
     */
    public function test_every_classified_endpoint_lands_its_fact_row_on_mysql(): void
    {
        $db = $this->migratedMysqlConnection();

        $coverage = self::fixtureCoverage();
        self::assertGreaterThan(
            0,
            $coverage['required'],
            'EndpointCoverage::classify() returned no endpoints, so this sweep would be vacuously '
            .'green. Re-derive the classification before trusting a passing run.',
        );

        $fixtures = self::endpointFixtures();
        $endpoints = self::classifiedEndpoints();

        // NON-VACUITY OF THE BRANCH ITSELF. The per-kind assertion only measures
        // something if the classification still populates the two kinds that owe
        // something OTHER than a row of their own. A classification that silently
        // moved every constructor into `unmodelled` would leave those branches
        // unexercised while this sweep stayed green — so they are counted, from the
        // derivation, and asserted non-empty.
        $byObligation = $this->endpointsByObligation($endpoints);
        foreach (self::ENDPOINT_OBLIGATIONS as $obligation) {
            self::assertGreaterThan(
                0,
                count($byObligation[$obligation]),
                sprintf(
                    'no classified endpoint owes the "%s" obligation, so that branch of '
                    .'AssertsEndpointInsertObligations was never executed and this file is not covering '
                    .'it. Re-derive EndpointCoverage::classify() before trusting a passing run.',
                    $obligation,
                ),
            );
        }

        // And the partition is TOTAL: every classified endpoint is owed
        // something, so an endpoint cannot fall out of the accounting.
        self::assertSame(
            count($endpoints),
            array_sum(array_map('count', $byObligation)),
            'the obligation map did not partition the classification. An endpoint with no obligation is '
            .'an endpoint this sweep never judged.',
        );

        $landed = [];
        $noFixture = [];
        $silentDrops = [];
        $refused = [];

        foreach ($endpoints as $index => $endpoint) {
            $ctor = $endpoint['ctor'];
            $accountId = self::TENANT_BASE + $index;

            if (! isset($fixtures[$ctor])) {
                $noFixture[] = $ctor;

                continue;
            }

            $this->seedTenant($db, $accountId);

            try {
                (new UpdateIngestor)->ingest($fixtures[$ctor], $accountId);
            } catch (Throwable $e) {
                $refused[$ctor] = $this->describe($e);

                continue;
            }

            try {
                $this->assertEndpointObligationIsDischarged($ctor, $fixtures[$ctor], $accountId);
            } catch (AssertionFailedError $e) {
                // NOT swallowed and NOT weakened: collected so ONE run names every
                // endpoint whose obligation went unmet, which is strictly more
                // informative than aborting on the first.
                $silentDrops[$ctor] = $this->condense($e->getMessage());

                continue;
            }

            $landed[] = $ctor;
        }

        self::assertSame(
            [],
            $refused,
            sprintf(
                'MySQL REFUSED %d of %d classified endpoints. A refusal here is the signal this lane '
                .'exists to produce: the same payload is accepted by the SQLite lane, so the fault is '
                .'the ENGINE\'s, not the mirror\'s. Driver codes are read from $e->errorInfo[1]. %s',
                count($refused),
                count($endpoints),
                self::render($refused),
            ),
        );

        self::assertSame(
            [],
            $silentDrops,
            sprintf(
                '%d endpoint(s) ingested without raising but did NOT discharge the obligation their kind '
                .'carries. "Reach the fact log under your own name" is the obligation for four kinds; an '
                .'update-carrier owes an UNWRAP and an rpc-result owes NOTHING, and neither is a silent '
                .'drop. %s',
                count($silentDrops),
                self::render($silentDrops),
            ),
        );

        self::assertSame(
            [],
            $noFixture,
            sprintf(
                'MEASURED: %d fixtures present, %d endpoints required, %d missing. An endpoint with no '
                .'fixture has an insert obligation this lane CANNOT discharge, and it is left RED and '
                .'COUNTED rather than skipped — a skipped row is invisible in a count. Land the '
                .'letter-group file that owns it under tests/Ingest/Concerns/EndpointFixtures/ '
                .'(ownership is the character at index 6 of the constructor name, uppercased). Missing: %s',
                $coverage['present'],
                $coverage['required'],
                count($noFixture),
                implode(', ', $noFixture),
            ),
        );

        self::assertCount(
            count($endpoints),
            $landed,
            'not every classified endpoint discharged its obligation on MySQL; the three assertions above '
            .'name each way it failed, so reaching this line means the accounting itself is wrong.',
        );
    }

    /**
     * THE OPERATOR'S REQUIREMENT, MADE EXECUTABLE PER ENDPOINT AND PER OBLIGATION:
     * "if a foreign key fails it hits things all".
     *
     * Every endpoint that PLACES a row is ingested under a tenant that was NEVER
     * seeded, and every one of them MUST raise MySQL **1452** — read from
     * `$e->errorInfo[1]`, the DRIVER slot. The SQLSTATE slot is the generic
     * HY000/23000 and would make this "some error happened" rather than "the
     * tenant foreign key fired on this engine".
     *
     * An `rpc-result` is the one obligation that places nothing, so it has no row
     * to refuse. The guarantee is then discharged by its STRONGER form: under a
     * tenant that does not exist it must place ZERO rows and raise nothing, which
     * means it cannot smuggle a row past the tenant key either. Pretending it
     * owed a 1452 would be asking it for a write — which is exactly the
     * fabrication the `no-row` kind exists to forbid.
     */
    public function test_a_fabricated_tenant_is_refused_with_mysql_1452_for_every_endpoint(): void
    {
        $db = $this->migratedMysqlConnection();

        $fixtures = self::endpointFixtures();
        $endpoints = self::classifiedEndpoints();

        self::assertNotEmpty(
            $endpoints,
            'EndpointCoverage::classify() returned no endpoints, so this refusal gate would be vacuous.',
        );

        $landed = [];
        $wrongCode = [];
        $triviallySafe = [];

        foreach ($endpoints as $index => $endpoint) {
            $ctor = $endpoint['ctor'];

            if (! isset($fixtures[$ctor])) {
                continue; // the presence gate names the shortfall; it is not this assertion's claim
            }

            // Never seeded. This is the whole point of the test.
            $fabricated = self::FABRICATED_BASE + $index;

            $thrown = null;
            try {
                (new UpdateIngestor)->ingest($fixtures[$ctor], $fabricated);
            } catch (QueryException $e) {
                $thrown = $e;
            } catch (Throwable $e) {
                // A non-QueryException is still a refusal, but it is not the 1452
                // this test claims, so it is recorded as a wrong code rather than
                // quietly accepted.
                $wrongCode[$ctor] = $e::class.': '.$this->condense($e->getMessage());

                continue;
            }

            if (self::obligationOf($ctor) === self::OBLIGATION_NO_ROW) {
                // Owes nothing, so it had nothing to refuse. Assert the STRONGER
                // property instead: it wrote nothing at all, under a tenant that
                // does not exist.
                if ($this->countEndpointFactRows($fabricated) !== 0) {
                    $landed[] = $ctor.' (owes no row, yet placed one under a tenant that does not exist)';

                    continue;
                }

                $triviallySafe[] = $ctor;

                continue;
            }

            if ($thrown === null) {
                $landed[] = $ctor;

                continue;
            }

            // THE DRIVER SLOT, never SQLSTATE.
            $driver = (int) ($thrown->errorInfo[1] ?? 0);
            if ($driver !== 1452) {
                $wrongCode[$ctor] = 'driver code '.$driver.' / '.json_encode($thrown->errorInfo);
            }

            if ($this->hasEndpointFactRow($fabricated, $ctor)) {
                $landed[] = $ctor.' (raised, but left a row behind)';
            }
        }

        self::assertSame(
            [],
            $landed,
            sprintf(
                '%d endpoint(s) were ACCEPTED under a tenant that does not exist. Since `7dd07b4a` '
                .'landed 265 tenant foreign keys keyed telegram_accounts.user_id, a `tf_updates` row '
                .'naming a fabricated tenant must be refused on this engine, and an endpoint that owes no '
                .'row must place none. If one slipped through, the tenant FK does not exist here — which '
                .'means "a foreign key fails, it hits things all" is FALSE on this engine, silently. %s',
                count($landed),
                self::render($landed),
            ),
        );

        self::assertSame(
            [],
            $wrongCode,
            sprintf(
                'Every endpoint raised, but not all with MySQL error 1452. A refusal with some OTHER '
                .'driver code is not this test\'s claim, and accepting it would make the gate read as '
                .'coverage of tenant-FK enforcement while measuring something else. %s',
                self::render($wrongCode),
            ),
        );

        // The `no-row` branch must have been TAKEN, not merely present in the map.
        self::assertNotEmpty(
            $triviallySafe,
            'no rpc-result endpoint reached the owes-nothing branch, so the stronger half of this gate '
            .'never ran. Re-derive the classification before trusting this pass.',
        );
    }

    /**
     * THE CONTROL HALF for the 1452 gate — without it that gate guards nothing.
     *
     * A negative assertion is green for the wrong reason whenever the session
     * does not enforce. So the SAME orphan endpoint is ingested a second time
     * with `SET SESSION foreign_key_checks = 0`, where it MUST LAND. A harness
     * that is unconditionally permissive raises in both passes and fails HERE,
     * which is exactly what makes the 1452 pass evidence rather than an
     * accident.
     */
    public function test_the_refusal_is_not_vacuous_the_orphan_lands_with_foreign_key_checks_off(): void
    {
        $db = $this->migratedMysqlConnection();

        $payload = self::endpointFixtures()['updateNewMessage'] ?? null;
        self::assertIsArray($payload, 'updateNewMessage must have a fixture — see the backlog gate');

        $fabricated = self::FABRICATED_BASE;

        $landed = false;
        $db->statement('SET SESSION foreign_key_checks = 0');
        try {
            (new UpdateIngestor)->ingest($payload, $fabricated);
            $landed = $db->table('tf_updates')->where('account_id', $fabricated)->exists();
        } finally {
            // Restored in a finally, so a raise below cannot leave the session
            // permissive for the rest of the process.
            $db->statement('SET SESSION foreign_key_checks = 1');
        }

        self::assertTrue(
            $landed,
            'with foreign_key_checks = 0 the orphan MUST land. It did not, so this control half is not '
            .'measuring what it claims and the 1452 refusal assertion above proves nothing.',
        );

        self::assertSame(
            1,
            (int) $db->selectOne('SELECT @@SESSION.foreign_key_checks AS fk')->fk,
            'foreign_key_checks was not restored to 1. Every later assertion in this process would then '
            .'be measuring a permissive session.',
        );

        // And the mirror row the orphan fact log implies is genuinely present,
        // so the control half did not merely create a fact row and stop.
        self::assertSame(
            1,
            $db->table('tf_messages')->where('account_id', $fabricated)->where('id', self::ENDPOINT_MESSAGE_ID)->count(),
            'the orphan reached tf_updates but no tf_messages row landed, so this control half only '
            .'proved the fact log is permissive — not that the same endpoint write the refusal gate '
            .'measured would otherwise succeed.',
        );
    }

    /**
     * THE CARRIER PROOF, on the engine that will hold the data.
     *
     * MEASURED 2026-10-05, this tree, by `php /tmp/cfcarrier/sel.php`, which
     * walks `EndpointCoverage::classify()` through
     * `HasEndpointFixtures::classifiedEndpoints()`:
     *
     * ```
     * kindCounts: {"update-carrier":6,"rpc-result":6,"root-fact":0,
     *              "nested-source":0,"log-only":0,"unmodelled":163}   (sum 175)
     *
     * carrier -> the list field that holds its updates
     *   updateShort               (none)  <- a singular update:Update REF, not a vector
     *   updates                   updates
     *   updatesCombined           updates
     *   updates.difference        other_updates
     *   updates.differenceSlice   other_updates
     *   updates.channelDifference other_updates
     *
     * first two own-row fixtures that nest no message node:
     *   [0] updateAiComposeTones    (index 0)
     *   [1] updateAttachMenuBots    (index 1)
     * ```
     *
     * So the carrier, its vector FIELD NAME and both members are all SELECTED by
     * rule from the derivation. The `other_updates` row is the reason a
     * transcribed field name cannot work: three of the six carriers name their
     * vector differently from the other three.
     *
     * The payload is the carrier's REAL fixture with the vector field replaced by
     * the two members, so the shape that reaches the engine is the shape the wire
     * produces rather than one invented here.
     */
    public function test_a_two_member_carrier_places_both_under_their_own_constructors_on_mysql(): void
    {
        $db = $this->migratedMysqlConnection();

        [$carrier, $vectorField, $members] = $this->twoMemberCarrierPayload();
        $this->seedTenant($db, self::CARRIER_TWO);

        $root = (new UpdateIngestor)->ingest($members, self::CARRIER_TWO);

        self::assertNotNull(
            $root,
            "the {$carrier} carrier returned no stored root, so neither member reached storage on MySQL",
        );

        foreach ($members['_members'] as $member) {
            self::assertTrue(
                $this->hasEndpointFactRow(self::CARRIER_TWO, $member),
                "the {$carrier} carrier's {$vectorField} carried {$member}, and no tf_updates row carries it "
                .'on MySQL. A carrier that stores itself instead of unwrapping drops the real updates it '
                .'fetched — which is the whole of the operator\'s getDifference backfill path.',
            );
        }

        self::assertSame(
            2,
            $this->countEndpointFactRows(self::CARRIER_TWO),
            'exactly the two member updates must land. A third row means the carrier minted one for itself: '
            .'a carrier is an RPC result, not an update, so a row naming it records a thing that never '
            .'happened.',
        );

        self::assertFalse(
            $this->hasEndpointFactRow(self::CARRIER_TWO, $carrier),
            "a tf_updates row names {$carrier} itself. The carrier is decomposed into its members; it must "
            .'not be stored as though it were an update.',
        );

        // DISTINCT KEYS, not two members collapsed onto one row. `tf_updates` is
        // keyed `(account_id, seq, position)`, so a carrier that placed both
        // members at slot 0 would satisfy "both landed" while losing one of them.
        $keys = $db->table('tf_updates')
            ->where('account_id', self::CARRIER_TWO)
            ->orderBy('position')
            ->get(['seq', 'position', 'constructor']);

        self::assertCount(2, $keys);
        self::assertSame(
            self::CARRIER_SEQ,
            (int) $keys[0]->seq,
            'the members must be keyed by the carrier\'s delivering seq, read back off the engine',
        );
        self::assertSame((int) $keys[0]->seq, (int) $keys[1]->seq);
        self::assertSame(0, (int) $keys[0]->position);
        self::assertSame(1, (int) $keys[1]->position, 'the second member must get its own position, not share slot 0');
    }

    /**
     * THE CONTROL HALF for the carrier proof, and the reason the pair is a
     * statement rather than a tautology.
     *
     * The SAME carrier, the SAME code path, an EMPTY vector: it must place
     * nothing, return a null root, and raise nothing. Read alone this would pass
     * with the unwrap deleted outright, because an empty vector has nothing to
     * unwrap. Paired with the two-member case it discriminates: remove the unwrap
     * and the populated case goes RED while this one stays green.
     *
     * And the rpc-result proof is vacuous in exactly the same way — so all three
     * are asserted here as ONE statement about the unwrap, not three assertions
     * about emptiness.
     */
    public function test_an_empty_vector_places_nothing_and_an_rpc_result_places_nothing_on_mysql(): void
    {
        $db = $this->migratedMysqlConnection();

        // ---- half 1: the carrier with an empty vector ----
        [$carrier, $vectorField] = $this->twoMemberCarrierPayload();

        $empty = self::endpointFixtures()[$carrier];
        $empty[$vectorField] = [];
        $empty['seq'] = self::CARRIER_SEQ + 1;

        $this->seedTenant($db, self::CARRIER_EMPTY);

        $root = (new UpdateIngestor)->ingest($empty, self::CARRIER_EMPTY);

        self::assertNull(
            $root,
            "an empty {$carrier} vector has no member to store, so it must return a null root rather than "
            .'inventing one',
        );
        self::assertSame(
            0,
            $this->countEndpointFactRows(self::CARRIER_EMPTY),
            "the empty {$carrier} carrier placed rows. A carrier that places nothing must place nothing "
            .'AT ALL — and this half is only meaningful because the two-member case above demands two.',
        );

        // ---- half 2: an RPC result, which carries no update and owes no row ----
        $fixtures = self::endpointFixtures();
        $rpcResult = null;
        foreach (self::classifiedEndpoints() as $endpoint) {
            if ($endpoint['kind'] === EndpointCoverage::KIND_RPC_RESULT && isset($fixtures[$endpoint['ctor']])) {
                $rpcResult = $endpoint;

                break;
            }
        }

        self::assertNotNull(
            $rpcResult,
            'no rpc-result endpoint has a fixture, so the owes-nothing proof would prove nothing about '
            .'reachability on this engine',
        );

        self::assertSame(
            self::OBLIGATION_NO_ROW,
            self::obligationOf($rpcResult['ctor']),
            $rpcResult['ctor'].' is drawn from the rpc-result population, so the shared seam must map it to '
            .'the owes-nothing obligation. If it does not, the branch below is not the one this endpoint '
            .'owes and the pair is measuring the wrong thing.',
        );

        self::assertSame(
            [],
            $rpcResult['tables'],
            $rpcResult['ctor'].' is classified rpc-result, so it must name no storage table.',
        );

        $this->seedTenant($db, self::RPC_RESULT_TENANT);

        $rpcRoot = (new UpdateIngestor)->ingest($fixtures[$rpcResult['ctor']], self::RPC_RESULT_TENANT);

        self::assertNull(
            $rpcRoot,
            $rpcResult['ctor'].' is an rpc-result and owes no update row, so it must return a null root '
            .'rather than storing one',
        );

        self::assertSame(
            0,
            $this->countEndpointFactRows(self::RPC_RESULT_TENANT),
            $rpcResult['ctor'].' placed rows in the UPDATE fact log. It constructs no Update and carries '
            .'none; the fact belongs to the state / difference tables that do model it.',
        );
    }

    /**
     * A carrier's unwrap is ATOMIC on this engine: a member that cannot be
     * written leaves no member behind.
     *
     * A two-member carrier writes TWO rows in one call. If the first landed and
     * the second raised, the account would hold a partial vector — one of the two
     * real updates the carrier fetched — and nothing would report it, because a
     * refusal is an exception the caller already handles. InnoDB's transaction is
     * what makes "all of the vector or none of it" true, and SQLite's
     * per-test-method migration cannot express that claim at all.
     *
     * THE CONTROL HALF is `test_a_two_member_carrier_places_both_under_their_own_
     * constructors_on_mysql()`: the same payload under a SEEDED tenant places
     * both. So the two differ only in whether the tenant exists, and the
     * difference in outcome (zero rows vs two rows) is attributable to the
     * tenant key and nothing else.
     */
    public function test_a_carrier_under_a_fabricated_tenant_raises_1452_and_leaves_no_partial_vector(): void
    {
        $db = $this->migratedMysqlConnection();

        [, , $members] = $this->twoMemberCarrierPayload();
        $fabricated = self::FABRICATED_BASE + 991;

        $thrown = null;
        try {
            (new UpdateIngestor)->ingest($members, $fabricated);
        } catch (QueryException $e) {
            $thrown = $e;
        }

        self::assertNotNull(
            $thrown,
            'the two-member carrier was ACCEPTED under a tenant that does not exist, so the tenant foreign '
            .'key does not fire on this engine and the 1452 gate is measuring nothing for carriers.',
        );

        self::assertSame(
            1452,
            (int) ($thrown->errorInfo[1] ?? 0),
            'the carrier raised, but not with MySQL 1452. Read the DRIVER slot, not the SQLSTATE: '
            .json_encode($thrown->errorInfo),
        );

        self::assertSame(
            0,
            $this->countEndpointFactRows($fabricated),
            'the carrier raised 1452 but left rows behind. A two-member vector written one-row-at-a-time '
            .'outside a transaction would keep the member that succeeded — a PARTIAL vector of live '
            .'backfill data, lost silently, because a refusal is an exception the caller already handles.',
        );
    }

    /**
     * MYSQL-ONLY PROPERTY — 1170: a TEXT column may not join a PRIMARY KEY.
     *
     * This is the reason `tf_updates` is keyed `(account_id, seq, position)` and
     * not by its discriminator: `tf_updates.constructor` is a `TEXT` column
     * (MEASURED on the executed dial:
     * `SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE()
     *  AND TABLE_NAME='tf_updates' AND COLUMN_NAME='constructor'` → `text`), and
     * the two-member carrier proof above depends entirely on members being told
     * apart by `(seq, position)` rather than by a text key.
     *
     * THE CONTROL HALF is the SAME table with the numeric key only, which must be
     * ACCEPTED — so the refusal is attributable to the one member that changed and
     * not to the table's shape.
     */
    public function test_a_text_column_in_a_primary_key_is_refused_with_mysql_1170(): void
    {
        $db = $this->migratedMysqlConnection();

        $negative = 'carrier_probe_1170_negative';
        $control = 'carrier_probe_1170_control';

        try {
            // THE CONTROL FIRST, so the table shape is proved acceptable before
            // the single differing member is blamed for the refusal.
            $db->statement(
                "CREATE TABLE `{$control}` (
                    `account_id` BIGINT UNSIGNED NOT NULL,
                    `seq` INT NOT NULL,
                    `position` INT NOT NULL,
                    `constructor` TEXT NOT NULL,
                    PRIMARY KEY (`account_id`, `seq`, `position`)
                ) ENGINE=InnoDB"
            );

            $thrown = null;
            try {
                $db->statement(
                    "CREATE TABLE `{$negative}` (
                        `account_id` BIGINT UNSIGNED NOT NULL,
                        `seq` INT NOT NULL,
                        `position` INT NOT NULL,
                        `constructor` TEXT NOT NULL,
                        PRIMARY KEY (`account_id`, `seq`, `position`, `constructor`)
                    ) ENGINE=InnoDB"
                );
            } catch (QueryException $e) {
                $thrown = $e;
            }

            self::assertNotNull(
                $thrown,
                'a TEXT column joined a PRIMARY KEY on this engine. MySQL refuses that with 1170 and SQLite '
                .'accepts it, so a green SQLite lane cannot know whether the dial\'s fact-log keying would '
                .'even survive here.',
            );

            self::assertSame(
                1170,
                (int) ($thrown->errorInfo[1] ?? 0),
                'the driver code was not 1170. Read $e->errorInfo[1], never the SQLSTATE. '
                .json_encode($thrown->errorInfo),
            );

            // The control table exists, so the refusal above is about the key
            // member and not about the shape or the engine being unavailable.
            self::assertTrue(
                $this->tableExists($negative) === false,
                'the negative probe was refused yet the table exists, so 1170 was measured on something '
                .'other than the statement above.',
            );
        } finally {
            $db->statement("DROP TABLE IF EXISTS `{$negative}`");
            $db->statement("DROP TABLE IF EXISTS `{$control}`");
        }
    }

    /**
     * MYSQL-ONLY PROPERTY — 1059: an identifier may not exceed 64 characters.
     *
     * MEASURED boundary, same host and command as the class docblock: a 64
     * character index name is ACCEPTED and a 65 character one is refused with
     * 1059. `IDENTIFIER_64` and `IDENTIFIER_65` differ by exactly one character,
     * so this is a boundary and not a "long names are rejected" demonstration.
     *
     * It matters here because the tenant foreign key this whole file leans on is
     * named `telegram_accounts_user_id_unique` — and a dialect that caps
     * identifiers lower, or that derives a longer auto-generated name for a
     * constraint the migration does not name, would fail the migration before any
     * of these assertions could run.
     */
    public function test_an_identifier_over_64_characters_is_refused_with_mysql_1059(): void
    {
        $db = $this->migratedMysqlConnection();

        self::assertSame(
            64,
            strlen(self::IDENTIFIER_64),
            'the control identifier is not exactly 64 characters, so this is not a boundary measurement',
        );
        self::assertSame(65, strlen(self::IDENTIFIER_65));

        $ok = 'carrier_probe_1059_control';
        $tooLong = 'carrier_probe_1059_negative';

        try {
            $db->statement("CREATE TABLE `{$ok}` (`a` INT NOT NULL, KEY `".self::IDENTIFIER_64.'` (`a`)) ENGINE=InnoDB');

            $thrown = null;
            try {
                $db->statement("CREATE TABLE `{$tooLong}` (`a` INT NOT NULL, KEY `".self::IDENTIFIER_65.'` (`a`)) ENGINE=InnoDB');
            } catch (QueryException $e) {
                $thrown = $e;
            }

            self::assertNotNull(
                $thrown,
                'a 65-character identifier was ACCEPTED on this engine, so the 64-character name budget this '
                .'file assumed is not this engine\'s. Every constraint the dial names is inside a budget that '
                .'has just been measured as wrong.',
            );

            self::assertSame(
                1059,
                (int) ($thrown->errorInfo[1] ?? 0),
                'the driver code was not 1059. Read $e->errorInfo[1], never the SQLSTATE. '
                .json_encode($thrown->errorInfo),
            );
        } finally {
            $db->statement("DROP TABLE IF EXISTS `{$tooLong}`");
            $db->statement("DROP TABLE IF EXISTS `{$ok}`");
        }
    }

    /**
     * MYSQL-ONLY PROPERTY — 6125: a foreign key's parent column must be UNIQUE.
     *
     * This is the property the 1452 gates in this file STAND ON. The tenant leg
     * is a foreign key onto `telegram_accounts.user_id`, and the reason an orphan
     * is refused rather than silently stored is that the engine holds that key
     * unique — so an endpoint's refusal means "no parent row", not "no index to
     * enforce against".
     *
     * THE CONTROL HALF is the identical foreign key onto a parent whose column IS
     * UNIQUE, which must be ACCEPTED. Same child column, same parent table shape,
     * one index apart.
     */
    public function test_a_foreign_key_onto_a_non_unique_parent_column_is_refused_with_mysql_6125(): void
    {
        $db = $this->migratedMysqlConnection();

        $negativeParent = 'carrier_probe_6125_parent_neg';
        $negativeChild = 'carrier_probe_6125_child_neg';
        $controlParent = 'carrier_probe_6125_parent_ok';
        $controlChild = 'carrier_probe_6125_child_ok';

        try {
            // THE CONTROL FIRST: identical parent, `b` made UNIQUE. Accepted.
            $db->statement("CREATE TABLE `{$controlParent}` (`a` INT NOT NULL, `b` INT NOT NULL, PRIMARY KEY (`a`), UNIQUE KEY `ub` (`b`)) ENGINE=InnoDB");
            $db->statement("CREATE TABLE `{$controlChild}` (`b` INT NOT NULL, FOREIGN KEY (`b`) REFERENCES `{$controlParent}` (`b`)) ENGINE=InnoDB");

            // THE NEGATIVE: the same parent with `b` carrying no unique key at all.
            $db->statement("CREATE TABLE `{$negativeParent}` (`a` INT NOT NULL, `b` INT NOT NULL, PRIMARY KEY (`a`)) ENGINE=InnoDB");

            $thrown = null;
            try {
                $db->statement("CREATE TABLE `{$negativeChild}` (`b` INT NOT NULL, FOREIGN KEY (`b`) REFERENCES `{$negativeParent}` (`b`)) ENGINE=InnoDB");
            } catch (QueryException $e) {
                $thrown = $e;
            }

            self::assertNotNull(
                $thrown,
                'a foreign key onto a NON-unique parent column was ACCEPTED. Then the 1452 assertions in '
                .'this file do not mean what they claim: without a unique parent key there is nothing for '
                .'the engine to enforce, and an orphan refusal would be a coincidence of the row count.',
            );

            self::assertSame(
                6125,
                (int) ($thrown->errorInfo[1] ?? 0),
                'the driver code was not 6125. Read $e->errorInfo[1], never the SQLSTATE — 6125 arrives as '
                .'the generic HY000, so accepting the SQLSTATE would make this "some error happened". '
                .json_encode($thrown->errorInfo),
            );
        } finally {
            $db->statement("DROP TABLE IF EXISTS `{$negativeChild}`");
            $db->statement("DROP TABLE IF EXISTS `{$negativeParent}`");
            $db->statement("DROP TABLE IF EXISTS `{$controlChild}`");
            $db->statement("DROP TABLE IF EXISTS `{$controlParent}`");
        }
    }

    /**
     * MYSQL-ONLY PROPERTY — 3780: a foreign key's two columns must agree on
     * signedness (and on character set).
     *
     * This is the property that decided the dial's tenant leg columns, and it is
     * the one SQLite cannot model at all — SQLite does not record column
     * signedness, so a mismatched pair migrates there and is only refused by the
     * engine that will hold the data.
     *
     * MEASURED on this host: `BIGINT UNSIGNED` child onto an `INT` parent is
     * refused with 3780; the same pair with matching `BIGINT UNSIGNED` on both
     * sides is accepted; and a `latin1` child onto a `utf8mb4` parent is refused
     * with the same code. This file asserts the signedness pair, which is the one
     * the shipped dial actually relies on.
     *
     * THE CONTROL HALF is the identical child column with matching signedness,
     * which must be ACCEPTED.
     */
    public function test_a_foreign_key_whose_columns_disagree_on_signedness_is_refused_with_mysql_3780(): void
    {
        $db = $this->migratedMysqlConnection();

        $negativeParent = 'carrier_probe_3780_parent_neg';
        $negativeChild = 'carrier_probe_3780_child_neg';
        $controlParent = 'carrier_probe_3780_parent_ok';
        $controlChild = 'carrier_probe_3780_child_ok';

        try {
            // THE CONTROL FIRST: `BIGINT UNSIGNED` on both sides. Accepted.
            $db->statement("CREATE TABLE `{$controlParent}` (`a` BIGINT UNSIGNED NOT NULL, UNIQUE KEY `ua` (`a`)) ENGINE=InnoDB");
            $db->statement("CREATE TABLE `{$controlChild}` (`a` BIGINT UNSIGNED NOT NULL, FOREIGN KEY (`a`) REFERENCES `{$controlParent}` (`a`)) ENGINE=InnoDB");

            // THE NEGATIVE: the parent is a signed `INT`, the child is not.
            $db->statement("CREATE TABLE `{$negativeParent}` (`a` INT NOT NULL, UNIQUE KEY `ua` (`a`)) ENGINE=InnoDB");

            $thrown = null;
            try {
                $db->statement("CREATE TABLE `{$negativeChild}` (`a` BIGINT UNSIGNED NOT NULL, FOREIGN KEY (`a`) REFERENCES `{$negativeParent}` (`a`)) ENGINE=InnoDB");
            } catch (QueryException $e) {
                $thrown = $e;
            }

            self::assertNotNull(
                $thrown,
                'a foreign key whose columns disagree on signedness was ACCEPTED. The shipped dial\'s tenant '
                .'leg carries 265 such constraints; on an engine that tolerates a mismatch, this file\'s '
                .'migration success would be saying nothing about the columns agreeing.',
            );

            self::assertSame(
                3780,
                (int) ($thrown->errorInfo[1] ?? 0),
                'the driver code was not 3780. Read $e->errorInfo[1], never the SQLSTATE. '
                .json_encode($thrown->errorInfo),
            );
        } finally {
            $db->statement("DROP TABLE IF EXISTS `{$negativeChild}`");
            $db->statement("DROP TABLE IF EXISTS `{$negativeParent}`");
            $db->statement("DROP TABLE IF EXISTS `{$controlChild}`");
            $db->statement("DROP TABLE IF EXISTS `{$controlParent}`");
        }
    }

    /**
     * MYSQL-ONLY PROPERTY — the truncation the strict mode exists to prevent,
     * and the round-trip assertion's own licence to mean something.
     *
     * `test_the_worked_example_endpoint_round_trips_its_message_text_on_mysql()`
     * asserts that `STRICT_TRANS_TABLES` is on the session and then that a value
     * came back byte-identical. Without THIS test the second assertion would be
     * guarding nothing: on a non-strict session the identical insert returns
     * success and a truncated value, and the file would be green either way.
     *
     * Both halves use the SAME shipped column — `telegram_accounts.label`,
     * MEASURED `varchar(255) NOT NULL` on the executed dial — and the SAME
     * over-long value. The only thing that differs is the session's SQL mode.
     * That is what makes this a CONTROL rather than a second example: it
     * discriminates "the strict mode raised" from "the value did not fit, ever".
     *
     * Measured on this host, `STRICT_TRANS_TABLES` present → driver 1406
     * (SQLSTATE 22001); `STRICT_TRANS_TABLES` removed → the row lands with the
     * label TRUNCATED to exactly 255 characters.
     */
    public function test_a_strict_session_refuses_an_over_long_value_1406_and_a_non_strict_one_truncates_it(): void
    {
        $db = $this->migratedMysqlConnection();

        $accountId = self::CARRIER_TWO + 900;
        $label = self::MYSQL_TRUNCATION_LABEL;

        self::assertSame(
            300,
            strlen($label),
            'the probe label is not exactly 300 characters, so this test is not measuring a 300-character '
            .'value against a 255-character column and is therefore not measuring truncation at all.',
        );

        $originalMode = (string) $db->selectOne('SELECT @@SESSION.sql_mode AS mode')->mode;

        try {
            // ---- the strict half: MUST raise, with the driver code ----
            $strict = null;
            try {
                $db->table('telegram_accounts')->insert([
                    'user_id' => $accountId,
                    'label' => $label,
                    'type' => 'user',
                    'dc_id' => 2,
                ]);
            } catch (QueryException $e) {
                $strict = $e;
            }

            self::assertNotNull(
                $strict,
                'a 300-character value fitted a varchar(255) on a STRICT_TRANS_TABLES session without '
                .'raising. Either the mode is not what the worked-example assertion claims, or the column '
                .'is wider than measured — and in both cases that assertion\'s round-trip is not evidence '
                .'that values survive.',
            );

            self::assertSame(
                1406,
                (int) ($strict->errorInfo[1] ?? 0),
                'the driver code was not 1406. Read $e->errorInfo[1], never the SQLSTATE. '
                .json_encode($strict->errorInfo),
            );

            self::assertFalse(
                $db->table('telegram_accounts')->where('user_id', $accountId)->exists(),
                'the strict insert raised 1406 yet left a row behind, so the refusal did not roll back and '
                .'the truncation this test is about would not have been observable.',
            );

            // ---- the control half: SAME value, SAME column, mode changed ----
            $nonStrict = str_replace('STRICT_TRANS_TABLES,', '', $originalMode);
            self::assertStringNotContainsString(
                'STRICT_TRANS_TABLES',
                $nonStrict,
                'could not build a non-strict mode out of the session\'s own, so the control half would not '
                .'be a control.',
            );

            $db->statement('SET SESSION sql_mode = ?', [$nonStrict]);

            $db->table('telegram_accounts')->insert([
                'user_id' => $accountId,
                'label' => $label,
                'type' => 'user',
                'dc_id' => 2,
            ]);

            $stored = $db->table('telegram_accounts')->where('user_id', $accountId)->value('label');

            self::assertIsString($stored, 'the non-strict insert reported success but stored no label');
            self::assertSame(
                255,
                strlen($stored),
                'the non-strict session did NOT truncate. If it raised or stored all 300 characters, the '
                .'strict-mode refusal above is not attributable to strictness, and the worked-example '
                .'round-trip has no failure mode to catch.',
            );
            self::assertSame(
                substr($label, 0, 255),
                $stored,
                'the non-strict session stored something that is neither the whole value nor its prefix, so '
                .'this is not the truncation this test claims to be observing',
            );
        } finally {
            // Restored in a finally, so a raise cannot leave the session
            // permissive for every later assertion in this process.
            $db->statement('SET SESSION sql_mode = ?', [$originalMode]);

            self::assertSame(
                $originalMode,
                (string) $db->selectOne('SELECT @@SESSION.sql_mode AS mode')->mode,
                'the session SQL mode was not restored. Every later value-fidelity assertion would then be '
                .'measuring a session this test made permissive.',
            );
        }
    }

    /** The over-long value for the truncation pair: MEASURED 300 characters into a `varchar(255)`. */
    private const MYSQL_TRUNCATION_LABEL = 'teleframe truncation probe: this value is deliberately longer than the 255 character column so that a strict session refuses it with 1406 while a non strict session truncates it instead. It is synthetic filler, carries no secret, and exists only to be over long. still synthetic filler text. still sy';

    /**
     * Every classified endpoint grouped by the obligation it owes, so the sweep
     * can assert its own branches are populated.
     *
     * @param  list<array{ctor:string,kind:string,tables:list<string>,reason:string}>  $endpoints
     * @return array<string,list<string>> obligation => ctors
     */
    private function endpointsByObligation(array $endpoints): array
    {
        $byObligation = [];
        foreach (self::ENDPOINT_OBLIGATIONS as $obligation) {
            $byObligation[$obligation] = [];
        }

        foreach ($endpoints as $endpoint) {
            $byObligation[self::obligationOf($endpoint['ctor'])][] = $endpoint['ctor'];
        }

        return $byObligation;
    }

    /**
     * A carrier, its vector FIELD, and a TWO-MEMBER payload — all selected by
     * rule from the derivation and the fixtures, never transcribed.
     *
     * The carrier is the first classified `update-carrier` whose payload actually
     * carries a LIST of updates; `updateShort` is skipped because it carries a
     * singular `update:Update` REF, which is a different shape and is exercised by
     * the sweep instead. The vector field is read off that payload, which is the
     * only way to survive the `updates` / `other_updates` split across the family.
     *
     * The two members are the first two endpoints that (a) owe an own-row,
     * (b) have a fixture, and (c) nest NO `message` constructor at any depth —
     * (c) because a message-carrying fixture reaches `writeMessage()` and would
     * need a peer-scoped parent this test has no reason to seed.
     *
     * The returned payload is the carrier's REAL fixture with its vector replaced
     * by the two members, so the shape reaching the engine is the shape the wire
     * produces. `_members` carries the member names out for the assertions; it is
     * stripped before the payload is ingested.
     *
     * @return array{0:string,1:string,2:array<string,mixed>}
     */
    private function twoMemberCarrierPayload(): array
    {
        $fixtures = self::endpointFixtures();

        $carrier = null;
        $vectorField = null;
        foreach (self::classifiedEndpoints() as $endpoint) {
            if ($endpoint['kind'] !== EndpointCoverage::KIND_UPDATE_CARRIER) {
                continue;
            }

            $payload = $fixtures[$endpoint['ctor']] ?? null;
            if (! is_array($payload)) {
                continue;
            }

            foreach ($payload as $field => $value) {
                if (! is_array($value) || ! array_is_list($value)) {
                    continue;
                }
                foreach ($value as $item) {
                    if (is_array($item) && isset($item['_']) && str_starts_with((string) $item['_'], 'update')) {
                        $carrier = $endpoint['ctor'];
                        $vectorField = (string) $field;

                        break 3;
                    }
                }
            }
        }

        self::assertNotNull(
            $carrier,
            'no classified update-carrier has a fixture carrying a list of updates, so this proof has no '
            .'subject. Add such a fixture, or re-derive the classification — do NOT relax the assertion.',
        );

        $members = $this->twoFixtureMembers();
        $payload = $fixtures[$carrier];
        $payload[$vectorField] = $members;
        $payload['seq'] = self::CARRIER_SEQ;
        $payload['_members'] = array_map(
            static fn (array $m): string => (string) $m['_'],
            $members,
        );

        return [$carrier, $vectorField, $payload];
    }

    /**
     * The first two own-row fixtures that nest no message constructor.
     *
     * @return list<array<string,mixed>> two fixture payloads
     */
    private function twoFixtureMembers(): array
    {
        $fixtures = self::endpointFixtures();
        $members = [];

        foreach (self::classifiedEndpoints() as $endpoint) {
            if (count($members) === 2) {
                break;
            }
            if (self::obligationOf($endpoint['ctor']) !== self::OBLIGATION_OWN_ROW) {
                continue;
            }
            $payload = $fixtures[$endpoint['ctor']] ?? null;
            if (! is_array($payload) || $this->carriesMessageNode($payload)) {
                continue;
            }
            $members[] = $payload;
        }

        self::assertCount(
            2,
            $members,
            'fewer than two own-row fixtures nest no message constructor, so the two-member carrier proof '
            .'has no second member. ONE member would not do: it cannot distinguish "the unwrap walked the '
            .'vector" from "the unwrap placed element 0 and stopped".',
        );

        return $members;
    }

    /**
     * Does this payload carry a `message*` constructor at ANY depth?
     *
     * Structural, not a top-level name check: `message` arrives NESTED inside
     * `updateNewMessage`, which is exactly the case that routes to
     * `UpdateIngestor::writeMessage()`.
     *
     * @param  array<string,mixed>  $payload
     */
    private function carriesMessageNode(array $payload): bool
    {
        if (isset($payload['_']) && is_string($payload['_']) && str_starts_with($payload['_'], 'message')) {
            return true;
        }

        foreach ($payload as $value) {
            if (is_array($value) && $this->carriesMessageNode($value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The tenant row, exactly as the EXECUTED `telegram_accounts` requires it,
     * and exactly as the tenant FK requires: keyed on `user_id`.
     *
     * `app_id` is left out because it is nullable and part of the
     * `['app_id','label']` unique index. No session string, no bot token: every
     * column here is synthetic.
     */
    private function seedTenant(Connection $db, int $userId): void
    {
        $db->table('telegram_accounts')->insert([
            'user_id' => $userId,
            'label' => 'mysql-endpoint-'.$userId,
            'type' => 'user',
            'dc_id' => 2,
        ]);
    }

    private function tableExists(string $table): bool
    {
        $found = $this->migratedMysqlConnection()->select(
            'SELECT TABLE_NAME AS name FROM information_schema.TABLES '
            ."WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '".addslashes($table)."'",
        );

        return $found !== [];
    }

    /**
     * A refusal, rendered so the report carries the ENGINE's own words —
     * including the driver code, which is the whole point.
     */
    private function describe(Throwable $e): string
    {
        if ($e instanceof QueryException) {
            return sprintf(
                'MySQL %s (SQLSTATE %s): %s',
                (string) ($e->errorInfo[1] ?? '?'),
                (string) ($e->errorInfo[0] ?? '?'),
                $this->condense($e->getMessage()),
            );
        }

        return $e::class.': '.$this->condense($e->getMessage());
    }

    /**
     * Collapse a failure message to one readable line.
     *
     * Collected findings are meant to be read in one report, so a multi-line
     * assertion diff per endpoint would bury the ones that matter.
     */
    private function condense(string $message): string
    {
        $flat = trim((string) preg_replace('/\s+/', ' ', $message));

        return mb_strlen($flat) > 400 ? mb_substr($flat, 0, 400).'…' : $flat;
    }

    /**
     * @param  array<string,string>|list<string>  $findings
     */
    private static function render(array $findings): string
    {
        if ($findings === []) {
            return '';
        }

        $lines = [];
        foreach ($findings as $key => $value) {
            $lines[] = is_int($key) ? "\n  - {$value}" : "\n  - {$key}: {$value}";
        }

        return implode('', $lines);
    }
}
