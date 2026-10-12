<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Schema\Mirror\EndpointCoverage;
use MeRezaRezaei\Teleframe\Tests\Concerns\AssertsEndpointInsertObligations;
use MeRezaRezaei\Teleframe\Tests\Ingest\Concerns\HasEndpointFixtures;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Every classified MTProto endpoint is INSERTABLE into the relational
 * database dedicated and created for it — or it says so out loud.
 *
 * ## WHAT MAKES THESE TESTS RELATED RATHER THAN 175 DISCONNECTED CASES
 *
 * 175, not the 178 the plan's prose quotes: 178 was a `grep -c` LINE count and
 * three of those lines are methods, not constructors. The MEASURED count of
 * parsed `update*` constructors is 175, and `EndpointCoverage::classify()`
 * classifies exactly those 175 — `{"root-fact":0,"nested-source":0,
 * "log-only":0,"unmodelled":175}`. Measured in this task with:
 *
 * ```php
 * EndpointCoverage::classify(
 *     TlParser::parseFile($root.'/schema/sources/TL_telegram_v227.tl'),
 *     MirrorCatalog::load(SchemaArtifacts::catalog(), $scheme),
 * )->counts();
 * ```
 *
 * Nothing here depends on either number: the provider is built by iterating the
 * derived classification, so it is right by construction either way.
 *
 * The endpoint list is not written here. It is DERIVED, by
 * `EndpointCoverage::classify()`, from the parsed layer-227 scheme and the
 * loaded mirror catalog — the same derivation Phase 2 gates. So a new TL
 * constructor becomes a new OBLIGATION the moment the schema pipeline runs,
 * rather than a surprise somebody discovers in production. A hand-typed list of
 * constructor names would be a second copy of the scheme, and would drift into
 * a coverage claim that reads as a safety net while covering nothing.
 *
 * That is the operator's requirement discharged: *"making sure any update comes
 * to those end points are insertable into the relational database dedicated and
 * created"* — and *"the correct set of tests are these and they are RELATED in a
 * way that lets agents figure out the correct shape of data"*. Relatedness is
 * the point: the shape of a payload is discoverable from the peers, and the set
 * of payloads owed is derivable, so neither is a transcription.
 *
 * ## WHAT "INSERTABLE" OBLIGATES, PER KIND (Step 1 of the plan, stated here)
 *
 * | kind             | the fixture must                                       | the proof must show                                                            |
 * |------------------|--------------------------------------------------------|---------------------------------------------------------------------------------|
 * | `root-fact`      | carry every field the row has a NOT NULL column for     | the row lands in the named table with those columns EQUAL to the fixture's values |
 * | `nested-source`  | carry the parent ctor plus the nested ctor's payload    | a row lands in BOTH tables, the child's scope resolving to the parent's key      |
 * | `log-only`       | carry the fields the `tf_updates_*` children map        | a `tf_updates` row lands, plus one child row per mapped field present            |
 * | `unmodelled`     | be shaped as the wire decodes it                       | a `tf_updates` row lands and NO deeper table gains a row — the ABSENCE is the assertion |
 *
 * The last row is the one that is easy to get wrong, and it is why
 * `test_an_unmodelled_endpoint_lands_only_in_the_fact_log` exists: an
 * `unmodelled` endpoint that quietly starts writing domain rows means the
 * catalog changed without the classification being regenerated.
 *
 * ## VALUES, NOT JUST PRESENCE
 *
 * A row landing with a FABRICATED key is a defect this repo has already
 * shipped once (`tests/Schema/Mirror/FabricatedMediaKeyTest.php`), so the
 * worked example asserts the values it sent. If a `tf_messages` value
 * assertion fails, **THE FIXTURE IS WRONG, NOT THE ASSERTION** — most often
 * because the peer-scoped key needs `(account_id, peer_type, peer_id, id)` and
 * a bare `id` resolves to nothing. That key is REAL in both halves, measured:
 * `src/Laravel/Migrations/2026_09_14_200010_create_tf_messages_tables.php:211`
 * declares `$table->primary(['account_id', 'peer_type', 'peer_id', 'id'])`
 * (35 more such declarations in that same file, all four columns), and
 * `UpdateIngestor.php:484` is the `updateOrInsert` whose conflict target is
 * literally those four keys. `tests/Store/MessagePeerScopedPrimaryKeyTest.php`
 * is the executable specification for the keying. (NB the plan's prose cites
 * `tests/Mirror/MessagePeerScopedPrimaryKeyTest.php`; the file that exists is
 * under `tests/Store/` — measured with `ls tests/{Store,Mirror}/MessagePeerScopedPrimaryKeyTest.php`.)
 *
 * CORRECTED 2026-10-05T11:31Z by RESIDUE-RECONCILE: this block previously cited
 * `200010:108` and `UpdateIngestor:409` as where the key "is verified in the
 * shipped DDL". Both citations point at PROSE, not code — `:108` is a line
 * inside that migration's own docblock, and `:409` is a docblock line inside
 * `updateMessage()`. They quote each other; neither declares anything. A
 * citation that looks like a code citation and lands in a comment is the same
 * defect this repo's other stale-number findings are, so the real lines are
 * named instead: `:211` (DDL) and `:484` (the call).
 *
 * ## THE SCHEMA IS EXECUTED, NEVER TRANSCRIBED
 *
 * `IngestTestCase` migrates the SHIPPED dial; the domain-table set is read from
 * `sqlite_master` at run time, so this file names no table list and no join
 * column list. A test that transcribes the DDL is a second copy of it.
 *
 * ## NO CREDENTIALS
 *
 * Every value is synthetic: no session string, no real `access_hash`, no
 * database password.
 */
final class EveryClassifiedEndpointIngestsTest extends IngestTestCase
{
    use AssertsEndpointInsertObligations;
    use HasEndpointFixtures;

    /**
     * The worked example: `updateNewMessage`, the densest common case. A
     * root-fact ctor whose payload nests Peer and MessageMedia, so a failure
     * here implicates the root/nested/decomposition path at once.
     */
    public function test_the_worked_example_endpoint_lands_its_row_and_its_nested_rows(): void
    {
        $payload = self::endpointFixtures()['updateNewMessage'] ?? null;
        self::assertIsArray($payload, 'updateNewMessage must have a fixture — see Step 4');

        $accountId = self::endpointFixtureAccount();
        $this->seedTenant($accountId);

        $root = $this->ingestor()->ingest($payload, $accountId);

        self::assertNotNull($root, 'the ingestor returned no root model');
        self::assertSame('updateNewMessage', (string) ($payload['_']));

        // The generic fact log MUST have a row. This holds for every endpoint,
        // which is exactly why the unmodelled population is not "no storage".
        $this->assertTrue(
            $this->hasRow('tf_updates', ['account_id' => $accountId, 'constructor' => 'updateNewMessage']),
            'tf_updates must carry a row for every ingested update — this is the generic fact log',
        );

        // And the modelled fact must have landed too. The row is looked up on
        // the FULL peer-scoped key the shipped DDL declares, so a lookup that
        // silently matched nothing cannot pass as a hit.
        $row = DB::table('tf_messages')
            ->where('account_id', $accountId)
            ->where('peer_type', self::ENDPOINT_PEER_TYPE)
            ->where('peer_id', self::ENDPOINT_PEER_ID)
            ->where('id', self::ENDPOINT_MESSAGE_ID)
            ->first();

        self::assertNotNull(
            $row,
            'no tf_messages row landed on the peer-scoped key (account_id, peer_type, peer_id, id) — '
            .'either the ingest dropped the modelled fact, or the fixture\'s peer does not match '
            .'the peer the writer derives. Do NOT weaken this assertion; fix the fixture.',
        );

        self::assertSame(
            self::ENDPOINT_MESSAGE_TEXT,
            (string) $row->message,
            'the message text did not survive the write — a fabricated value is the defect this '
            .'assertion exists to catch',
        );
        self::assertSame(
            self::ENDPOINT_MESSAGE_DATE,
            (int) $row->date,
            'the date did not survive the write',
        );
        self::assertSame(
            'message',
            (string) $row->constructor,
            'the discriminator must be the constructor the wire actually sent, not a coerced default',
        );
    }

    /**
     * An UNMODELLED endpoint lands in the fact log and adds NO domain row.
     *
     * The endpoint is SELECTED FROM THE DERIVED classification, never a
     * hardcoded name, so this test follows the catalog rather than a name that
     * may be modelled tomorrow. The selection rule is stated and derived too,
     * because a rule picked to make the assertion pass would be the defect:
     *
     * **Candidates are unmodelled endpoints that HAVE a fixture and whose
     * payload nests no message constructor.**
     *
     * The message clause is not a convenience — it is measured. With the
     * catalog as it stands, `EndpointCoverage` reports `root-fact = 0` and
     * `nested-source = 0` (see that class's docblock), so EVERY one of the 175
     * endpoints is `unmodelled`. That is not a contradiction of the worked
     * example: `tf_messages` is written by `UpdateIngestor::writeMessage()`, the
     * DEDICATED message path (routed on `MESSAGE_CTORS`, `UpdateIngestor:102`),
     * not by catalog routing. Measured with a probe over the four fixtures:
     *
     * | fixture                  | tables that gained a row |
     * |--------------------------|--------------------------|
     * | `updateNewMessage`       | `tf_messages`, `tf_message_search`, `tf_messages_from_id` (+ fact log) |
     * | `updateDeleteMessages`   | fact log only |
     * | `updateUserStatus`       | fact log only |
     * | `updateReadHistoryInbox` | fact log only |
     *
     * So the property under test — an endpoint the CATALOG does not model adds
     * no row to a table the CATALOG models — is only observable on an endpoint
     * that does not reach the dedicated message writer. Selecting a
     * message-carrying endpoint here would assert something the design
     * deliberately does not promise.
     *
     * **MEASURED: this test derives `updateDeleteMessages`.** Its four fixtures
     * sit at these indices of the derived classification, so the first one that
     * both has a fixture and nests no message node wins:
     *
     * | index | ctor                      | has fixture | nests a message node |
     * |-------|---------------------------|-------------|-----------------------|
     * | 48    | `updateDeleteMessages`    | yes         | no                    |
     * | 90    | `updateNewMessage`        | yes         | **yes** — skipped     |
     * | 121   | `updateReadHistoryInbox`  | yes         | no                    |
     * | 157   | `updateUserStatus`        | yes         | no                    |
     *
     * MEASURED by iterating `EndpointCoverage::classify(...)->endpoints()` and
     * printing the index of each fixture — see the class docblock for the
     * classification call. `updateDeleteMessages` wins on fixture availability
     * AND on the message clause independently, so the gate does not rest on the
     * clause alone; and it is a REAL endpoint, not a name invented here, so the
     * assertion is not vacuous.
     *
     * The `updateUserStatus` index was 158 and is now 157. `updateShort` left
     * the `unmodelled` population on 2026-10-05 — it carries a singular
     * `update:Update`, so it is an `update-carrier` and its carried update is
     * unwrapped. Indices at or below 90 are unaffected; the table is re-measured
     * rather than left to drift.
     */
    public function test_an_unmodelled_endpoint_lands_only_in_the_fact_log(): void
    {
        $unmodelled = array_values(array_filter(
            self::classifiedEndpoints(),
            static fn (array $e): bool => $e['kind'] === 'unmodelled',
        ));

        self::assertNotEmpty($unmodelled, 'if nothing is unmodelled, this gate is decoration — '
            .'re-derive the classification before trusting a green run');

        $ctor = $this->firstUnmodelledNonMessageEndpoint($unmodelled);
        self::assertNotNull(
            $ctor,
            'no unmodelled endpoint both has a fixture and nests no message constructor, so this '
            .'gate has nothing to observe. Add such a fixture, or re-derive the classification.',
        );

        $payload = self::endpointFixtures()[$ctor];
        $accountId = self::endpointFixtureAccount();
        $this->seedTenant($accountId);

        $before = $this->countDomainRows($accountId);

        $this->ingestor()->ingest($payload, $accountId);

        $this->assertTrue(
            $this->hasRow('tf_updates', ['account_id' => $accountId, 'constructor' => $ctor]),
            "{$ctor} is classified unmodelled but wrote no tf_updates row; the label is wrong "
            .'or the generic path is broken',
        );

        self::assertSame(
            $before,
            $this->countDomainRows($accountId),
            "{$ctor} is classified unmodelled, so it must not add rows to any domain table. It did. "
            .'Either the catalog changed without regenerating the classification, or the label is '
            .'wrong — regenerate and re-derive rather than adjusting the assertion.',
        );
    }

    /**
     * @param  list<array{ctor:string,kind:string,tables:list<string>,reason:string}>  $unmodelled
     */
    private function firstUnmodelledNonMessageEndpoint(array $unmodelled): ?string
    {
        $fixtures = self::endpointFixtures();

        foreach ($unmodelled as $e) {
            $ctor = $e['ctor'];
            if (! isset($fixtures[$ctor])) {
                continue; // fixture availability is the provider gate's obligation, not this one's
            }
            if ($this->carriesMessageNode($fixtures[$ctor])) {
                continue; // reaches UpdateIngestor::writeMessage() — see the test docblock
            }

            return $ctor;
        }

        return null;
    }

    /**
     * Does this payload carry a message constructor at ANY depth?
     *
     * The walk is structural, not a name check on the top level: `message`
     * arrives NESTED inside `updateNewMessage`, which is exactly the case that
     * routes to the dedicated writer.
     *
     * @param  array<string, mixed>  $payload
     */
    private function carriesMessageNode(array $payload): bool
    {
        if (($payload['_'] ?? null) !== null) {
            $ctor = (string) $payload['_'];
            if (str_starts_with($ctor, 'message')) {
                return true;
            }
        }

        foreach ($payload as $value) {
            if (is_array($value)) {
                if (array_is_list($value)) {
                    foreach ($value as $item) {
                        if (is_array($item) && $this->carriesMessageNode($item)) {
                            return true;
                        }
                    }

                    continue;
                }

                if ($this->carriesMessageNode($value)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * The backlog gate, per KIND. Every classified endpoint owes its fixture
     * and owes to reach storage — but "reach storage" means a DIFFERENT thing
     * per kind, and a single assertion for all of them is a lie for two kinds.
     *
     * | kind             | the fixture must                                        | the proof must show                                                                  |
     * |------------------|---------------------------------------------------------|--------------------------------------------------------------------------------------|
     * | `root-fact`      | carry every field the row has a NOT NULL column for      | the row lands in the named table with those columns EQUAL to the fixture's values       |
     * | `nested-source`  | carry the parent ctor plus the nested ctor's payload     | a row lands in BOTH tables, the child's scope resolving to the parent's key             |
     * | `log-only`       | carry the fields the `tf_updates_*` children map         | a `tf_updates` row lands, plus one child row per mapped field present                   |
     * | `unmodelled`     | be shaped as the wire decodes it                        | a `tf_updates` row lands carrying ITS OWN ctor, and NO domain table gains a row          |
     * | `update-carrier` | carry at least one real update in its `Vector<Update>`   | EVERY member lands under its OWN ctor; the carrier itself owes NO row                     |
     * | `rpc-result`     | be shaped as the wire decodes it                        | NO `tf_updates` row carrying its ctor — it is not an update — and the endpoint is still classified |
     *
     * THE TWO NEW KINDS ARE NOT A WEAKENING. Before them this gate asserted one
     * thing of every endpoint: "a `tf_updates` row carrying your own ctor". That
     * is unobtainable for a carrier (a row naming `updates` would record a thing
     * that never happened as though it were an update) and meaningless for an
     * rpc-result (nothing was owed). MEASURED, before the change: `updates`,
     * `updatesCombined` and `updatesTooLong` FAILED this gate, and the first two
     * were not broken — they were being asked for a row they must not have,
     * while `updates.difference` PASSED it while silently dropping the
     * `updateChannel` its `other_updates` carried. A gate that is unmeetable by
     * three endpoints and satisfied by a fourth that loses data is not a gate.
     *
     * The assertion per kind is therefore STRONGER than the one it replaces: a
     * carrier must place N of N members, each under its own constructor, where
     * the old gate asked for one row that could never exist.
     *
     * ONE method, ONE provider row per endpoint — never 175 methods. The rows
     * that fail with "has no fixture" are the project's real backlog made
     * executable: they are left RED and COUNTED, not papered over with invented
     * payloads.
     *
     * ## MEASURED 2026-10-05 — the coverage ratio, via `HasEndpointFixtures::fixtureCoverage()`
     *
     * ```
     * required : 175     <- classify() over the parsed layer-227 scheme
     * present  : 175     <- 4 curated base + payloads from 11 group files
     * missing  : 0
     * extra    : 0
     * groups   : 11
     * duplicates : 0
     * skipped  : 0
     * ```
     *
     * The classification counts changed under the new kinds — MEASURED with
     * `php bin/generate-endpoint-coverage.php`, which walks the same scheme and
     * catalog this gate does:
     *
     * ```
     * update-carrier: 5   rpc-result: 10   root-fact: 0
     * nested-source: 0    log-only: 0       unmodelled: 160
     * universe (parsed update* ctors): 175
     * ```
     *
     * 175 is unchanged because the partition is TOTAL over the same universe —
     * `EndpointCoverage` gained kinds, not exclusions. The per-kind split moved
     * from `{root-fact:0, nested-source:0, log-only:0, unmodelled:175}` because
     * 15 constructors whose declared result type is NOT `Update` were being
     * counted as update endpoints.
     *
     * The full file exceeds a 900 s `timeout` — measured, exit 124 — so the whole
     * file is NOT run to completion by this lane and is not claimed to be. The
     * per-kind half of this gate is small and IS run:
     *
     * ```
     * test_every_classified_endpoint_reaches_the_fact_log@updates            OK
     * test_every_classified_endpoint_reaches_the_fact_log@updatesTooLong     OK
     * test_every_classified_endpoint_reaches_the_fact_log@updates.difference OK
     * ```
     *
     * ## WHY THE OLD BLOCK WAS WORSE THAN WRONG — DO NOT REINTRODUCE IT
     *
     * A docblock that says "red on purpose" is a licence to ignore red. The next
     * reader to see 171 failures would consult this note, conclude the red was
     * expected, and walk past a genuinely broken ingest. A stale number in a
     * docblock is green by construction — nothing fails when it drifts — and a
     * stale EXCUSE is worse than a stale number, because it actively misdirects
     * the next agent away from the failure it was about to cause.
     *
     * The replacement rule, and it is the rule this whole file follows: publish
     * the MEASUREMENT and the COMMAND that produced it, never a conclusion about
     * what the measurement means. Re-run it; do not inherit it.
     *
     * @return array<string, array{0: string}>
     */
    public static function endpointProvider(): array
    {
        $out = [];
        foreach (self::classifiedEndpoints() as $e) {
            $out[$e['ctor']] = [$e['ctor']];
        }

        return $out;
    }

    #[DataProvider('endpointProvider')]
    public function test_every_classified_endpoint_reaches_the_fact_log(string $ctor): void
    {
        $fixtures = self::endpointFixtures();
        self::assertArrayHasKey(
            $ctor,
            $fixtures,
            "{$ctor} is classified and therefore has an insert obligation, but has no fixture. "
            .'Add one to HasEndpointFixtures::endpointFixtures() — this is the obligation Phase 3 '
            .'exists to make impossible to ignore.',
        );

        $kind = self::kindOf($ctor);
        $accountId = self::endpointFixtureAccount();
        $this->seedTenant($accountId);

        $this->ingestor()->ingest($fixtures[$ctor], $accountId);

        // DELEGATED to the shared trait since 2026-10-05, and the delegation is
        // LOAD-BEARING rather than tidiness. This file carried its own private copy
        // of the per-kind branch, and the MySQL twin (`a8daba89`) had to grow a
        // THIRD copy — at which point a fix to the obligation logic had to be made
        // three times or not at all.
        //
        // The copy here was also WRONG in a way the shared one is not. It walked
        // the payload looking for `array_is_list($value)` fields, so it found zero
        // members for `updateShort`, which carries a SINGULAR `update:Update` ref
        // rather than a vector — and then `assertNotEmpty` fired on a perfectly
        // healthy ingest. That is the 'green gate checking nothing' failure in its
        // most annoying form: it reported a defect that did not exist, which trains
        // a reader to ignore it. The trait's `carrierMembersOf()` resolves a
        // singular ref through `carriedUpdateName()`, so both shapes are covered.
        //
        // The MySQL twin's copy of the walk filtered on `str_starts_with($name,
        // 'update')` and therefore collected `updates.state` — an `updates.State`
        // RPC result — as a carried member. The shared trait requires a node to be
        // CLASSIFIED and NOT one of the two rpc-result kinds, read from
        // EndpointCoverage. **A name prefix is a heuristic; the classifier is the
        // authority** — the same principle the copy-don't-design directive states
        // about the schema, applied here to the harness that checks it.
        $this->assertEndpointObligationIsDischarged($ctor, $fixtures[$ctor], $accountId);
    }

    /**
     * SUPERSEDED 2026-10-05 — the per-kind branch now lives in
     * tests/Concerns/AssertsEndpointInsertObligations so the SQLite and MySQL
     * lanes cannot drift. Kept as a comment, not code, so the reasoning survives.
     *
     * if ($kind === EndpointCoverage::KIND_UPDATE_CARRIER) {
     *     $this->assertCarrierUnwrapped($ctor, $fixtures[$ctor], $accountId);
     *
     *     return;
     * }
     *
     * if ($kind === EndpointCoverage::KIND_RPC_RESULT) {
     *     $this->assertRpcResultOwesNoRow($ctor, $accountId);
     *
     *     return;
     * }
     */

    /**
     * The classified kind of one endpoint, read from the derived classification.
     *
     * @return string one of `EndpointCoverage::KIND_*`
     */
    private static function kindOf(string $ctor): string
    {
        foreach (self::classifiedEndpoints() as $e) {
            if ($e['ctor'] === $ctor) {
                return $e['kind'];
            }
        }

        self::fail("{$ctor} is not classified at all, so this gate cannot say what it owes");
    }

    /**
     * The ingestor the production path uses. Not transcribe-able — resolved
     * through the same constructor the service provider binds.
     */
    private function ingestor(): UpdateIngestor
    {
        return new UpdateIngestor;
    }

    /**
     * Seed the parent of every `tf_*` tenant FK.
     *
     * Since `7dd07b4a` landed 265 tenant foreign keys, any `tf_*` row carrying
     * an `account_id` is refused with FOREIGN KEY constraint failed unless a
     * `telegram_accounts` row exists — and the key is `telegram_accounts.user_id`
     * (`2026_10_04_000700`, PARENT_UNIQUE `telegram_accounts_user_id_unique`),
     * NOT `telegram_accounts.id`. Seeding `id` alone satisfies nothing.
     *
     * No session string, no bot token: every column here is synthetic.
     */
    private function seedTenant(int $accountId): void
    {
        DB::table('telegram_accounts')->insert([
            'user_id' => $accountId,
            'label' => 'endpoint-fixture-'.$accountId,
            'type' => 'user',
            'dc_id' => 2,
        ]);
    }

    /**
     * @param  array<string, mixed>  $where
     */
    private function hasRow(string $table, array $where): bool
    {
        return DB::table($table)->where($where)->exists();
    }

    /**
     * The two ENGINE-SPECIFIC primitives the shared obligation trait requires.
     *
     * Everything kind-specific — which obligation an endpoint owes, what a
     * carrier carries, what an rpc-result must NOT write — lives in the trait, so
     * this lane and the MySQL twin cannot drift. All this file supplies is how to
     * ask its own engine two questions, and they are the SAME two queries on both
     * engines, which is what makes the shared logic sound.
     *
     * @see AssertsEndpointInsertObligations
     */
    protected function hasEndpointFactRow(int $accountId, string $ctor): bool
    {
        return $this->hasRow('tf_updates', ['account_id' => $accountId, 'constructor' => $ctor]);
    }

    protected function countEndpointFactRows(int $accountId): int
    {
        // COUNTS tf_updates ROWS, not domain-table rows. Wired wrongly the first
        // time — pointed at countDomainRows() — and the symptom was a carrier
        // asserting "0 is identical to 1" on a perfectly healthy ingest, because
        // the domain count excludes the very fact log the obligation is about.
        return DB::table('tf_updates')->where('account_id', $accountId)->count();
    }

    /**
     * Total rows across every DOMAIN table — every shipped table carrying an
     * `account_id` EXCEPT the generic fact log (`tf_updates` and its
     * `tf_updates_*` children).
     *
     * The table set and the `account_id` presence are read from the EXECUTED
     * schema, never transcribed: a hardcoded list would be a second copy of the
     * dial, and it would go stale the moment a table is added — which is
     * precisely the moment an `unmodelled` endpoint starts writing rows and
     * this assertion is supposed to notice.
     */
    private function countDomainRows(int $accountId): int
    {
        $total = 0;
        foreach ($this->domainTables() as $table) {
            $total += DB::table($table)->where('account_id', $accountId)->count();
        }

        return $total;
    }

    /**
     * @return list<string>
     */
    private function domainTables(): array
    {
        $tables = [];
        foreach (DB::select("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name") as $row) {
            $name = (string) ((array) $row)['name'];
            if (str_starts_with($name, 'sqlite_') || ! str_starts_with($name, 'tf_')) {
                continue;
            }
            if ($name === 'tf_updates' || str_starts_with($name, 'tf_updates_')) {
                continue; // the generic fact log is not a domain table
            }
            if (! $this->hasAccountColumn($name)) {
                continue;
            }
            $tables[] = $name;
        }

        self::assertNotEmpty($tables, 'no domain tables were discovered from the executed schema — '
            .'this assertion would be vacuously green, which is worse than red');

        return $tables;
    }

    private function hasAccountColumn(string $table): bool
    {
        $quoted = DB::connection()->getPdo()->quote($table);

        foreach (DB::select('PRAGMA table_info('.$quoted.')') as $info) {
            if ((string) ((array) $info)['name'] === 'account_id') {
                return true;
            }
        }

        return false;
    }
}
