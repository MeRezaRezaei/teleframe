<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Console;

use Illuminate\Database\Capsule\Manager as Capsule;
use MeRezaRezaei\Teleframe\Backfill\MemoryCursorStore;
use MeRezaRezaei\Teleframe\Core\Services\UserAccountScope;
use MeRezaRezaei\Teleframe\Laravel\Console\BackfillCommand;
use MeRezaRezaei\Teleframe\Tests\Ingest\IngestTestCase;
use MeRezaRezaei\Teleframe\Tests\Store\ShippedStoreSchema;
use MeRezaRezaei\Teleframe\Tests\Support\FakeUserScope;

/**
 * `teleframe:backfill` dials its account-scoped collection methods with
 * WELL-FORMED arguments — the defect this suite exists to prevent.
 *
 * The previous state of `mirrorAccountCollections()` dialled all four admitted
 * methods through `$scope->call($method)` with an EMPTY array, on the stated
 * ground that "the method catalog declares only flagged (optional) params on
 * these". That ground is false. Three of the four carry unflagged params, and
 * on a live account Telegram rejects a payload that omits them: the call
 * raises, the command warns and skips, and the table never fills. A caller
 * that throws and warns is worse than no caller, because the DDL, the model,
 * the policy admission and the catalog edge all read as "reachable".
 *
 * So the assertion here is on the ARGUMENTS, captured off a scope double —
 * not on "it did not crash", which is what a missing-argument bug looks like
 * from the outside — and on the ROWS that the resulting payloads actually
 * place, into a database whose schema came from EXECUTING the shipped
 * migrations via `ShippedStoreSchema::capsuleFor()`. Transcribing the DDL by
 * hand here would make this a second copy of the truth, and a second copy
 * silently drifts; `capsuleFor()` makes that structurally impossible.
 *
 * IT BITES, in both directions, in this one file:
 *
 *  - `test_the_empty_argument_array_fails_this_very_suite` — the pre-fix
 *    caller, reconstructed here, places no row. If the positive test below
 *    could pass with empty args, this one would fail; that is the proof the
 *    assertion is load-bearing rather than decorative.
 *  - the shipped default policy — every row is PRESENT, keyed on the shipped
 *    primary key, which is why the DDL is the capsule's own migrated truth.
 *
 * Provenance: the required-parameter sets under test are
 * src/Schema/schema/methods-mtproto.json — account.getBusinessChatLinks (line
 * 851, `params: []`), messages.getSavedDialogs (line 18157),
 * payments.getSavedStarGifts (line 26117), payments.getStarsStatus (line
 * 26652) — cross-checked against schema/sources/TL_telegram_v227.tl:2370,
 * :2618, :2872, :2846. The caller is
 * src/Laravel/Console/BackfillCommand.php::mirrorAccountCollections() and
 * accountCollectionParams(). The shipped DDL is
 * src/Laravel/Migrations/2026_09_14_200040_create_tf_stars_business_tables.php
 * and src/Laravel/Migrations/2026_09_14_200050_create_tf_misc_tables.php.
 */
final class BackfillCollectionArgsTest extends IngestTestCase
{
    /**
     * The tenants this fixture writes under.
     *
     * MEASURED, not assumed: extracted from the account ids this file
     * actually inserts. It was undeclared, and with foreign-key enforcement
     * on (19 constraints) every row was refused at the DATABASE rather than
     * at the code under test — the test failed on its own fixture.
     *
     * Declared HERE rather than by relaxing seedIngestTenant(): that helper
     * contracts that each test declares the tenants it means to use, so a
     * test writing under an unplanted id fails LOUDLY. These were failing
     * loudly, which is the mechanism working. A blanket auto-seed would also
     * make the orphan-refusal tests unable to plant an orphan.
     */
    protected function ingestTenantIds(): array
    {
        return [7];
    }

    /** The shipped stars/business DDL — tf_saved_star_gifts, tf_stars_transactions, tf_stars_subscriptions, tf_business_chat_links. */
    private const STARS_MIGRATION = __DIR__.'/../../src/Laravel/Migrations/2026_09_14_200040_create_tf_stars_business_tables.php';

    /** The shipped misc DDL — tf_saved_dialogs. */
    private const MISC_MIGRATION = __DIR__.'/../../src/Laravel/Migrations/2026_09_14_200050_create_tf_misc_tables.php';

    private const ACCOUNT = 7;

    private const PAGE_LIMIT = 100;

    private Capsule $capsule;

    protected function setUp(): void
    {
        parent::setUp();

        // Execute the SHIPPED migrations, in dependency order. capsuleFor()
        // clobbers the global container and the facade application, so the
        // real app is put back and the capsule's connection manager installed
        // as the app's `db` — which is what makes the command's writes resolve
        // to a database whose schema came from the migration FILES.
        $this->capsule = ShippedStoreSchema::capsuleFor(self::STARS_MIGRATION, self::MISC_MIGRATION);
        ShippedStoreSchema::restoreFacadeApplication($this->app);
        $this->app->instance('db', $this->capsule->getDatabaseManager());
        $this->app['config']->set('database.default', 'default');
        \Illuminate\Support\Facades\Facade::clearResolvedInstances();
    }

    /**
     * THE ASSERTION THAT MATTERS: the caller supplies the REQUIRED parameters
     * each catalogued method declares, read off the catalog rather than
     * guessed. Every `inputPeerSelf` below is the account's OWN value — the
     * tenancy these tables are keyed by — and `account.getBusinessChatLinks`
     * is asserted to carry NO parameters at all, because the catalog says it
     * has none (params: []), so "supply something" is as wrong there as
     * "supply nothing" is on the other three.
     */
    public function test_the_caller_supplies_every_required_parameter(): void
    {
        $scope = $this->bind();

        $this->artisan('teleframe:backfill', ['--account' => self::ACCOUNT, '--peer' => 'chat'])
            ->assertExitCode(0);

        $sent = $scope->sent;

        // payments.getSavedStarGifts: peer:InputPeer offset:string limit:int,
        // all unflagged. `offset` is a STRING cursor, not an int — '' is its
        // first-page value, and 0 would be a different type than the schema.
        self::assertSame(
            ['peer' => ['_' => 'inputPeerSelf'], 'offset' => '', 'limit' => self::PAGE_LIMIT],
            $sent['payments.getSavedStarGifts'] ?? null,
            'getSavedStarGifts declares peer/offset/limit unflagged; all three are required',
        );

        // payments.getStarsStatus: peer:InputPeer is the only unflagged param,
        // and inputPeerSelf is this method's OWN documented way to ask for the
        // calling account's balance.
        self::assertSame(
            ['peer' => ['_' => 'inputPeerSelf']],
            $sent['payments.getStarsStatus'] ?? null,
            'getStarsStatus requires peer, and self is the account\'s own balance',
        );

        // messages.getSavedDialogs: offset_date/offset_id/offset_peer/limit/hash
        // are all unflagged. Zero/empty is the "start from the beginning" value
        // for each, and the peer-typed cursor is a position, not a target.
        self::assertSame(
            [
                'offset_date' => 0,
                'offset_id' => 0,
                'offset_peer' => ['_' => 'inputPeerSelf'],
                'limit' => self::PAGE_LIMIT,
                'hash' => 0,
            ],
            $sent['messages.getSavedDialogs'] ?? null,
            'getSavedDialogs declares five unflagged params; all five are required',
        );

        // account.getBusinessChatLinks: `params: []`. The ONLY genuinely
        // param-free method of the four — and the one place an empty array is
        // the correct answer, which is why it must be asserted separately
        // rather than lumped in with the other three.
        self::assertSame(
            [],
            $sent['account.getBusinessChatLinks'] ?? null,
            'getBusinessChatLinks declares params: [] — an empty array is correct ONLY here',
        );

        // A param this caller invents is as much a bug as one it omits: an
        // unflagged `true` filter would NARROW what comes back and hide rows
        // that genuinely exist.
        foreach ($sent as $method => $params) {
            foreach (array_keys($params) as $name) {
                self::assertNotContains(
                    $name,
                    ['exclude_pinned', 'exclude_unsaved', 'exclude_saved', 'ton', 'sort_by_value', 'parent_peer'],
                    sprintf('%s: a flagged exclusion filter must never be passed, or real rows are hidden', $method),
                );
            }
        }
    }

    /**
     * ...and the parameters are not merely well-formed, they PLACE: each of
     * the five RPC-carrier tables gets its row out of the payload the command
     * itself dialled for. Asserted on ROWS, against migrated DDL, on the
     * shipped primary keys.
     */
    public function test_well_formed_parameters_place_a_row_in_every_rpc_carrier_table(): void
    {
        $this->bind();

        $this->artisan('teleframe:backfill', ['--account' => self::ACCOUNT, '--peer' => 'chat'])
            ->assertExitCode(0);

        $db = $this->db();

        self::assertSame(1, $db->table('tf_business_chat_links')->count(), 'tf_business_chat_links must place');
        self::assertSame(
            'https://t.me/+AbCdEf',
            (string) $db->table('tf_business_chat_links')->value('link'),
            'keyed on the decoded link string, which IS the shipped primary key',
        );
        self::assertSame(
            7,
            (int) $db->table('tf_business_chat_links')->value('views'),
            'the shipped base column carries the payload integer',
        );

        self::assertSame(1, $db->table('tf_saved_star_gifts')->count(), 'tf_saved_star_gifts must place');
        self::assertSame(
            55501,
            (int) $db->table('tf_saved_star_gifts')->value('id'),
            'keyed on the StarGift id, supplied by the catalog keyFrom gift.id',
        );
        self::assertSame(
            1712345678,
            (int) $db->table('tf_saved_star_gifts')->value('date'),
            'the unflagged date lands on the shipped NOT NULL column',
        );

        self::assertSame(1, $db->table('tf_stars_transactions')->count(), 'tf_stars_transactions must place');
        self::assertSame(
            'txn-beta',
            (string) $db->table('tf_stars_transactions')->value('id'),
            'keyed on the TL opaque string handle, which is the shipped primary key',
        );

        self::assertSame(1, $db->table('tf_stars_subscriptions')->count(), 'tf_stars_subscriptions must place');
        self::assertSame(
            'sub-alpha',
            (string) $db->table('tf_stars_subscriptions')->value('id'),
            'both stars tables come off the ONE getStarsStatus payload, not two calls',
        );
        self::assertSame(
            1712432078,
            (int) $db->table('tf_stars_subscriptions')->value('until_date'),
            'the shipped NOT NULL until_date lands',
        );

        self::assertSame(1, $db->table('tf_saved_dialogs')->count(), 'tf_saved_dialogs must place');
        self::assertSame(
            'savedDialog',
            (string) $db->table('tf_saved_dialogs')->value('constructor'),
            'the union discriminator records which SavedDialog ctor carried the row',
        );
        self::assertSame(
            9002,
            (int) $db->table('tf_saved_dialogs')->value('peer_id'),
            'keyed on the shipped (account_id, peer_type, peer_id) primary key',
        );

        foreach (['tf_business_chat_links', 'tf_saved_star_gifts', 'tf_stars_transactions', 'tf_stars_subscriptions', 'tf_saved_dialogs'] as $table) {
            self::assertSame(
                self::ACCOUNT,
                (int) $db->table($table)->value('account_id'),
                sprintf('%s: every row is scoped to the account it was fetched FOR', $table),
            );
        }
    }

    /**
     * THE BITE, and it is proved against the CATALOG rather than against a
     * hand-written expectation, so it cannot rot.
     *
     * `test_the_caller_supplies_every_required_parameter` states the expected
     * arguments as literals. This test derives the required set the same way
     * the source of truth defines it — every UNFLAGGED param of the catalogued
     * method is required on the wire — and asserts the caller supplies each
     * one. Run against the pre-fix caller, reconstructed exactly by
     * `$emptyArgs`, that derivation comes back SHORT for the three methods
     * that need params, and the check fails.
     *
     * Stated precisely, because the first attempt at this test asserted
     * something false: the ARGS do not decide whether a row places. A row
     * places from the RESPONSE payload, which the server shapes. So
     * "empty args place no rows" is untrue here, and asserting it would have
     * been a test of the double, not of the caller. What the empty array
     * actually breaks is the REQUEST: Telegram rejects a payload missing its
     * unflagged params, the command warns and skips, and no response is ever
     * fetched. So this test asserts the request-side truth — every required
     * param supplied — and the row test above asserts the response-side truth
     * separately. Two claims, two tests, neither pretending to be the other.
     *
     * @return array<string, list<string>> method => names it FAILED to supply
     */
    public function test_the_empty_argument_array_fails_this_very_suite(): void
    {
        $scope = $this->bind(emptyArgs: true);

        $this->artisan('teleframe:backfill', ['--account' => self::ACCOUNT, '--peer' => 'chat'])
            ->assertExitCode(0);

        // The empty array is what the old caller sent, for every method.
        self::assertSame(
            [],
            $scope->sent['messages.getSavedDialogs'] ?? null,
            'this suite drives the DEFECTIVE caller on purpose; [] is the pre-fix payload',
        );
        self::assertSame(
            [],
            $scope->sent['payments.getStarsStatus'] ?? null,
            'and for the method whose only required param is a peer',
        );

        // The catalog-derived requirement, applied to the defective caller.
        $short = $this->missingRequiredParams($scope->sent);

        self::assertSame(
            [
                'payments.getSavedStarGifts' => ['peer', 'offset', 'limit'],
                'payments.getStarsStatus' => ['peer'],
                'messages.getSavedDialogs' => ['offset_date', 'offset_id', 'offset_peer', 'limit', 'hash'],
            ],
            $short,
            'with [] the three param-requiring methods omit EVERY unflagged param — this is the defect, and it is what made them throw on a live account',
        );

        // The one method with no unflagged params is short on nothing, which
        // is the control: the check keys on the catalog, not on a method list.
        self::assertArrayNotHasKey(
            'account.getBusinessChatLinks',
            $short,
            'getBusinessChatLinks declares params: [] — the empty array is genuinely correct for it, and for it ALONE',
        );
    }

    /**
     * The catalog's own definition of "required": a param with NO flag word.
     * `flag_word: null` is exactly what makes it mandatory on the wire, so
     * this reads the truth from the file rather than restating a list that
     * could drift away from it.
     *
     * @param  array<string, array<string, mixed>>  $sent
     * @return array<string, list<string>>
     */
    private function missingRequiredParams(array $sent): array
    {
        $catalog = json_decode(
            (string) file_get_contents(__DIR__.'/../../src/Schema/schema/methods-mtproto.json'),
            true,
        );
        self::assertIsArray($catalog, 'the shipped method catalog must be readable JSON');
        self::assertIsArray($catalog['methods'] ?? null, 'the shipped method catalog must carry a `methods` map');

        $short = [];
        foreach (['payments.getSavedStarGifts', 'payments.getStarsStatus', 'account.getBusinessChatLinks', 'messages.getSavedDialogs'] as $method) {
            $entry = $catalog['methods'][$method] ?? null;
            self::assertIsArray($entry, sprintf('%s must be present in the shipped catalog', $method));

            $missing = [];
            foreach ($entry['params'] as $param) {
                if (($param['flag_word'] ?? null) === null && ! array_key_exists($param['name'], $sent[$method] ?? [])) {
                    $missing[] = $param['name'];
                }
            }

            if ($missing !== []) {
                $short[$method] = array_values($missing);
            }
        }

        return $short;
    }

    /**
     * The page size is an operator preference, not a derivation, so it reads
     * through `teleframe.backfill.collection_limit` — and a MISSING key is the
     * default, not an error. This pins both halves of that contract.
     */
    public function test_the_collection_limit_is_configurable_and_defaults_when_absent(): void
    {
        $this->app['config']->set('teleframe.backfill.collection_limit', 5);

        $scope = $this->bind();

        $this->artisan('teleframe:backfill', ['--account' => self::ACCOUNT, '--peer' => 'chat'])
            ->assertExitCode(0);

        self::assertSame(
            5,
            (int) ($scope->sent['messages.getSavedDialogs']['limit'] ?? 0),
            'an operator-narrowed limit must reach BOTH list methods, not just one',
        );
        self::assertSame(
            5,
            (int) ($scope->sent['payments.getSavedStarGifts']['limit'] ?? 0),
            'one limit, one constant, both list methods',
        );

        // Unset: falls back to the shipped default rather than sending 0 or
        // failing, because the config file is the operator's to add the key to.
        // A SECOND account, so the first run's rows cannot collide with this
        // one's — the mirror keys on (account_id, id) and a repeat would raise
        // a unique violation that has nothing to do with what is under test.
        $this->app['config']->set('teleframe.backfill.collection_limit', null);

        $second = $this->bind();
        $this->artisan('teleframe:backfill', ['--account' => self::ACCOUNT + 1, '--peer' => 'chat'])
            ->assertExitCode(0);

        self::assertSame(
            self::PAGE_LIMIT,
            (int) ($second->sent['messages.getSavedDialogs']['limit'] ?? 0),
            'an absent key is the default page size, not 0 and not a throw',
        );
    }

    /**
     * The cursor and ingester seams are container doubles so the crawl
     * contributes nothing; the four collection calls under test are NOT seams
     * and run for real through the policy-gated mirror route.
     *
     * @param  bool  $emptyArgs  drive the PRE-FIX caller (`$scope->call($method)`) instead of the fixed one
     */
    private function bind(bool $emptyArgs = false): CollectionArgsScope
    {
        $scope = new CollectionArgsScope($emptyArgs);

        $this->app->instance(BackfillCommand::SCOPE_RESOLVER_KEY, static fn (): UserAccountScope => $scope);
        $this->app->bind(
            BackfillCommand::INGESTER_KEY,
            static fn (): callable => static fn (int $accountId): callable => static fn (array $batch): array => ['ingested' => 0],
        );
        $this->app->bind(
            BackfillCommand::CURSOR_STORE_KEY,
            static fn (): callable => static fn (int $accountId): MemoryCursorStore => new MemoryCursorStore,
        );

        return $scope;
    }

    private function db(): \Illuminate\Database\Connection
    {
        return $this->capsule->getConnection();
    }
}
