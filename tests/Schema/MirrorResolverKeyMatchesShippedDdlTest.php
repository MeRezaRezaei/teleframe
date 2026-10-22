<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use Illuminate\Container\Container;
use Illuminate\Database\Connection;
use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlScheme;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactDecomposer;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactWriter;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorNestedSourceWalker;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorTable;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorTableEntry;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorTableResolver;
use MeRezaRezaei\Teleframe\Tests\Store\ShippedStoreSchema;
use PHPUnit\Framework\TestCase;

/**
 * Cycle 31 gate: the key MirrorTableResolver writes must be the key the
 * shipped DDL declares.
 *
 * `MirrorCatalogShipsInMigrationsTest` (added 2026-09-29, commit 380aff82)
 * asserts the catalog and the shipped set agree on table NAMES. Names are not
 * enough: `MirrorIngesterFactory` resolves a decoded constructor to a table,
 * `MirrorFactDecomposer` fills that table's `keyColumns` from the payload, and
 * `MirrorFactWriter` inserts the row. If the resolved key is not the shipped
 * PRIMARY KEY the row cannot place — and nothing in the suite compared the
 * two, so the divergence was invisible.
 *
 * The finding that motivated this test named four tables. Ground truth, read
 * from the resolver, the shipped `Schema::create` blocks and the models, is
 * that only ONE of the four was a key-NAMING divergence. The other three are
 * worse and are not fixable by declaring a key — see
 * `test_three_tables_ship_a_designer_key_the_payload_never_carries`, which
 * pins them as a named, asserted, still-open class rather than a green suite
 * bought by widening scope.
 */
final class MirrorResolverKeyMatchesShippedDdlTest extends TestCase
{
    /** Repo root — this suite is filesystem-only, no Laravel boot needed. */
    private const PACKAGE_ROOT = __DIR__.'/../..';

    private const CATALOG = self::PACKAGE_ROOT.'/src/Schema/schema/mirror-catalog.json';

    private const SHIP_DIR = self::PACKAGE_ROOT.'/src/Laravel/Migrations';

    /**
     * Tables whose shipped PK is a CONTEXT key: an identity that exists only
     * in the ENCLOSING payload, never in the constructor the row is built
     * from. `tf_channel_participants` ships
     * `primary(['account_id','channel_id','user_id'])` but the resolver writes
     * `(account_id, user_id)` — `channel_id` is reachable only through
     * `channels.channelParticipants`'s sibling `chats` vector (TL 782). Dropping
     * it from the PK would be data-losing (the same user is a participant in
     * many channels), so this one needs context propagation through the write
     * path, not a key rename. See
     * `test_channel_participants_channel_id_is_an_open_context_gap` for the
     * assertion that keeps it from rotting silently.
     *
     * `tf_bot_infos` and `tf_saved_star_gifts` were in this list until
     * 2026-09-29: both shipped a PK on `id`, a column their constructors do
     * not carry. They are now reconciled by an expressed key derivation
     * (`keyFrom`) and are covered by the general assertion above.
     *
     * @var list<string>
     */
    private const CONTEXT_KEY_TABLES = [
        'tf_channel_participants',
    ];

    /**
     * Tables whose shipped primary key names a coordinate the decomposed row
     * CANNOT carry, so their facts degrade to a per-row keying clue instead of
     * placing. Measured against the shipped catalog on 2026-10-02 and asserted
     * as a CLOSED set by
     * `test_the_set_of_tables_whose_shipped_key_a_row_cannot_cover_is_exactly_the_measured_allow_list`.
     *
     * Every entry is now ONE shape: a union store whose shipped PK names a
     * synthetic id the DDL has and no constructor carries —
     * `tf_message_entities` (`message_entity_id`), `tf_reactions`
     * (`reaction_id`), `tf_saved_reaction_tags` (`saved_reaction_tag_id`),
     * `tf_todo_lists` (`todo_list_id`). FOUR entries as of 2026-10-04.
     *
     * `tf_dialogs` and `tf_saved_dialogs` were in this list until 2026-10-02
     * and were WRONG. They are the two peer-keyed tables, and a peer key is
     * two columns (`peer_type` + `peer_id`) filled from ONE payload field
     * (`peer`) by `MirrorFactDecomposer::fillPeerHalf()`, which the oracle
     * below did not model — so the two healthy tables were pinned as broken.
     * Measured against the shipped migration and the real decomposer, both
     * place with zero write clues on a payload carrying a real peer. They are
     * gone because they are now correctly seen as FILLABLE, which is right for
     * the oracle; it does NOT mean the peer-key class is free of hazards. See
     * the DDL-default warning immediately below.
     *
     * `tf_message_actions` was in this list until 2026-10-04 and was WRONG for
     * a THIRD reason, the same class as the peer pair: see the
     * `UNPLACEABLE_KEY_TABLES` docblock, which carries the measurement.
     *
     * ## WHY A DDL DEFAULT IS NOT PROTECTION — `tf_dialogs` was nearly one
     *
     * These FOUR declare their synthetic key `NOT NULL` with NO default, so a
     * bare insert fails loudly: the fact is dropped and a clue is reported.
     * That is bad but HONEST. `tf_dialogs` declared `peer_type`/`peer_id` with
     * `->default(0)` instead
     * (`src/Laravel/Migrations/2026_09_14_200004_create_tf_dialogs_tables.php`
     * lines 53-54), and a bare insert therefore SUCCEEDS at
     * `(account_id, 0, 0)` — measured, see
     * `test_a_peer_keyed_table_with_a_ddl_default_would_corrupt_silently_not_drop_loudly`.
     * The allow-list does not describe that corruption surface, and it is not
     * meant to: it describes tables that never PLACE. A table whose key column
     * carries a DDL default is not protected by the NOT NULL drop, so any
     * future regression in a peer-keyed or default-keyed table surfaces as
     * silent 0/0 rows rather than a clue.
     *
     * The fix for that hazard is at the source, in `placeRow()`, which another
     * lane owns — NOT a runtime guard here, and not an extra allow-list entry
     * (a correctly-placing table does not belong in a set of tables that
     * cannot place). No guard is added for it in this file on purpose.
     *
     * Distinct from `CONTEXT_KEY_TABLES`: there the key column IS a real
     * base/ctor column and only the ENCLOSING context is missing, so the
     * oracle reads it as fillable. Here the column is absent outright.
     *
     * @var array<string, list<string>>
     */
    /**
     * Tables whose shipped primary key a decomposed row cannot cover, with the
     * column that is unreachable.
     *
     * Each entry is a TABLE WHOSE FACTS DEGRADE TO KEYING CLUES AND NEVER
     * PLACE, and the list is asserted for EQUALITY in both directions: a new
     * name fails the test as loudly as a missing one. FOUR entries as of
     * 2026-10-04.
     *
     * `tf_message_medias` left this list on 2026-10-03: `message_media_id` is
     * not fillable from `messageMedia` itself — but it is not a fabricated
     * column either. It is supplied by the `sources.context:
     * ["id","peer_id"]` overlay, because a `message` carries at most ONE
     * `media` (`message#7600b9d3 … media:flags.9?MessageMedia`,
     * `api_full.tl:121`), so the enclosing message's id IS the media row's
     * identity. Placement is proven end to end in
     * `tests/Ingest/MessageMediaReachesTheWriterTest`.
     *
     * CORRECTED 2026-10-04, and the earlier text here asserted "no
     * `MessageMedia` constructor carries an `id`". That was FALSE, and the file
     * range it cited refuted it in the same file. Exactly one DOES — of the
     * 19 constructors at `api_full.tl:124-142`, only
     * `messageMediaStory#68cb6283 … peer:Peer id:int story:flags.0?StoryItem`
     * (`api_full.tl:137`) carries a bare `id:int`. It changes nothing here,
     * because that `id` is a STORY id: the stories namespace, keyed `id` on
     * `tf_story_items`, carried by 1 of 19, so it is neither a union-wide key
     * nor a valid `message_media_id`. The other id-bearing params are
     * distinctly named — `user_id`, `venue_id`, `receipt_msg_id`, `channel_id`,
     * `launch_msg_id` — and are not `id`. The shipped DDL reached that verdict
     * independently and DROPPED `messageMediaStory.id` as a plain-name
     * collision with the parent FK column `id`
     * (`2026_09_14_200012_create_tf_message_medias_tables.php:18-19`).
     *
     * ## `tf_message_actions` LEFT THIS LIST ON 2026-10-04 — THE ORACLE WAS
     * ## BLIND, THE CATALOGUE WAS NOT
     *
     * This entry said the `keyFrom` + `sources` for `messageService.action`
     * "were written, load-validated, and then REVERTED". They were NOT
     * reverted. The shipped `src/Schema/schema/mirror-catalog.json` declares
     * both, verbatim:
     *
     *     "key":     ["message_action_id"]
     *     "keyFrom": {"message_action_id": "id"}
     *     "sources": [{"ctor":"messageService","field":"action",
     *                 "context":["id"]}]
     *
     * and the row PLACES. MEASURED through the real walker, decomposer and
     * writer against the shipped migration — a `messageService` (id 5000111,
     * `peer_id` `peerChat 1232026269`) carrying `messageActionChatEditTitle`:
     * the walk yields a `tf_message_actions` payload keyed `["id","_","title"]`,
     * the decomposer emits
     * `{"account_id":7,"constructor":"messageActionChatEditTitle",
     * "message_action_id":5000111,"title":"new group title"}` with clues `[]`,
     * and `MirrorFactWriter` returns `inserted=1`, `fkClues=[]`. The docblock
     * predicted the fix precisely — "it becomes placeable when `isFillable()`
     * learns about a context-supplied key AND a placement test proves the row
     * lands, both, not one" — and both now hold: `isContextSupplied()` is the
     * first half and
     * `test_a_context_overlaid_key_places_with_no_clue_at_all()` is the second.
     *
     * The sub-claim that carried the old entry is TRUE and is why the oracle
     * could not see it: none of the 67 `MessageAction` constructors
     * (`api_full.tl:144-210`) carries a bare `id:int`. Only the overlay
     * supplies `message_action_id`, and an oracle modelled on constructors
     * alone reads a placing table as unplaceable — which is precisely the
     * "declaring an edge nothing can fill is a lie" hazard the allow-list
     * exists to prevent, pointing the wrong way.
     *
     * ## MEASURED DEFECT THIS EXPOSES — NOT in scope here, and NOT an entry
     *
     * The edge places, but it places AT A COLLIDING KEY, the same defect
     * 737d095d just fixed for `tf_message_medias`. `message#7600b9d3` carries
     * `id:int` AND `peer_id:Peer` and Telegram numbers `id` PER CHAT, while
     * the shipped PK is still `primary(['account_id','message_action_id'])`
     * (`2026_09_29_000100_create_tf_mirror_gap_tables.php:208`) — no
     * `peer_type`/`peer_id`, no `scoped_by`, and the overlay is
     * `context: ["id"]` alone. MEASURED: writing the SAME message id 5000111
     * for a second `messageService` in a DIFFERENT dialog (`peerUser 4242`)
     * returned `inserted=1` and `fkClues=[]` while the table still held ONE
     * row, whose `title` had been REPLACED with the other dialog's. Two
     * distinct actions silently overwrite each other, reporting success.
     *
     * It is recorded here, not fixed here, and deliberately NOT added to this
     * list: this list describes tables that never PLACE, and this one places
     * (see the `tf_dialogs` paragraph above — a hazard the list is not meant to
     * describe). The fix is the one 737d095d applied to `tf_message_medias`:
     * widen the overlay to `context: ["id","peer_id"]`, declare
     * `scoped_by: ["peer_type","peer_id"]`, and widen the PK — which is a
     * migration in a file this lane does not own.
     *
     * What remains are tables whose key names a column NO payload and NO
     * overlay can supply: `MessageEntity`, `Reaction`, `SavedReactionTag` and
     * `TodoList` constructors carry no id of their own, and a correct key for
     * them is `(message identity, position)` — a real design step, not a
     * catalog annotation, and the reason this list is not empty by fiat.
     */
    private const UNPLACEABLE_KEY_TABLES = [
        // `tf_message_entities` WAS HERE and was REMOVED on 2026-10-05.
        //
        // Its table is gone — da926d87 deleted `tf_message_entities` after
        // measuring that nothing ever wrote it: over ten decoded payload shapes
        // the shipped MirrorCatalog + MirrorFactDecomposer emit 0 rows into it
        // and 18 into its sibling, and 2026_09_29_000100's docblock records the
        // retirement. So the entry described a table that no longer exists, and
        // this gate correctly reported the drift.
        //
        // It is left as a COMMENT rather than silently deleted, because a
        // one-line removal from an allow-list is exactly the kind of edit that
        // hides a real regression: if a future migration ever recreates
        // `tf_message_entities`, the allow-list must be re-extended deliberately,
        // and this is where the next person will look. The three remaining
        // entries are unchanged and still measured.
        //
        // 'tf_message_entities' => ['message_entity_id'],   <- retired, table deleted
        'tf_reactions' => ['reaction_id'],
        'tf_saved_reaction_tags' => ['saved_reaction_tag_id'],
        'tf_todo_lists' => ['todo_list_id'],
    ];

    /**
     * Tables whose RESOLVER key is correct and whose SHIPPED DDL is not yet —
     * a named, dated divergence between two artifacts this plan fixes in
     * sequence.
     *
     * ## MEASURED EMPTY, 2026-10-03 (Task 2.1 Round 1)
     *
     * This set held ONE entry, `tf_stars_subscriptions`, after Task 2.1 added
     * `scoped_by: ["peer_type","peer_id"]` to it. That catalog edit was a
     * REGRESSION and is REVERTED, so the divergence is gone and the set is
     * empty again. The mechanism is deliberately KEPT rather than deleted: the
     * useful property is not "zero divergence today" but that a divergence
     * cannot appear unremarked. A NEW name means some entry started resolving a
     * key its shipped primary() does not carry — the exact failure Task 2.1
     * round 1 measured on `tf_stars_subscriptions`: declaring its scope widened
     * the key of all FIVE tables `MirrorTableResolver` resolves beneath it (four
     * `children` entries plus the BASE field `pricing`, whose `FK→` shape
     * decomposes into a table as well), and each emitted both "column 'peer_id'
     * is not in the shipped DDL — field not modelled, skipped" and the same for
     * `peer_type` — TEN clues, two per table. A MISSING name is not
     * possible while the set is empty; a Task 2.2 that widens a parent key and
     * its children in one migration adds nothing here, which is the point.
     *
     * WHY THE DECLARATION WAS REVERTED RATHER THAN LANDED. `tf_stars_subscriptions`
     * is peer-scoped for real (`starsSubscription.peer` is a whole nested
     * `Peer`, so `id` alone is not an identity across peers), but `makeChildPkCols()`
     * copies a parent's key onto every child, so declaring the scope widened the
     * PRIMARY KEY of all five tables resolved beneath it at once — columns that
     * live in migrations this lane does not own. Task 2.2's Phase A adds them to
     * the children FIRST, then widens the parent "in one migration".
     *
     * `tf_messages` must not be added here either, for the same reason with 30
     * children. `MirrorCatalog`'s gate exempts entries whose key is inherited by
     * children (see `assertScopeDeclared()`), so `tf_messages` still resolves to
     * the bare `id` and stays CONSISTENT with its shipped DDL — which is why it
     * belongs in neither this set nor the exempt-set pin in
     * `MirrorScopedKeyDeclarationTest`.
     *
     * Distinct from `CONTEXT_KEY_TABLES` (key fillable, PK needs context
     * propagation through the write path) and from
     * `UNPLACEABLE_KEY_TABLES` (key column absent from the payload): here the
     * resolver emits a key the payload DOES carry, and only the migration lags.
     *
     * @var list<string>
     */
    private const SCOPE_AWAITING_MIGRATION = [];

    public function test_every_catalog_table_ships_the_key_the_resolver_writes(): void
    {
        [$catalog, $resolver] = $this->pipeline();
        $shipped = $this->shippedPrimaryKeys();

        $mismatched = [];
        foreach ($resolver->resolveAll($catalog->tableNames()) as $table) {
            if (in_array($table->tfName, self::CONTEXT_KEY_TABLES, true)) {
                continue;
            }

            // See SCOPE_AWAITING_MIGRATION. The set is EMPTY today: every
            // `scoped_by` the shipped catalog declares is now honoured by the
            // migration that declares the table (`tf_message_medias` and
            // `tf_message_actions` are the two, both fixed 2026-10-04), so this
            // skip currently matches nothing and the comparison below is
            // unconditional. That is the honest state, not an omission. A name
            // belongs here ONLY while the ENCLOSING-scope declaration is correct
            // on the resolver side while the migration lags: a table's
            // children's columns and PRIMARY KEYS are Task 2.2's to add,
            // atomically with the parent that `makeChildPkCols()` would widen.
            // Naming the table is what keeps that divergence VISIBLE instead of
            // silent — an unnamed divergence would just be a red test and a
            // revert. The set is asserted in full below so it cannot quietly
            // grow.
            if (in_array($table->tfName, self::SCOPE_AWAITING_MIGRATION, true)) {
                continue;
            }

            $expected = $this->expectedKey($table);
            $actual = $shipped[$table->tfName] ?? null;

            if ($actual !== $expected) {
                $mismatched[$table->tfName] = ['resolver' => $expected, 'shipped' => $actual];
            }
        }

        self::assertSame(
            [],
            $mismatched,
            'catalog table(s) ship a primary key that is not the one MirrorTableResolver writes: '
            .json_encode($mismatched),
        );
    }

    /**
     * The counterpart to the skip above: the exempted tables must be exempted
     * for the MEASURED reason and no other. The set is EMPTY as of Task 2.1
     * round 1 (the one entry was reverted as a regression), so today this test
     * asserts the stronger thing — that no table's resolver key disagrees with
     * its shipped primary() — while keeping the escape hatch alive. A NEW name
     * here means a catalog edit widened a key past the DDL, which is exactly
     * how the `tf_stars_subscriptions` regression announced itself; the message
     * says to name the table rather than delete the constant, because deleting
     * it would turn the skip into a permanent blind spot.
     */
    public function test_the_scope_awaiting_migration_set_is_exactly_the_measured_one(): void
    {
        [$catalog, $resolver] = $this->pipeline();
        $shipped = $this->shippedPrimaryKeys();

        $awaiting = [];
        foreach ($resolver->resolveAll($catalog->tableNames()) as $table) {
            if (in_array($table->tfName, self::CONTEXT_KEY_TABLES, true)) {
                continue;
            }
            if ($this->expectedKey($table) === ($shipped[$table->tfName] ?? null)) {
                continue;
            }
            $awaiting[] = $table->tfName;
        }
        sort($awaiting);

        $expected = self::SCOPE_AWAITING_MIGRATION;
        sort($expected);

        self::assertSame(
            $expected,
            $awaiting,
            'the set of tables whose resolver key disagrees with its shipped primary() has changed. '
            .'A NEW entry means a catalog edit widened a key past the DDL — measured 2026-10-03, '
            ."declaring scoped_by on 'tf_stars_subscriptions' made all FIVE of the tables resolved "
            ."beneath it emit 'column not in the shipped DDL' clues (ten clues, two per table), "
            .'because makeChildPkCols() inherits the parent key. '
            .'Name it here only if the divergence is intentional and Task 2.2 owns the migration.',
        );
    }

    /**
     * A resolver key the payload can never fill is not a naming problem, and no
     * `key` declaration can fix it: `MirrorFactDecomposer` emits a
     * "keying column missing from payload" clue and the row never places, which
     * is the same outcome as a key/PK mismatch but for a different reason.
     *
     * This is the class the expressed key derivation (`keyFrom`, 2026-09-29)
     * was added to EMPTY. It stays as a guard, not a to-do list: a future
     * catalog entry whose key the payload cannot supply — by no derivation, by
     * base column, or by constructor parameter — fails here.
     */
    public function test_no_table_ships_a_key_the_payload_can_never_fill(): void
    {
        [$catalog, $resolver, $scheme] = $this->pipeline(withScheme: true);

        $unfillable = [];
        foreach ($catalog->tableNames() as $tfName) {
            $entry = $catalog->table($tfName);
            $table = $resolver->resolveAll([$tfName])[0];

            foreach ($table->keyColumns as $col) {
                if ($this->isFillable($entry, $table, $scheme, $col)) {
                    continue;
                }
                // Context-free union stores keyed by a synthetic id are the
                // designed behaviour of the curated dial, not this defect.
                if (! in_array($tfName, self::CONTEXT_KEY_TABLES, true)) {
                    continue;
                }
                $unfillable[$tfName][] = $col;
            }
        }

        ksort($unfillable);

        self::assertSame(
            [],
            $unfillable,
            'the set of tables whose resolver key the payload can never fill has grown — '
            .'a key needs an expressed derivation (keyFrom) or a real base column / ctor parameter',
        );
    }

    /**
     * THE STATIC GATE for the defect class a runtime guard cannot catch.
     *
     * The claim "the shipped DDL under-declares a table's identity" has two
     * independent halves, and only ONE of them is per-row:
     *
     *   - "the shipped `primary([...])` is not the key the resolver writes" is
     *     STATIC — catalog versus migration. Asserted by
     *     `test_every_catalog_table_ships_the_key_the_resolver_writes`.
     *   - "the row the decomposer builds cannot cover that key" is a fact
     *     about the CATALOG entry's key derivation, and it is also static.
     *
     * Someone once tried to assert the second half inside
     * `MirrorFactWriter::placeRow()`, per row, at runtime. It was wrong for two
     * measured reasons: it false-fired on `tf_bot_infos` and
     * `tf_saved_star_gifts` (whose `id` arrives via an expressed `keyFrom`
     * derivation, so a row legitimately lacks `id` under its own name), and it
     * threw a non-`QueryException` from inside `write()`'s loop, aborting the
     * whole batch instead of degrading to a per-row clue. The runtime
     * degradation contract is pinned in
     * `tests/Ingest/MirrorFactWriterUnplaceableKeyDegradesTest.php`; this is
     * where the STATIC half lives.
     *
     * So the set below is asserted EQUAL to a closed, measured allow-list.
     * That direction is the point: it fails when a table JOINS the set (a new
     * unfillable key is a regression) as well as when one leaves it (a fixed
     * table must be re-classified rather than silently forgotten).
     *
     * The list is measured, not guessed. Each entry is a union store whose
     * shipped PK names a synthetic id (`message_action_id`, `reaction_id`, …)
     * that the constructor does not carry. At runtime these degrade gracefully —
     * the decomposer emits a `keying column ... missing from payload` clue and
     * the row does not place. That is the documented behaviour of the curated
     * dial, NOT a silent drop, and each is a real open gap rather than a
     * naming fix.
     *
     * This set describes tables that never PLACE. It is not a register of every
     * key-hazard in the catalog — a table whose key column carries a DDL default
     * can be inserted with a 0/0 key and corrupt silently while still placing,
     * and no membership here says anything about that. The default-key hazard
     * is recorded on the constant itself.
     *
     * @see self::CONTEXT_KEY_TABLES for the separate class whose key IS
     *       fillable by name but whose PK needs context propagation.
     */
    public function test_the_set_of_tables_whose_shipped_key_a_row_cannot_cover_is_exactly_the_measured_allow_list(): void
    {
        [$catalog, $resolver, $scheme] = $this->pipeline(withScheme: true);

        $unfillable = [];
        foreach ($catalog->tableNames() as $tfName) {
            $entry = $catalog->table($tfName);
            $table = $resolver->resolveAll([$tfName])[0];

            foreach ($table->keyColumns as $col) {
                if ($this->isFillable($entry, $table, $scheme, $col)) {
                    continue;
                }
                $unfillable[$tfName][] = $col;
            }
        }
        ksort($unfillable);

        $allowList = self::UNPLACEABLE_KEY_TABLES;
        ksort($allowList);

        self::assertSame(
            $allowList,
            $unfillable,
            'the set of catalog tables whose shipped primary key a decomposed row cannot cover '
            .'has changed: a NEW entry means a table whose facts degrade to keying clues and never '
            .'place (add an expressed keyFrom derivation, or widen the allow-list with a reason); a '
            .'MISSING entry means a table was fixed and should be re-classified rather than left '
            .'floating in an allow-list that no longer describes reality.',
        );
    }

    /**
     * The regression that motivated the `key` field: `tf_business_chat_links`
     * leads its `base` with `link`, a real TL field (`businessChatLink.link`)
     * and the shipped PRIMARY KEY, but `deriveKeyColumns()` only recognised
     * `id` / `peer` / `*_id` by NAME, so it fabricated `business_chat_link_id`
     * — a column the payload does not carry and the table does not have.
     *
     * Asserted end to end through the real write path, not just the resolver:
     * a decomposed businessChatLink payload must place a row keyed on `link`
     * and emit no keying clue.
     */
    public function test_business_chat_link_places_on_the_link_the_ddl_declares(): void
    {
        [$catalog, $resolver] = $this->pipeline();

        $table = $resolver->resolveAll(['tf_business_chat_links'])[0];
        self::assertSame(['link'], $table->keyColumns, 'resolver must key business chat links on `link`');

        $fact = (new MirrorFactDecomposer($resolver, $catalog))->decompose(
            'tf_business_chat_links',
            1,
            ['_' => 'businessChatLink', 'link' => 'https://t.me/+abc', 'message' => 'hi', 'views' => 3],
        );

        $row = null;
        foreach ($fact['rows'] as $r) {
            if ($r['table'] === 'tf_business_chat_links') {
                $row = $r['row'];
            }
        }

        self::assertIsArray($row, 'no tf_business_chat_links row was decomposed');
        self::assertSame('https://t.me/+abc', $row['link'] ?? null, 'row must carry the Telegram-supplied link as its key');
        self::assertArrayNotHasKey('business_chat_link_id', $row, 'the fabricated key must not be written');
        self::assertSame([], $fact['clues'], 'a business chat link must place without a keying clue');
    }

    /**
     * A declared `key` naming something the payload cannot carry is a catalog
     * error, and it must fail at LOAD rather than silently dropping rows at
     * ingest. This is the guard that stops the `key` field from being used to
     * paper over a designer key.
     */
    public function test_a_declared_key_the_payload_cannot_carry_is_rejected_at_load(): void
    {
        $scheme = TlParser::parseFile(self::PACKAGE_ROOT.'/schema/sources/TL_telegram_v227.tl');

        $this->expectExceptionMessage("explicit key 'link' is neither a base column nor a parameter");

        MirrorCatalog::fromEntries([
            'tf_business_chat_links' => [
                'tl' => 'BusinessChatLink',
                'ctors' => ['businessChatLink'],
                'key' => ['link'],
                // `link` deliberately absent from base: the ctor parameter is
                // checked too, so this asserts the base-column half of the guard.
                'base' => [['views', 'INTEGER NOT NULL']],
                'bools' => [],
                'children' => [],
            ],
        ]);
    }

    /**
     * The regression that motivated the peer branch in `isFillable()`: the two
     * peer-keyed tables were pinned in `UNPLACEABLE_KEY_TABLES` as broken when
     * they place perfectly. This asserts it end to end — real decomposer, real
     * writer, real shipped migration — so a future oracle that forgets the peer
     * pair again fails HERE first, with a symptom a reader can believe.
     *
     * A peer key is two columns from one payload field, so the assertion is on
     * BOTH halves landing with the wire's own peer: `peer_type` is the
     * canonical enum (1=user, 2=chat, 3=channel) and `peer_id` the id Telegram
     * supplied. Zero clues, zero write clues.
     */
    public function test_a_peer_keyed_table_places_its_whole_key_pair_with_no_clue(): void
    {
        $db = $this->shippedSchema(
            self::PACKAGE_ROOT.'/src/Laravel/Migrations/2026_09_14_200004_create_tf_dialogs_tables.php',
        );
        [$catalog, $resolver] = $this->pipeline();
        $decomposer = new MirrorFactDecomposer($resolver, $catalog);

        // TL 852 `dialog` — the peer arrives whole, as `peer`.
        $fact = $decomposer->decompose('tf_dialogs', 1, [
            '_' => 'dialog',
            'peer' => ['_' => 'peerUser', 'user_id' => 777],
            'top_message' => 0,
            'read_inbox_max_id' => 0,
            'read_outbox_max_id' => 0,
            'unread_count' => 0,
            'unread_mentions_count' => 0,
            'unread_reactions_count' => 0,
            'unread_poll_votes_count' => 0,
        ]);

        $row = null;
        foreach ($fact['rows'] as $r) {
            if ($r['table'] === 'tf_dialogs') {
                $row = $r['row'];
            }
        }

        self::assertIsArray($row, 'no tf_dialogs row was decomposed');
        self::assertSame([], $fact['clues'], 'a dialog carrying a real peer must place with no clue at all');
        self::assertSame(1, (int) ($row['peer_type'] ?? 0), 'peer_type must carry the canonical user enum (1)');
        self::assertSame(777, (int) ($row['peer_id'] ?? 0), 'peer_id must carry the id the wire supplied');

        $write = (new MirrorFactWriter)->write($db, [['table' => 'tf_dialogs', 'row' => $row]]);
        self::assertSame(1, $write['inserted'], 'the dialog row must place into the shipped DDL');
        self::assertSame([], $write['fkClues'], 'the dialog row must place with no write clue at all');

        $stored = $db->table('tf_dialogs')->where('account_id', 1)->where('peer_type', 1)->where('peer_id', 777)->first();
        self::assertNotNull($stored, 'the row must be addressable on the peer pair that IS its primary key');
    }

    /**
     * The ruling of 2026-10-04, proven BY EXECUTION rather than by reading:
     * `tf_message_actions` is PLACEABLE, so it is not an
     * `UNPLACEABLE_KEY_TABLES` entry.
     *
     * Its key column `message_action_id` is supplied only by the edge's
     * `context: ["id"]` overlay — none of the 67 `MessageAction` constructors
     * (`api_full.tl:144-210`) carries a bare `id:int`, which is why
     * `isFillable()` needed `isContextSupplied()` to see the key at all. So the
     * assertion has to run the whole real path (walker → decomposer → writer →
     * shipped migration): a payload-level check would pass for a table whose
     * row never lands, and a writer-level check without the key assertion would
     * pass for a row that lands at the wrong coordinate.
     *
     * The catalog is the SHIPPED file, untouched — the point is that what ships
     * places, not that a hand-built catalog would.
     *
     * ## WHY `context` IS `["id", "peer_id"]` AND NOT `["id"]` (2026-10-04)
     *
     * This assertion used to pin `['id']`, and it was RIGHT about the mechanism
     * and WRONG about the key. `id` is still the overlay that supplies
     * `message_action_id` — the 67-constructor claim above is untouched — but
     * `id` is not an identity.
     *
     * `messageService#7a800e0a` (`src/Schema/schema/sources/api_full.tl:122`)
     * carries `id:int` AND `peer_id:Peer`, and Telegram numbers `id` PER CHAT.
     * So the shipped key `(account_id, message_action_id)` was never unique on a
     * real account: the same message number in two dialogs is the second row of
     * the SAME key, and it REPLACES the first. Measured by reverting the fix and
     * writing the shipped path twice — the second insert reports
     * `inserted=1`, `fkClues=[]`, zero error, and the first row's `title` is
     * gone. A silent overwrite with no clue is the worst failure shape this
     * store has: there is nothing to alert on and nothing to retry.
     *
     * So the key became the enclosing message's COORDINATE
     * `(account_id, peer_type, peer_id, message_action_id)`
     * (`2026_09_29_000100_create_tf_mirror_gap_tables.php:210`), which is the
     * same identity `tf_messages` and `tf_message_medias` already carry, and the
     * overlay grew to carry both halves. `peer_id` is in `context` rather than
     * derived from `keyFrom` because `context` is the ONLY mechanism that puts
     * the peer's VALUE into the fact payload: measured, widening the DDL
     * without it yields `inserted=0` and `row cannot cover its primary key
     * (account_id, peer_type, peer_id, message_action_id) — uncovered key
     * columns: peer_type, peer_id`. That is a LOUDER total loss than the silent
     * overwrite it replaced, and it is why the two halves move together.
     *
     * The assertion is not weakened and the overlay is not optional — the
     * declaration and the executed key are still required to agree, which is
     * what `test_every_catalog_table_ships_the_key_the_resolver_writes` checks
     * for every catalogue table. What changed is the VALUE, because the defect
     * was in the value.
     */
    public function test_a_context_overlaid_key_places_with_no_clue_at_all(): void
    {
        [$catalog, $resolver] = $this->pipeline();

        // The shipped declaration, asserted rather than assumed: if the edge is
        // removed or its context narrowed, the overlay stops supplying the key.
        $sources = $catalog->table('tf_message_actions')->sources ?? [];
        self::assertCount(1, $sources, 'the shipped edge is one: messageService.action');
        self::assertSame('messageService', $sources[0]['ctor'] ?? null);
        self::assertSame('action', $sources[0]['field'] ?? null);
        self::assertSame(
            ['id', 'peer_id'],
            $sources[0]['context'] ?? null,
            'the overlay supplies BOTH halves of the key that IS the primary key: `id` fills '
            .'message_action_id, `peer_id` fills the peer pair fillPeerHalf() reads. `id` ALONE is '
            .'not an identity — `messageService#7a800e0a` (api_full.tl:122) numbers `id:int` PER CHAT '
            .'and carries `peer_id:Peer` alongside it',
        );

        $db = $this->shippedSchema(
            self::PACKAGE_ROOT.'/src/Laravel/Migrations/2026_09_29_000100_create_tf_mirror_gap_tables.php',
        );
        $decomposer = new MirrorFactDecomposer($resolver, $catalog);
        $walker = new MirrorNestedSourceWalker($catalog);

        $facts = $walker->collect([
            '_' => 'messages.messages',
            'messages' => [[
                '_' => 'messageService',
                'id' => 5000111,
                'peer_id' => ['_' => 'peerChat', 'chat_id' => 1232026269],
                'date' => 1712345700,
                // `messageActionChatEditTitle#b5a1ce5a title:string` (TL:146) —
                // a variant with NO vector child, so this test measures the key
                // and nothing else.
                'action' => ['_' => 'messageActionChatEditTitle', 'title' => 'new group title'],
            ]],
            'chats' => [],
            'users' => [],
        ]);

        $rows = [];
        $clues = [];
        foreach ($facts as $fact) {
            $decomposed = $decomposer->decompose($fact['table'], 7, $fact['payload']);
            $clues = [...$clues, ...$decomposed['clues']];
            $rows = [...$rows, ...$decomposed['rows']];
        }

        self::assertSame([], $clues, 'a service message carrying an action must decompose with NO clue');

        $row = null;
        foreach ($rows as $r) {
            if ($r['table'] === 'tf_message_actions') {
                $row = $r['row'];
            }
        }
        self::assertIsArray($row, 'no tf_message_actions row was decomposed');
        self::assertSame(5000111, (int) $row['message_action_id'], 'the overlay must supply the ENCLOSING message id');

        $write = (new MirrorFactWriter)->write($db, [['table' => 'tf_message_actions', 'row' => $row]]);
        self::assertSame(1, $write['inserted'], 'the action row must place into the shipped DDL');
        self::assertSame([], $write['fkClues'], 'the action row must place with no write clue at all');

        $stored = $db->table('tf_message_actions')->where('account_id', 7)->first();
        self::assertNotNull($stored, 'the row must be addressable on the key that IS its primary key');
        self::assertSame('messageActionChatEditTitle', $stored->constructor);
        self::assertSame('new group title', $stored->title);
    }

    /**
     * The fact that made `tf_message_actions` look unplaceable, asserted so it
     * cannot rot into a comment carrying an unmeasured number.
     *
     * `message_action_id` is fillable from ONE place only — the
     * `context: ["id"]` overlay — and this is why: read through the real
     * `TlScheme`, `MessageAction` has 67 constructors and NOT ONE of them
     * carries a bare `id`. So an oracle modelled on constructors alone
     * (`isFillable()`'s ctor branch) can never see this key, and that is
     * exactly the blindness `isContextSupplied()` was added to remove.
     *
     * Asserted rather than narrated because the number is load-bearing twice
     * over: a future `MessageAction` constructor that DID gain an `id` would
     * make the overlay redundant (and possibly wrong), and this is where that
     * would surface first.
     *
     * The `MessageMedia` half is the mirror of the claim corrected in
     * `e8a26be7`, and it cuts the other way: 19 constructors, and exactly one
     * of them DOES carry a bare `id` (`messageMediaStory`, `api_full.tl:137`).
     * It is a STORY id — the stories namespace, keyed `id` on `tf_story_items`
     * — so it is neither a union-wide key nor a valid `message_media_id`, which
     * is why `tf_message_medias` leaves on the overlay too. Both numbers are
     * here so neither can be quietly re-asserted in the wrong direction.
     */
    public function test_only_the_story_constructor_carries_a_bare_id_and_no_action_does_at_all(): void
    {
        [, , $scheme] = $this->pipeline(withScheme: true);

        $actions = $scheme->types()['MessageAction'];
        self::assertCount(67, $actions->constructors(), 'the MessageAction union size is what the overlay-only claim rests on');

        $actionIds = [];
        foreach ($actions->constructors() as $ctor) {
            if (isset($ctor->params()['id'])) {
                $actionIds[] = $ctor->name;
            }
        }
        self::assertSame(
            [],
            $actionIds,
            'a MessageAction constructor has gained a bare `id` — message_action_id would then be '
            .'fillable from the ctor and the `context: ["id"]` overlay would need re-examining, not blind trust',
        );

        $medias = $scheme->types()['MessageMedia'];
        self::assertCount(19, $medias->constructors(), 'the MessageMedia union is 19 constructors, api_full.tl:124-142');

        $mediaIds = [];
        foreach ($medias->constructors() as $ctor) {
            if (isset($ctor->params()['id'])) {
                $mediaIds[] = $ctor->name;
            }
        }
        self::assertCount(
            1,
            $mediaIds,
            'exactly one MessageMedia constructor carries a bare `id` — if this grows, the "no union-wide '
            .'identity" claim needs re-measuring rather than being assumed',
        );
        self::assertStringContainsString(
            'messageMediaStory',
            $mediaIds[0],
            'the bare `id` belongs to messageMediaStory (api_full.tl:137) — a STORY id, the stories '
            .'namespace keyed `id` on tf_story_items, which is why it is not a message_media_id',
        );
    }

    /**
     * WHY `tf_dialogs` LEAVING the allow-list is not the same as `tf_dialogs`
     * BEING SAFE — the difference is a DDL default, measured here.
     *
     * Every table still in `UNPLACEABLE_KEY_TABLES` declares its synthetic key
     * `NOT NULL` with NO default, so a bare insert fails loudly: the fact is
     * dropped and a clue is reported. Bad, but honest. `tf_dialogs` declares
     * `peer_type`/`peer_id` with `->default(0)`, so the same bare insert
     * SUCCEEDS at `(account_id, 0, 0)` — a dialog fact silently written against
     * a peer nobody has. That is the shape of the hazard, asserted so the
     * difference cannot be forgotten when the next table is classified.
     *
     * Deliberately NOT a runtime guard, and deliberately NOT an allow-list
     * entry: the behavioural fix belongs in `placeRow()`, which another lane
     * owns. This test reads the SHIPPED DDL for the defaults (stable) rather
     * than the write path's current behaviour (being changed), so it stays true
     * across that fix instead of pinning the bug it is about to remove.
     */
    public function test_a_peer_keyed_table_with_a_ddl_default_would_corrupt_silently_not_drop_loudly(): void
    {
        $db = $this->shippedSchema(
            self::PACKAGE_ROOT.'/src/Laravel/Migrations/2026_09_14_200004_create_tf_dialogs_tables.php',
            self::PACKAGE_ROOT.'/src/Laravel/Migrations/2026_09_14_200050_create_tf_misc_tables.php',
            self::PACKAGE_ROOT.'/src/Laravel/Migrations/2026_09_29_000100_create_tf_mirror_gap_tables.php',
        );

        // The allow-list's shape: NOT NULL, no default → a bare insert RAISES.
        foreach (self::UNPLACEABLE_KEY_TABLES as $tfName => $cols) {
            foreach ($cols as $col) {
                self::assertNull(
                    $this->columnDefault($db, $tfName, $col),
                    "{$tfName}.{$col} declares a DDL default, so this table is NOT protected by the NOT NULL drop — "
                    .'reclassify it: a table with a default can be inserted at a 0/0 key and corrupt silently',
                );
            }
        }

        // tf_dialogs' shape: defaulted on the peer halves → the same insert SUCCEEDS.
        self::assertSame(
            '0',
            $this->columnDefault($db, 'tf_dialogs', 'peer_type'),
            'tf_dialogs.peer_type must still default to 0 — that default is the documented hazard',
        );

        // And the consequence, measured against the real shipped DDL: a dialog
        // row inserted without its peer key lands at (account_id, 0, 0) instead
        // of being rejected. Written through a plain insert so the assertion is
        // about the DDL, not about placeRow()'s current behaviour.
        $db->table('tf_dialogs')->insert(['account_id' => 1, 'constructor' => 'dialog', 'top_message' => 0]);
        $corrupt = $db->table('tf_dialogs')->where('account_id', 1)->where('peer_type', 0)->where('peer_id', 0)->first();
        self::assertNotNull(
            $corrupt,
            'tf_dialogs must still accept a key-less row at (account_id,0,0) — if this starts failing the DDL '
            .'gained NOT NULL protection and the hazard this documents is gone',
        );
    }

    /**
     * The declared DEFAULT of one shipped column, or null when it has none.
     * Read from the running schema rather than the migration source: the point
     * is what the DATABASE will do with an absent value.
     */
    private function columnDefault(Connection $db, string $table, string $column): ?string
    {
        foreach ($db->select('PRAGMA table_info('.$db->getPdo()->quote($table).')') as $info) {
            $info = (array) $info;
            if (($info['name'] ?? null) !== $column) {
                continue;
            }

            return $info['dflt_value'] === null ? null : trim((string) $info['dflt_value'], "'\"");
        }

        return null;
    }

    /**
     * Build a real in-memory sqlite schema from real shipped migrations, and
     * put the container/facade state back afterwards — this suite is otherwise
     * filesystem-only and must not leak sqlite into its neighbours.
     *
     * @param  list<string>  $migrations
     */
    private function shippedSchema(string ...$migrations): Connection
    {
        $previous = Container::getInstance();
        $capsule = ShippedStoreSchema::capsuleFor(...$migrations);

        try {
            return $capsule->getConnection();
        } finally {
            ShippedStoreSchema::restoreFacadeApplication($previous);
        }
    }

    /**
     * @return array{0: MirrorCatalog, 1: MirrorTableResolver, 2?: TlScheme}
     */
    private function pipeline(bool $withScheme = false): array
    {
        $scheme = TlParser::parseFile(self::PACKAGE_ROOT.'/schema/sources/TL_telegram_v227.tl');
        $catalog = MirrorCatalog::load(self::CATALOG, $scheme);
        $resolver = new MirrorTableResolver($catalog, $scheme);

        return $withScheme ? [$catalog, $resolver, $scheme] : [$catalog, $resolver];
    }

    /**
     * The full shipped key: account scoping + the resolver's key columns,
     * plus `position` for a positioned table.
     *
     * @return list<string>
     */
    private function expectedKey(MirrorTable $table): array
    {
        $expected = array_merge(['account_id'], $table->keyColumns);
        if ($table->positioned) {
            $expected[] = 'position';
        }

        return $expected;
    }

    /**
     * True when the decomposer can source this key from the payload: it is a
     * declared base column, a parameter of one of the entry's constructors, an
     * EXPRESSED DERIVATION (`keyFrom`) whose path resolves to one of those, or
     * one half of a PEER PAIR filled by `MirrorFactDecomposer::fillPeerHalf()`.
     *
     * The derivation half matters: `tf_bot_infos.id` and
     * `tf_saved_star_gifts.id` are neither base columns nor ctor params, and
     * before the derivation existed that was exactly the defect.
     *
     * The peer-pair half matters for the same reason, one rung deeper. A peer
     * key is TWO columns (`peer_type` + `peer_id`) filled from ONE payload
     * field (`peer`), and the decomposer's `fillPeerHalf()` reaches it BEFORE
     * the ordinary key lookup — on the column's `peer` flag, not on the column
     * name matching a payload field. So `peer_type` is fillable even though no
     * constructor carries a param named `peer_type`. Without this branch the
     * oracle mis-models the decomposer and pins the two peer-keyed tables as
     * broken. The condition is read from the RESOLVED table's own column flags
     * and derived by the same stem/candidate arithmetic `fillPeerHalf()` uses,
     * so the next peer-keyed table is classified correctly rather than by name.
     */
    private function isFillable(MirrorTableEntry $entry, MirrorTable $table, TlScheme $scheme, string $col): bool
    {
        foreach ($entry->base as [$name]) {
            if ($name === $col) {
                return true;
            }
        }
        $tlType = $scheme->types()[$entry->tlType] ?? null;

        // fillPeerHalf() path: a peer-flagged column is filled from the single
        // peer field, and it is only reachable when the payload actually
        // carries that field. Mirror the decomposer's own probe: derive the
        // stem from the column name, then accept any of its candidate
        // spellings being a real constructor parameter.
        if ($this->isPeerColumn($table, $col) && $this->hasCtorParamNamed($tlType, $this->peerCandidates($col))) {
            return true;
        }

        // Context-overlay path: a `sources` edge overlays the parent fields it
        // declares in `context` UNDER the nested payload
        // (`MirrorNestedSourceWalker::overlay()`), so a key column the entry's
        // own constructors never carry is still filled — from the ENCLOSING
        // object. See isContextSupplied() for the measurement that added this
        // branch on 2026-10-04; before it, `tf_message_actions` was pinned here
        // as unplaceable while the shipped edge placed it with zero clues.
        if ($this->isContextSupplied($entry, $col)) {
            return true;
        }

        $source = $entry->keySource($col);
        if (! str_contains($source, '.')) {
            return $this->isCtorParam($tlType, $source);
        }
        [$field, $param] = explode('.', $source, 2);
        if (! $this->isCtorParam($tlType, $field)) {
            return false;
        }
        $shape = $this->fieldShape($entry, $field);
        if ($shape === null || ! str_starts_with($shape, 'FK→')) {
            return false;
        }

        return $this->isCtorParam($scheme->types()[substr($shape, 5)] ?? null, $param);
    }

    /**
     * True when a `sources` edge on this entry declares that the field
     * supplying this key column is OVERLAID onto the nested payload from its
     * parent, so the decomposer finds a value the entry's own constructors
     * never carry.
     *
     * `MirrorCatalog` already proves every `context` field is a real parameter
     * of the PARENT constructor at load (`MirrorNestedSource`), so naming it is
     * the whole test — the overlay is unconditional and the child's own fields
     * win a name collision, which is what makes this a fill and not a guess.
     *
     * MEASURED 2026-10-04, and this branch exists because the oracle was blind
     * to it. Walking a real `messageService` (id 5000111) carrying
     * `messageActionChatEditTitle` through the shipped `MirrorNestedSourceWalker`
     * produces a `tf_message_actions` payload whose keys are exactly
     * `["id","_","title"]` — `id` is the enclosing message's, supplied by the
     * edge's `context: ["id"]` — and `MirrorFactDecomposer` then emits the row
     * `{"account_id":7,"constructor":"messageActionChatEditTitle",
     * "message_action_id":5000111,"title":"new group title"}` with an EMPTY
     * clue list. `MirrorFactWriter` reported `inserted=1` and `fkClues=[]`
     * against the shipped migration. MEASURED the same run: none of the 67
     * `MessageAction` constructors in `api_full.tl:144-210` carries a bare
     * `id:int`, so the ctor-param branch genuinely cannot see this key — the
     * overlay is the ONLY source, and an oracle that models only constructors
     * calls a placing table unplaceable.
     */
    private function isContextSupplied(MirrorTableEntry $entry, string $col): bool
    {
        $source = $entry->keySource($col);
        if (str_contains($source, '.')) {
            return false;
        }

        foreach ($entry->sources ?? [] as $declared) {
            if (in_array($source, $declared['context'] ?? [], true)) {
                return true;
            }
        }

        return false;
    }

    /** The declared shape of a base or child field, or null when undeclared. */
    private function fieldShape(MirrorTableEntry $entry, string $field): ?string
    {
        foreach ([...$entry->base, ...$entry->children] as [$name, $shape]) {
            if ($name === $field) {
                return $shape;
            }
        }

        return null;
    }

    /**
     * True when the RESOLVED table declares this column as a peer column
     * (`MirrorColumn::$peer`), which is the flag `fillPeerHalf()` dispatches
     * on. Read from the resolver rather than re-derived from the catalog, so
     * the oracle and the decomposer cannot disagree about which columns are
     * peer columns.
     */
    private function isPeerColumn(MirrorTable $table, string $col): bool
    {
        foreach ($table->columns as $column) {
            if ($column->name === $col) {
                return $column->peer;
            }
        }

        return false;
    }

    /**
     * The payload field names `fillPeerHalf()` probes for a peer column,
     * derived with the decomposer's OWN arithmetic: strip `_type`/`_id` off the
     * column name to get the field stem, then — for the `peer` stem only —
     * accept the `peer_id` spelling too, because the wire spells that field
     * both ways (`Message.peer_id`, `Dialog.peer`, `SavedDialog.peer`).
     *
     * @return list<string>
     */
    private function peerCandidates(string $col): array
    {
        $isType = str_ends_with($col, '_type');
        $field = $isType ? substr($col, 0, -5) : substr($col, 0, -3);

        return $field === 'peer' ? ['peer', 'peer_id'] : [$field, $field === 'peer_id' ? 'peer' : $field];
    }

    /**
     * True when any constructor of the type carries a param under one of these
     * names — i.e. the payload CAN carry a peer field the decomposer would
     * find. `fillPeerHalf()` also normalizes an already-canonical
     * `['_type'|'type', '_id'|'id']` pair, so a carrier name is the right
     * question: without one on the wire there is nothing to normalize.
     *
     * @param  list<string>  $names
     */
    private function hasCtorParamNamed(?object $tlType, array $names): bool
    {
        foreach ($names as $name) {
            if ($this->isCtorParam($tlType, $name)) {
                return true;
            }
        }

        return false;
    }

    /** True when any constructor of the type carries a param of this name. */
    private function isCtorParam(?object $tlType, string $name): bool
    {
        if ($tlType === null) {
            return false;
        }
        foreach ($tlType->constructors() as $ctor) {
            if (isset($ctor->params()[$name])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Every `Schema::create` table in the shipped set mapped to the column list
     * of its `$table->primary([...])` (empty when the block declares none).
     *
     * @return array<string, list<string>>
     */
    private function shippedPrimaryKeys(): array
    {
        $keys = [];
        foreach (glob(self::SHIP_DIR.'/*.php') ?: [] as $file) {
            // COMMENTS ARE STRIPPED FIRST, and that is the whole point — the same
            // defect class the sibling gate `MirrorCatalogShipsInMigrationsTest`
            // carried, found and fixed on 2026-10-06 (commit 594d1c44). This gate
            // had the IDENTICAL `strpos`-over-the-raw-body parser.
            //
            // `2026_10_04_000895_widen_tf_wallpapers_id_to_bigint.php` QUOTES the
            // original create in its docblock:
            //
            //     line 163   Schema::create('tf_wallpapers', …)   $table->integer('id');
            //     line 177   Schema::create('tf_wallpapers_settings', …)
            //
            // Parsing the raw file read those two PROSE LINES as creates. Because
            // the loop assigns `$keys[$name] = …` and that file sorts AFTER
            // 2026_09_29_000100_create_tf_mirror_gap_tables.php — which really does
            // declare `$table->primary(['account_id', 'id'])` at its line 172 — the
            // quoted line OVERWROTE the correct entry with a block containing no
            // `primary([...])`. Both failures below were that one artefact:
            //
            //     test_every_catalog_table_ships_the_key_the_resolver_writes
            //         {"tf_wallpapers":{"resolver":["account_id","id"],"shipped":[]}}
            //     test_the_scope_awaiting_migration_set_is_exactly_the_measured_one
            //         expected [] but got ['tf_wallpapers']
            //
            // Both read as a real fidelity gap — a shipped mirror table missing the
            // primary key its resolver writes, the exact infidelity this project
            // exists to prevent — and neither was true. The FALSE PASS is worse
            // than the false failure: a migration that documents a table WITHOUT
            // quoting its key would have passed here while that table genuinely had
            // no primary key. A gate that reads prose cannot tell a documented table
            // from a shipped one, so it must not read prose.
            $body = $this->stripPhpComments((string) file_get_contents($file));
            $offset = 0;
            while (($start = strpos($body, "Schema::create('", $offset)) !== false) {
                $start += strlen("Schema::create('");
                $end = strpos($body, "'", $start);
                if ($end === false) {
                    break;
                }
                $name = substr($body, $start, $end - $start);
                $offset = $end;

                $next = strpos($body, "Schema::create('", $offset);
                $block = $next === false ? substr($body, $offset) : substr($body, $offset, $next - $offset);

                $keys[$name] = $this->primaryKeyColumns($block);
            }
        }

        return $keys;
    }

    /**
     * Remove `//`, `#` and `/* *\/` comments while preserving every offset.
     *
     * Offsets are preserved because the callers slice this string to delimit
     * `Schema::create(…)` blocks; collapsing lines or changing length would move
     * every boundary. Comments become equivalent runs of spaces and newlines
     * instead, so the remaining text keeps its exact geometry.
     */
    private function stripPhpComments(string $source): string
    {
        $out = '';
        $length = strlen($source);
        $i = 0;

        while ($i < $length) {
            $two = substr($source, $i, 2);

            // Line comment: consume to end of line, keep the newline itself.
            if ($two === '//' || ($source[$i] ?? '') === '#') {
                while ($i < $length && $source[$i] !== "\n") {
                    $out .= ' ';
                    $i++;
                }

                continue;
            }

            // Block comment: consume to the terminator, keeping every newline.
            if ($two === '/*') {
                $out .= '  ';
                $i += 2;

                while ($i < $length) {
                    if (substr($source, $i, 2) === '*/') {
                        $out .= '  ';
                        $i += 2;
                        break;
                    }

                    $out .= ($source[$i] === "\n") ? "\n" : ' ';
                    $i++;
                }

                continue;
            }

            $out .= $source[$i];
            $i++;
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    private function primaryKeyColumns(string $block): array
    {
        $marker = '$table->primary(';
        $start = strpos($block, $marker);
        if ($start === false) {
            return [];
        }
        $start += strlen($marker);
        $open = strpos($block, '[', $start);
        $end = strpos($block, ']', $open === false ? $start : $open);
        if ($open === false || $end === false) {
            return [];
        }

        $cols = [];
        foreach (explode(',', substr($block, $open + 1, $end - $open - 1)) as $raw) {
            $col = trim(trim($raw), "'\"");
            if ($col !== '') {
                $cols[] = $col;
            }
        }

        return $cols;
    }
}
