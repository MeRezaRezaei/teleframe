<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Mirror;

use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Schema\SchemaArtifacts;
use MeRezaRezaei\Teleframe\Tests\Ingest\IngestTestCase;

/**
 * `tf_peers` — the polymorphic registry — is NOT POPULATED, so its foreign key
 * must not exist. This file is the gate that keeps it that way.
 *
 * ## THE VERDICT THIS FILE PINS, MEASURED NOT ASSUMED
 *
 * The plan calls `tf_peers` "the structural fix" for the polymorphic relations
 * and warns in one sentence why that is dangerous:
 *
 * > "71 tables need BOTH a registry row written by the ingest path AND the FK.
 * > **A registry that is never populated turns every existing join into an FK
 * > violation — the database stops accepting real Telegram data**, the opposite
 * > of 'insert without error'."
 *
 * MEASURED on this tree, SQLite, shipped DDL booted from
 * `UpdateIngestor::migrationPaths()`:
 *
 *  - `tf_peers` does not exist. `hasTable('tf_peers')` → false. There is no
 *    migration for it anywhere in `src/Laravel/Migrations/` and no occurrence of
 *    the name in `src/` outside a doc comment in `MirrorRelationPlan`, which
 *    itself records "`tf_peers` does not exist yet ... it is Task 4.3".
 *    A registry that does not exist has a population count of ZERO, so the
 *    plan's stop condition is met before any work begins.
 *  - No ingest path writes one. The population hook the plan names —
 *    "Populate it in `MirrorFactWriter` whenever a peer is resolved" — is not
 *    present in the tree.
 *
 * So the FK is NOT created, and this file is why that stays true. It is a gate,
 * not a pending test: every assertion below is GREEN on this tree, and each one
 * turns RED the moment someone adds the FK without the population. That is the
 * whole job — the catastrophe is prevented by a test that fails, not by a test
 * that is permanently red.
 *
 * ## THE COUNT IS NOT PINNED TO A HARD-CODED NUMBER
 *
 * The plan says 71 tables. The sibling arm re-derived the population from the
 * executed DDL and it is NOT 71: 8 polymorphic relations over 8 child tables
 * (`MirrorRelationPlan::polymorphic()`), and 67 tables carrying a
 * `{base}_type` + `{base}_id` sibling pair across 17 distinct bases. A pinned
 * 71 would be a transcription of a census that moves every time a table lands,
 * and a number that has to be edited to stay green is a number that will be
 * edited. What is asserted here is the RELATIONSHIP that decides the verdict,
 * and the sentinel fact below, which no count can stand in for.
 *
 * ## THE FINDING THAT DECIDES IT — AN ABSENT PEER IS STORED, NOT OMITTED
 *
 * This is the part that is not derivable from reading the plan, and it is why
 * the FK is unsafe even in principle as a "resolved peer" registry.
 *
 * 38 `{table}.{base}` sites declare `{base}_id BIGINT NOT NULL DEFAULT 0`. For
 * those, an OPTIONAL peer that Telegram did not send is not omitted from the
 * row — it is materialised as the pair `(type = 0, id = 0)`. The migration that
 * added the most recent of them says so in its own words: "`DEFAULT 0` is also
 * the correct PEER value, not a placeholder: the pair is absent whenever
 * `flags.1` is clear, and the writer leaves both halves at their default rather
 * than inventing a peer that Telegram did not send"
 * (`2026_10_04_000300_add_recent_repliers_to_tf_messages_replies.php`).
 *
 * So a registry populated with RESOLVED peers cannot satisfy
 * `(account_id, 0, 0)`. Adding the FK would reject a real forwarded message.
 * `test_real_ingest_stores_an_absent_optional_peer_as_type_zero` demonstrates
 * that with a real payload rather than by argument, and its control half
 * requires a genuinely-present peer in the SAME ingest to come through
 * non-zero — so a writer that stored nothing at all cannot pass it.
 */
final class PolymorphicRelationsResolveThroughTfPeersTest extends IngestTestCase
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
        return [31];
    }

    /** A `telegram_accounts.user_id` no real account holds. */
    private const ACCOUNT = 31;

    private const REGISTRY = 'tf_peers';

    /**
     * THE HEADLINE. The registry does not exist, therefore its population count
     * is zero, therefore the FK must not be created.
     *
     * Stated as an assertion on the MEASURED state rather than as a test that
     * fails because the state is unfavourable. A test asserting "the registry
     * is populated" would be red forever on a tree that correctly has no
     * registry; a test asserting "the registry is absent, and here is the
     * proof the dial really ran" is green now and red the instant someone adds
     * the table without the population.
     */
    public function test_the_registry_does_not_exist_and_therefore_must_not_be_constrained_to(): void
    {
        $db = DB::connection();

        self::assertFalse(
            $db->getSchemaBuilder()->hasTable(self::REGISTRY),
            self::REGISTRY.' now EXISTS in the shipped dial. That is not automatically a defect — a '
            .'registry WITH a proven population is the goal — but it means this file\'s verdict is no '
            .'longer measured, it is assumed. Before adding any foreign key that references it, prove '
            .'the population with a test that ingests a fixture and counts one row per distinct '
            .'(peer_type, peer_id), WITH the control half that empties the registry and re-runs the '
            .'real ingest path. Do not infer population from the table\'s existence.',
        );
    }

    /**
     * THE CONTROL HALF for the absence above.
     *
     * A database that never ran the dial also has no `tf_peers`, so "absent"
     * proves nothing unless the dial is shown to have executed. This requires
     * the peer-carrying tables to be really present and really empty, so the
     * absence above is a fact about the SCHEMA and not about an unmigrated
     * database.
     */
    public function test_the_dial_ran_so_the_absence_above_is_about_the_schema_and_not_an_empty_database(): void
    {
        $db = DB::connection();
        $listing = $this->tableNames();

        foreach (['tf_messages', 'tf_dialogs', 'tf_users', 'telegram_accounts'] as $table) {
            self::assertContains(
                $table,
                $listing,
                $table.' is missing, so the dial did not execute and '
                .'test_the_registry_does_not_exist_and_therefore_must_not_be_constrained_to is '
                .'proving nothing: an unmigrated database has no tf_peers either.',
            );
        }

        // tf_messages really does carry the peer pair, so the census this file
        // reasons about is present in this schema and not merely asserted.
        $columns = array_map(
            static fn (array $c): string => (string) $c['name'],
            $db->getSchemaBuilder()->getColumns('tf_messages'),
        );
        self::assertContains('peer_type', $columns, 'tf_messages no longer carries peer_type');
        self::assertContains('peer_id', $columns, 'tf_messages no longer carries peer_id');
    }

    /**
     * THE ANTI-ROT GATE — no `tf_*` table may carry a foreign key to a registry
     * that has no population.
     *
     * This is the assertion that would have caught the catastrophe, and it is
     * the one the plan's own anti-rot step cannot: that step asserts each
     * unrouted relation's excuse EXISTS, which is not the same as the excuse
     * being TRUE. A stale excuse plus a fresh FK is exactly the failure.
     *
     * The control half is that the scan really ran: the query is executed over
     * the live schema and the count of inspected tables is asserted non-zero,
     * so an empty result cannot be produced by a scan that looked at nothing.
     */
    public function test_no_mirror_table_carries_a_foreign_key_to_an_unpopulated_registry(): void
    {
        $db = DB::connection();

        $tables = [];
        foreach ($this->tableNames() as $table) {
            if (str_starts_with($table, 'tf_')) {
                $tables[] = $table;
            }
        }

        self::assertNotSame(
            [],
            $tables,
            'no tf_* table was found, so the constraint scan below inspected nothing and its empty '
            .'result is meaningless. The scan is the assertion; an unscanned schema proves nothing.',
        );

        $offenders = [];
        $observed = 0;
        foreach ($tables as $table) {
            foreach ($db->getSchemaBuilder()->getForeignKeys($table) as $foreign) {
                $observed++;
                $to = strtolower((string) ($foreign['foreign_table'] ?? ''));
                $name = strtolower((string) ($foreign['name'] ?? ''));
                if ($to === self::REGISTRY || str_contains($name, strtolower(self::REGISTRY))) {
                    $offenders[] = $table.'.'.($foreign['name'] ?? '?').' -> '.$to;
                }
            }
        }

        // THE CONTROL HALF THAT KEEPS THE GATE FROM BEING VACUOUS. A scan that
        // cannot SEE a single foreign key reports "no offenders" no matter what
        // the schema contains, so the gate would be green forever while
        // protecting nothing. The tenant FKs from
        // `2026_10_04_000700_add_tenant_foreign_keys_to_mirror_tables.php` are
        // known to exist on `tf_*` tables, so requiring the reader to observe
        // at least one constraint proves the reader works on THIS driver.
        // Without this, the empty `$offenders` above is not evidence.
        self::assertGreaterThan(
            0,
            $observed,
            'the foreign-key reader observed ZERO constraints across '.\count($tables).' tf_* tables, so '
            ."the \"no constraint points at {$this->registryState()}\" result above is VACUOUS — it would "
            .'be green no matter what the schema contained. The known-good tenant foreign keys from '
            .'2026_10_04_000700 must be visible here; if they are not, this driver is not reporting '
            .'constraints and this gate cannot do its job.',
        );

        sort($offenders);

        self::assertSame(
            [],
            $offenders,
            "A foreign key now points at {$this->registryState()}, which holds NO rows written by the "
            .'ingest path. Real Telegram rows are now rejected by a correct constraint over data that '
            .'was never written — the exact state the plan calls worse than having no registry at all. '
            .'Offending constraints: '.implode('; ', $offenders).'. Remove the FK, or prove the '
            .'population first with a green ingest test and its control half.',
        );
    }

    /**
     * THE SENTINEL FACT, demonstrated with a real payload.
     *
     * An ordinary forwarded message carries `fwd_from` but no `saved_from`
     * peer, so `tf_messages_fwd_from` stores `saved_from_id_type = 0`. A
     * registry of RESOLVED peers has no `(account_id, 0, 0)` row to point at,
     * so the FK would refuse this row. This is why the population cannot be
     * made safe by populating it: the row that needs a registry entry names no
     * peer at all.
     *
     * CONTROL HALF: the same ingest also carries a genuine `from_id` peer, and
     * it must land NON-ZERO in the same table. Without that half a writer that
     * stored nothing at all would pass, since "no rows" trivially contains no
     * zero rows.
     */
    public function test_real_ingest_stores_an_absent_optional_peer_as_type_zero(): void
    {
        $db = DB::connection();

        (new UpdateIngestor)->ingest([
            '_' => 'message',
            'id' => 2,
            'peer_id' => ['_' => 'peerChannel', 'channel_id' => -1001234567],
            'fwd_from' => [
                '_' => 'messageFwdHeader',
                'from_id' => ['_' => 'peerUser', 'user_id' => 501558149],
                'date' => 1726000001,
            ],
            'date' => 1726000002,
            'message' => 'forwarded, with no saved_from peer of its own',
        ], self::ACCOUNT);

        $has = static function (string $table, string $column) use ($db): bool {
            if (! $db->getSchemaBuilder()->hasTable($table)) {
                return false;
            }
            $columns = array_map(
                static fn (array $c): string => (string) $c['name'],
                $db->getSchemaBuilder()->getColumns($table),
            );

            return in_array($column, $columns, true);
        };

        // ---- CONTROL HALF: the genuine peer in the SAME ingest is non-zero. ----
        self::assertTrue(
            $has('tf_messages_fwd_from', 'from_id_type'),
            'tf_messages_fwd_from.from_id_type is absent, so the zero-row assertion below would '
            .'pass on a schema that stored nothing. The control half is what makes the finding '
            .'evidence rather than an artefact.',
        );
        self::assertGreaterThan(
            0,
            (int) $db->table('tf_messages_fwd_from')->where('from_id_type', '!=', 0)->count(),
            'the genuine fwd_from peer did NOT land with a non-zero type, so this ingest stored no '
            .'peer at all. A writer that stores nothing cannot support the zero-row finding below.',
        );

        // ---- THE FINDING: the absent optional peer is stored as type 0. ----
        $zeroColumn = null;
        foreach (['saved_from_id_type', 'saved_from_peer_type'] as $candidate) {
            if ($has('tf_messages_fwd_from', $candidate)
                && (int) $db->table('tf_messages_fwd_from')->where($candidate, 0)->count() > 0) {
                $zeroColumn = $candidate;
                break;
            }
        }

        self::assertNotNull(
            $zeroColumn,
            'Real ingest no longer stores an absent optional peer as type=0 in tf_messages_fwd_from. '
            .'That would mean the writer now OMITS the pair rather than defaulting it, which removes '
            .'the sentinel obstacle and re-opens the registry design. Re-derive before trusting this '
            .'file — this assertion is the load-bearing one and its failure is GOOD NEWS worth '
            .'chasing, not a regression to silence.',
        );

        $idColumn = substr((string) $zeroColumn, 0, -5).'_id';
        $zeroRow = $db->table('tf_messages_fwd_from')->where($zeroColumn, 0)->first();

        self::assertSame(
            0,
            (int) $zeroRow->{$idColumn},
            "the absent peer is stored as ({$zeroColumn} = 0, {$idColumn} = 0), NOT as a null. This is "
            .'the row a resolved-peer registry cannot satisfy, and it is why the foreign key must not '
            .'be created against a registry that holds only peers the ingest path actually resolved.',
        );
    }

    /**
     * `venue` is NOT a peer — it is a `GeoPoint` in TEXT columns, so a
     * `(account_id, peer_type, peer_id)` registry can never reference it.
     *
     * Recorded as an exclusion with its mechanical reason rather than left
     * implicit, so a later sweep that counts `{base}_type` + `{base}_id` pairs
     * discovers the TEXT type and leaves these three tables out instead of
     * writing a constraint the target engine refuses.
     */
    public function test_the_venue_pair_is_text_and_cannot_be_a_registry_leg(): void
    {
        $db = DB::connection();

        $carriers = [];
        foreach ($this->tableNames() as $table) {
            if (! $db->getSchemaBuilder()->hasTable($table)) {
                continue;
            }
            $columns = [];
            foreach ($db->getSchemaBuilder()->getColumns($table) as $column) {
                $columns[strtolower((string) $column['name'])] = $column;
            }
            if (isset($columns['venue_type'], $columns['venue_id'])) {
                $carriers[$table] = (string) $columns['venue_type']['type'];
            }
        }

        self::assertNotSame(
            [],
            $carriers,
            'no table carries a venue_type/venue_id pair any more. If venue moved to a real numeric '
            .'pair it may have become registry-able; re-derive the bases before trusting the exclusion.',
        );

        foreach ($carriers as $table => $type) {
            self::assertStringContainsStringIgnoringCase(
                'text',
                $type,
                "{$table}.venue_type is now {$type}, not TEXT. `venue` is a GeoPoint, not a Peer, so a "
                .'BIGINT peer registry cannot reference it. If the type changed, re-derive whether this '
                .'table is still excluded — and do not include it just because the count went up.',
            );
        }
    }

    /**
     * EVERY CONSTRUCTOR LANDS IN EXACTLY ONE TABLE, AND NO ARM CARRIES A DISCRIMINATOR.
     *
     * `tf_peers` keyed `(account_id, peer_type, peer_id)` needs a `(type, id)`
     * pair that is *attributable* from storage alone. Two things guarantee
     * that, and both are asserted here as data rather than as prose:
     *
     * 1. **Ctor exclusivity across the whole catalog.** `mirror-catalog.json`
     *    is the authority on which Telegram constructor lands in which table.
     *    If a constructor is claimed by TWO entries, every consumer reading the
     *    catalog has to guess, and — worse — a table can absorb arms belonging
     *    to different peer kinds (`peer_type` 2 vs 3) with no column to tell
     *    them apart. This scan covers EVERY entry and EVERY ctor, so the whole
     *    bug class is covered, not just the `Chat` union that motivated it.
     *    MEASURED on this tree: 39 entries, 180 distinct constructors, 0 ctor
     *    claimed by more than one entry. `Chat` is split by ctor family —
     *    `tf_chats = [chatEmpty, chat, chatForbidden]`,
     *    `tf_channels = [channel, channelForbidden]` — which is a storage
     *    translation, not a collision.
     * 2. **No arm carries a discriminator column.** `tf_users`, `tf_chats` and
     *    `tf_channels` are keyed `(account_id, id)`; none of them carries
     *    `peer_type` or `peer_id`, so a registry still cannot be read back out
     *    of the arms and remains a child-write-only attribution.
     *
     * The exclusivity scan is deliberately a WHOLE-CATALOG loop rather than
     * two hand-written per-table assertions: a hand-written pair would have
     * caught `Chat` and nothing else, which is exactly the regression shape
     * that let the original finding go stale.
     */
    public function test_the_arm_tables_carry_no_discriminator_so_the_registry_cannot_be_derived_from_them(): void
    {
        $db = DB::connection();

        foreach (['tf_users', 'tf_chats', 'tf_channels'] as $arm) {
            if (! $db->getSchemaBuilder()->hasTable($arm)) {
                continue;
            }

            $columns = [];
            foreach ($db->getSchemaBuilder()->getColumns($arm) as $column) {
                $columns[] = (string) $column['name'];
            }

            self::assertNotContains(
                'peer_type',
                $columns,
                "{$arm} carries a peer_type column, so a (account_id, peer_type, peer_id) registry "
                .'COULD be derived from it. This test asserts the opposite and would now be a false '
                .'alarm — re-measure the arm-side derivation before trusting this file.',
            );
            self::assertNotContains(
                'peer_id',
                $columns,
                "{$arm} carries a peer_id column, so a (account_id, peer_type, peer_id) registry "
                .'COULD be derived from it. See the peer_type assertion above.',
            );
        }

        // The catalog is the authority on which constructor lands where, and it
        // is read as data rather than asserted by hand.
        $catalogPath = SchemaArtifacts::path('mirror-catalog.json');
        self::assertFileIsReadable($catalogPath, 'mirror-catalog.json is not readable at '.$catalogPath);
        $catalog = json_decode((string) file_get_contents($catalogPath), true);
        self::assertIsArray($catalog, 'mirror-catalog.json did not decode to an array');

        self::assertSame(
            [],
            self::ctorCollisions($catalog),
            'Every constructor must be claimed by EXACTLY ONE catalog entry. A ctor claimed by two '
            .'entries means the table-to-constructor mapping is ambiguous, and a table absorbing arms '
            .'of different peer kinds has no column to attribute them. Offenders are listed as '
            .'`<ctor> -> <tables>`, sorted. Split the ctor families apart as the catalog already does '
            .'for `Chat` (tf_chats / tf_channels), or re-derive whether the registry is underivable.',
        );

        // The real split, asserted as the positive half of the scan: if the
        // scan above ever passes, these two lists are what it passed ON, so
        // they are named here rather than left implicit.
        self::assertSame(
            ['chat', 'chatEmpty', 'chatForbidden'],
            self::normalizedCtors($catalog, 'tf_chats'),
            'tf_chats must own exactly the chat-only constructor family. Re-measure the split.',
        );
        self::assertSame(
            ['channel', 'channelForbidden'],
            self::normalizedCtors($catalog, 'tf_channels'),
            'tf_channels must own exactly the channel-only constructor family. Re-measure the split.',
        );
    }

    /**
     * Every ctor in the catalog, mapped to the DISTINCT entries that claim it.
     *
     * Distinct, not counted: `tf_updates` lists `updateChannelTooLong` twice
     * inside its OWN ctor list, and a repeated name in one entry is not a
     * collision — only a name claimed by two different tables is.
     *
     * @param  array<string, mixed>  $catalog
     * @return array<string, list<string>>
     */
    private static function ctorClaims(array $catalog): array
    {
        $claims = [];
        foreach ($catalog as $table => $entry) {
            if (! is_array($entry)) {
                continue;
            }
            foreach ((array) ($entry['ctors'] ?? []) as $ctor) {
                if (! is_string($ctor) || $ctor === '') {
                    continue;
                }
                $claims[$ctor][(string) $table] = true;
            }
        }

        return array_map(
            static fn (array $tables): array => array_keys($tables),
            $claims,
        );
    }

    /**
     * The offenders, sorted, rendered for the failure message.
     *
     * Kept as its own method so the negative control can exercise the exact
     * same logic outside PHPUnit — see the scratch-copy run in the plan.
     *
     * @param  array<string, mixed>  $catalog
     * @return list<string>
     */
    private static function ctorCollisions(array $catalog): array
    {
        $offenders = [];
        foreach (self::ctorClaims($catalog) as $ctor => $tables) {
            if (count($tables) > 1) {
                sort($tables);
                $offenders[] = $ctor.' -> '.implode(', ', $tables);
            }
        }
        sort($offenders);

        return $offenders;
    }

    /**
     * One entry's ctor list, de-duplicated and sorted.
     *
     * @param  array<string, mixed>  $catalog
     * @return list<string>
     */
    private static function normalizedCtors(array $catalog, string $table): array
    {
        $entry = is_array($catalog[$table] ?? null) ? $catalog[$table] : [];
        $ctors = array_values(array_unique(array_filter(
            array_map(
                static fn ($ctor): string => is_string($ctor) ? $ctor : '',
                (array) ($entry['ctors'] ?? []),
            ),
            static fn (string $ctor): bool => $ctor !== '',
        )));
        sort($ctors);

        return $ctors;
    }

    /**
     * The migrated table names, bare.
     *
     * `getTableListing()` qualifies its results on SQLite (`main.tf_messages`)
     * and applies the connection's table prefix, so the listing is normalised
     * the same way the sibling family-FK tests normalise it rather than
     * compared raw — otherwise every assertion below fails on a QUALIFIED name
     * and the scan silently inspects nothing.
     *
     * @return list<string>
     */
    private function tableNames(): array
    {
        $db = DB::connection();
        $prefix = (string) $db->getTablePrefix();

        $names = [];
        foreach ($db->getSchemaBuilder()->getTableListing() as $listed) {
            $bare = str_contains($listed, '.')
                ? substr($listed, (int) strrpos($listed, '.') + 1)
                : $listed;
            if ($prefix !== '' && str_starts_with($bare, $prefix)) {
                $bare = substr($bare, strlen($prefix));
            }
            $names[] = $bare;
        }
        sort($names);

        return $names;
    }

    /** The measured registry state, quoted into failure messages so they are self-explaining. */
    private function registryState(): string
    {
        $exists = DB::connection()->getSchemaBuilder()->hasTable(self::REGISTRY);

        if (! $exists) {
            return self::REGISTRY.' (a table that does not exist)';
        }

        $rows = DB::connection()->table(self::REGISTRY)->count();

        return self::REGISTRY." (a table holding {$rows} row(s) written by nothing in the ingest path)";
    }
}
