<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Mirror;

use Illuminate\Container\Container;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\Ddl\MirrorColumnType;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorTable;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorTableResolver;
use MeRezaRezaei\Teleframe\Tests\Store\ShippedStoreSchema;
use PHPUnit\Framework\TestCase;

/**
 * THE GENERIC COLUMN CONTRACT: every column `MirrorTableResolver` emits on a
 * write-reachable table must exist in the EXECUTED shipped DDL.
 *
 * ## THE DEFECT CLASS, AND WHY IT IS SILENT
 *
 * `MirrorFactWriter::write()` pre-filters every row against the live table's
 * column listing (`src/Schema/Mirror/MirrorFactWriter.php:655-663`) and strips
 * a column the table does not have, recording an unmapped clue. So a resolver
 * column with no DDL counterpart is removed BEFORE the insert, and the row is
 * then declined by whichever sibling column is `NOT NULL` with no default. The
 * row is dropped TWICE and the only record is a clue string.
 *
 * MEASURED 2026-10-04 on `tf_saved_star_gifts_collection_id` before `c99af7e0`:
 * 3 unmapped clues ("column 'value' is not in the shipped DDL — field not
 * modelled, skipped"), 3 fkClues ("NOT NULL constraint failed"), 0 stored rows,
 * for a three-element `collection_id`. Every element of every saved gift's
 * `collection_id` was lost.
 *
 * ## WHY THIS FILE IS OVER THE SET AND NOT OVER A LIST
 *
 * The four instances named in the brief are not four defects, they are four
 * samples of one. Three prior passes in this file's own history were named and
 * incomplete:
 *
 *  - `c3bb84f9` fixed `tf_todo_items_title` and `tf_saved_star_gifts_message`
 *    and said so in its commit message, while `tf_todo_lists_title` and
 *    `tf_todo_lists_list` — in the SAME migration, the SAME species — were left
 *    declaring `title`. Its own in-file note explains why, and the reason is the
 *    trap this file exists to close: *"no row can reach them yet, because their
 *    parent's `todo_list_id` key is synthetic and unfillable, so no behavioural
 *    test can be written for them; they are reported, not guessed at."* A sweep
 *    driven by what a behavioural test can REACH is a sweep over the reachable
 *    subset wearing a sweep's clothes. The contract here is the DDL's, not the
 *    writer's, and it is asserted over the resolver's whole reachable graph.
 *  - `c99af7e0` then found `tf_saved_star_gifts_collection_id` — a third
 *    instance of the same species, missed by both earlier passes.
 *  - `ScalarVectorChildColumnMatchesShippedDdlTest` is the narrow generalisation
 *    the sibling lane wrote for ONE sub-shape (scalar-vector children). Its
 *    own docblock records the limit: it checks one derived column per scalar
 *    child, not the resolver's column set.
 *
 * So the assertion below is stated over the SET: walk every catalog root,
 * collect the root and every synthesized direct child the decomposer can write,
 * and require each one's FULL resolver column list to be present in the
 * executed DDL. A fifth instance in a table nobody has heard of fails here.
 *
 * ## THE SET, AND WHY IT IS DEPTH 1
 *
 * The reachable set is the catalog's 36 roots plus their synthesized direct
 * children, filtered by `$child->parentTf === $root->tfName`. That filter is
 * copied from `MirrorFactDecomposer::decomposeChildren()`
 * (`src/Schema/Mirror/MirrorFactDecomposer.php:271-274`), the only place a child
 * row is produced, which walks `$table->children` and never recurses. So a
 * depth-2+ node has no write path and asserting on it would pin hundreds of
 * permanently-absent tables as defects — noise that teaches a reader to ignore
 * a gate. `MirrorCatalogShipsInMigrationsTest::test_deep_child_gap_is_pinned_because_nothing_writes_them`
 * pins that dormant remainder as a growth alarm, on the same graph walk.
 *
 * ## WHAT THIS FILE CANNOT SEE, STATED PLAINLY
 *
 * The DDL side is read by EXECUTING every migration against sqlite
 * (`ShippedStoreSchema::capsuleFor()`), because a transcribed second copy of the
 * schema agrees with itself forever. sqlite erases integer WIDTH: `BIGINT`,
 * `INTEGER`, `SMALLINT` and `TINYINT` all arrive as `INTEGER`, and only
 * `$table->boolean()` keeps its `TINYINT(1)` spelling. So the type assertion
 * below compares TYPE FAMILIES — `bool | int | text | float` — and cannot
 * catch a width divergence. That is a real limit of the executed-DDL
 * discipline, not a gap being papered over: the one width divergence this
 * repo has actually hit (`position` TINYINT vs INTEGER) was found by reading
 * the resolver, not by running sqlite, and its fix carries the reasoning in
 * `MirrorTableResolver::resolveVectorFromType()`.
 *
 * @see ScalarVectorChildColumnMatchesShippedDdlTest for the scalar-vector sub-shape, and for the end-to-end proof that a fixed row actually LANDS with its real value
 */
final class ResolvedColumnsMatchShippedDdlTest extends TestCase
{
    private const PACKAGE_ROOT = __DIR__.'/../../..';

    private const TL = self::PACKAGE_ROOT.'/schema/sources/TL_telegram_v227.tl';

    private const CATALOG = self::PACKAGE_ROOT.'/src/Schema/schema/mirror-catalog.json';

    private const MIGRATIONS = self::PACKAGE_ROOT.'/src/Laravel/Migrations';

    /**
     * The DDL-only columns, as `table.column`.
     *
     * ## HISTORY OF THIS CONSTANT, and why the number moved twice
     *
     * ORIGIN (measured 2026-10-04): written as `[]` first, which was WRONG, not
     * aspirational — the audit reported 27 and the list was those 27. The 27
     * were benign and measured so: the dominant sub-kind was `constructor` (11 of
     * them), a discriminator the resolver emits for no single-concrete-TL-type
     * catalog node, so the shipped column is unused weight rather than a gap
     * (verified for `tf_sticker_sets`, `tf_chats_admin_rights`,
     * `tf_web_pages_url`, `tf_users_usernames`, `tf_dialogs_notify_settings`).
     * `tf_chats_migrated_to.peer_id`/`.peer_type` are the other shape: the
     * resolver emits `channel_id`/`access_hash`/`msg_id` for that node instead.
     *
     * MOVE 1 (27 -> 79, measured 2026-10-09): four catalog ROOTS were added —
     * `tf_channels`, `tf_channel_updates`, `tf_update_differences`, `tf_updates`
     * — each of which is a table the dial has always shipped. That is a real
     * improvement in evidence: before it those tables were BLIND to the audit
     * (no TL type, so every one of their constraints was filed UNEXPLAINED
     * rather than judged), and a blind bucket is explicitly documented as
     * hiding both faithful constraints and real gaps. It is also why the count
     * ROSE: a table the resolver now emits is a table whose shipped columns get
     * compared, so its un-emitted columns become visible. The new entries fall
     * into exactly three benign sub-kinds:
     *
     *  - `tf_channels.*` — 31 boolean flags plus `title`, `access_hash`, `date`.
     *    The catalog declares only `id` in `base` and an empty `bools` list, so
     *    every other shipped scalar on the table is, by construction, DDL-only.
     *    These are DEAD WEIGHT in every install, not data loss — and naming them
     *    here is what makes that claim checkable instead of assumed.
     *  - `tf_updates*.position` — 11 entries. The child's key is the parent's
     *    `key` (`seq`), so `makeChildPkCols()` never emits `position`, while the
     *    dial puts `position` in every child table's primary key. Same benign
     *    kind: the ordinal is carried, the resolver just does not claim it.
     *  - the five `constructor` entries under the new roots, of the origin kind.
     *
     * MOVE 2 (97 -> 79): the four new roots are NOT the subject of this gate.
     * `tf_updates.children` had seven entries naming Telegram NESTED TYPES
     * (`channel`, `chat`, `user`, `peer`, `from`, `message`) plus `messages` as
     * if each were a scalar column of that child table, which made the resolver
     * emit columns (`tf_updates_channel.channel`, …, `tf_updates_messages.value`)
     * that the dial does not declare — the exact defect
     * `test_no_write_reachable_table_lacks_a_column_the_resolver_emits` exists to
     * catch. Removing them dropped 18 fabricated DDL-only rows with them. The
     * trade-off that removal costs is recorded in
     * `test_the_audited_reachable_set_is_the_one_the_write_path_walks`, and the
     * two gates are NOT independent: the audit only reaches `COPYING-GAP=0`
     * again once the migration stores `channel_id`/`chat_id`/`user_id`/`from_id`
     * as columns on `tf_updates` and drops those four child tables, which is a
     * migration and therefore outside the mirror side's reach.
     *
     * MOVE 3 (79 -> 80 -> 79, measured 2026-10-11): this count briefly went to
     * 80 and is back at 79, and the round trip is the record worth keeping.
     *
     * `2026_10_11_000100_fold_bot_infos_description_onto_the_bot_info_fact.php`
     * folded `tf_bot_infos_description` onto `tf_bot_infos.description`. While
     * `mirror-catalog.json` still listed `["description","TEXT NOT NULL"]` in
     * `tf_bot_infos.children`, the resolver kept synthesizing the dropped child
     * table and kept emitting `description` as THAT CHILD's column — so the
     * column showed up here as DDL-only, AND `MirrorFactWriter` raised
     * "no such table: tf_bot_infos_description" on every `botInfo` ingest. A
     * green count would have hidden a production break, which is exactly what
     * this constant exists to make visible.
     *
     * The catalog now declares `description` in `tf_bot_infos.base`, so the
     * resolver emits it as a PARENT column and the DDL-only set is 79 again.
     * The count returning to its original value is the evidence the fold is
     * complete rather than merely recorded.
     *
     * ## 2026-10-11: 79 -> 35, and BOTH halves of that are the same repair
     *
     * Forty-four entries left this set, and none were deleted to make a count
     * look better. Each one is a column the shipped DDL declares and the
     * resolver USED not to emit, so every install carried it as dead weight
     * with no write path. Three catalog repairs made the resolver emit them:
     *
     *  - `tf_channels` base gained `title`, `access_hash`, `date` and its 28
     *    flag bools, matching `Schema::create('tf_channels', …)`
     *    (`2026_09_14_200003_create_tf_channels_tables.php:58-94`). The entry
     *    declared `id` alone, so a channel row landed with its id and dropped
     *    its title, its access hash, its date and every flag — including
     *    `access_hash`, which is what makes a channel resolvable as a peer at
     *    all. 32 entries left the set.
     *  - `tf_updates.key` became `["seq","position"]` and
     *    `tf_channel_updates.key` became `["channel_id","position"]`, matching
     *    `primary(['account_id','seq','position'])` and
     *    `primary(['account_id','channel_id','position'])` in the shipped
     *    migrations. `MirrorKeySpec::childColumns()` copies a parent's key onto
     *    every child, so declaring the ordinal once made the resolver emit the
     *    `position` column on the parent AND on all twelve children that ship
     *    it — 12 more entries left the set.
     *
     * Nothing was ADDED, which is the check that matters: an entry appearing
     * would be a column the DDL declares and the resolver still cannot fill.
     * Re-measured on the shipped dial 2026-10-11 with the same
     * `writeReachableTables()` walk and the same executed-migration read the
     * test uses on every run — 35 measured, 35 pinned.
     *
     * @var list<string>
     */
    private const DDL_ONLY_COLUMNS = [
        'tf_channel_participants.can_edit',
        'tf_channel_participants.inviter_id',
        'tf_channel_participants.is_left',
        'tf_channel_participants.is_self',
        'tf_channel_participants.kicked_by',
        'tf_channel_participants.promoted_by',
        'tf_channel_participants.via_request',
        'tf_channels_admin_rights.constructor',
        'tf_channels_banned_rights.constructor',
        'tf_channels_default_banned_rights.constructor',
        'tf_channels_stories_max_id.constructor',
        'tf_channels_usernames.constructor',
        'tf_chats_admin_rights.constructor',
        'tf_chats_default_banned_rights.constructor',
        'tf_chats_migrated_to.peer_id',
        'tf_chats_migrated_to.peer_type',
        'tf_dialogs.unread_muted_messages_count',
        'tf_dialogs.unread_muted_peers_count',
        'tf_dialogs.unread_unmuted_messages_count',
        'tf_dialogs.unread_unmuted_peers_count',
        'tf_dialogs_notify_settings.constructor',
        'tf_messages_factcheck.text',
        'tf_messages_service.constructor',
        'tf_messages_suggested_post.price_amount',
        'tf_messages_suggested_post.price_nanos',
        'tf_sticker_sets.constructor',
        'tf_sticker_sets_installed_date.constructor',
        'tf_sticker_sets_thumb_dc_id.constructor',
        'tf_sticker_sets_thumb_document_id.constructor',
        'tf_sticker_sets_thumb_version.constructor',
        'tf_todo_lists_list.title',
        'tf_todo_lists_title.title',
        'tf_users_stories_max_id.constructor',
        'tf_users_usernames.constructor',
        'tf_web_pages_url.constructor',
    ];

    private mixed $previousContainer = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousContainer = Container::getInstance();
    }

    protected function tearDown(): void
    {
        ShippedStoreSchema::restoreFacadeApplication($this->previousContainer);
        parent::tearDown();
    }

    /**
     * RED gate. Every resolver-emitted column on a write-reachable table must
     * be a column the shipped DDL declares.
     */
    public function test_no_write_reachable_table_lacks_a_column_the_resolver_emits(): void
    {
        $ddl = $this->shippedColumnsByTable();

        $missing = [];
        foreach ($this->writeReachableTables() as $table) {
            self::assertArrayHasKey(
                $table->tfName,
                $ddl,
                "{$table->tfName} is write-reachable, so the shipped dial must create it — table SHIPPING is "
                ."MirrorCatalogShipsInMigrationsTest's assertion, and this gate only asks about columns",
            );
            // NAMES, not values: $ddl is name => declared SQL type, and an
            // `in_array` over its values would never match a column name and
            // would pass this gate vacuously. Measured: doing so turned 14 real
            // divergences into a green run on 2026-10-04.
            $columns = array_keys($ddl[$table->tfName]);

            foreach ($table->columns as $column) {
                if (in_array($column->name, $columns, true)) {
                    continue;
                }
                $missing[] = $table->tfName.'.'.$column->name;
            }
        }

        self::assertSame([], $missing, sprintf(
            "%d resolver-emitted column(s) the shipped DDL does not declare: %s.\n"
            .'MirrorFactWriter::write() strips a column the table does not have (src/Schema/Mirror/MirrorFactWriter.php:655-663), '
            .'so the value is dropped BEFORE the insert and the row is then declined by whichever sibling column is '
            .'NOT NULL with no default — the row is dropped twice and the only record is a clue string.',
            count($missing),
            implode(', ', $missing),
        ));
    }

    /**
     * The mirror image, pinned: a column the DDL declares and the resolver
     * never emits.
     *
     * It is not asserted empty, and it is not asserted silently either: an
     * unknown extra is named here, so a reader can decide whether it is a
     * hand-authored convenience column or a resolver gap. The measured set is
     * the 27 in `DDL_ONLY_COLUMNS`, every one of them a column no write path
     * produces — see that constant for why each sub-kind is benign.
     */
    public function test_the_columns_the_ddl_declares_but_the_resolver_never_emits_are_exactly_the_measured_set(): void
    {
        $ddl = $this->shippedColumnsByTable();

        $extra = [];
        foreach ($this->writeReachableTables() as $table) {
            if (! isset($ddl[$table->tfName])) {
                continue;
            }
            $shipped = array_keys($ddl[$table->tfName]);
            $emitted = array_map(
                static fn ($c): string => $c->name,
                $table->columns,
            );
            foreach (array_diff($shipped, $emitted) as $column) {
                $extra[] = $table->tfName.'.'.$column;
            }
        }

        sort($extra);

        self::assertSame(self::DDL_ONLY_COLUMNS, $extra, sprintf(
            'the DDL-only column set changed. %d now, %d measured on 2026-10-04. A NEW entry is either a column the '
            .'engine can never fill (dead weight in every install) or a resolver gap this gate should name — read it '
            .'before touching the constant. An entry DISAPPEARING means a resolver column was renamed and the DDL '
            .'followed it, which is fine but must be deliberate.',
            count($extra),
            count(self::DDL_ONLY_COLUMNS),
        ));
    }

    /**
     * Type FAMILIES must agree, which is as far as an executed sqlite DDL can
     * see — see the class docblock on integer width.
     */
    public function test_no_write_reachable_column_declares_a_sql_type_family_the_resolver_does_not(): void
    {
        $ddl = $this->shippedColumnsByTable();

        $mismatched = [];
        foreach ($this->writeReachableTables() as $table) {
            foreach ($table->columns as $column) {
                if (! isset($ddl[$table->tfName][$column->name])) {
                    continue; // named by the assertion above; do not double-report
                }
                $declared = $this->ddlFamily($ddl[$table->tfName][$column->name]);
                $emitted = $this->resolverFamily($column->type);
                if ($declared === $emitted) {
                    continue;
                }
                $mismatched[] = sprintf(
                    '%s.%s: resolver emits %s (%s), shipped DDL declares %s',
                    $table->tfName,
                    $column->name,
                    $column->type->value,
                    $column->type->sql(),
                    $declared,
                );
            }
        }

        self::assertSame([], $mismatched, sprintf(
            "%d write-reachable column(s) disagree with the shipped DDL on SQL TYPE FAMILY: %s.\n"
            .'The families are bool | int | text | float because sqlite erases integer width (BIGINT, INTEGER, SMALLINT '
            .'and TINYINT all read back as INTEGER). A bool/int swap is real: it stores 0/1 against a TEXT column, or '
            .'a string against an integer one. Read the migration, decide which side is wrong, and fix that side.',
            count($mismatched),
            implode('; ', $mismatched),
        ));
    }

    /**
     * The audit must be looking at the set the write path walks, at a size
     * that would notice the graph changing under it.
     *
     * Without this, a resolver that returned nothing would make every other
     * assertion in this file pass vacuously. Measured 2026-10-04: 36 catalog
     * roots and 365 synthesized direct children.
     *
     * ## MEASURED 2026-10-09: 40 roots / 228 write-reachable tables
     *
     * The ROOT count moved 36 -> 40 because four catalog ROOTS were added, and
     * all four name tables the dial has always shipped: `tf_channels`,
     * `tf_channel_updates`, `tf_update_differences`, `tf_updates`. This is not
     * graph growth for its own sake — it is the evidence the audit was missing.
     * Measured on the HEAD catalog, `bin/audit-fidelity.php` reported these
     * four (plus `tf_updates_pts`, `tf_bot_infos_description`, `tf_store_buckets`
     * and `tf_update_routing`) as BLIND PARENTS: with no TL type on the parent,
     * every constraint hanging off them was filed UNEXPLAINED instead of
     * judged, and UNEXPLAINED stood at 51. The repo's own audit output warns
     * that a blind row "hides both faithful constraints AND real gaps". Naming
     * the four roots is what lets the audit read them — and reading them is
     * what exposed the 4 COPYING-GAPs recorded in `DDL_ONLY_COLUMNS`'s history.
     * A blind bucket and a read one are not comparable on their UNEXPLAINED
     * counts, so the drop 51 -> 10 is not a claim that 41 divergences vanished.
     *
     * The TABLE count moved 190 -> 228 with those roots' children, less the
     * seven entries removed from `tf_updates.children` (see below), which the
     * audit had declared as children of `tf_updates` while the dial gives each
     * of them a column name the resolver cannot produce.
     *
     * ## THE TWO GATES ARE NOT INDEPENDENT, and that is the honest limit here
     *
     * `tf_updates.children` cannot express the dial's six peer/peer-id children.
     * The catalog convention hardwires BOTH halves of a child — the table is
     * `{parent}_{fieldName}` and a scalar child's column is named after the
     * field (`MirrorTableResolver::resolveChildField()`,
     * `MirrorFieldDecomposer::fromShape()`) — so `tf_updates_channel` wants the
     * field `channel` with the column `channel_id`, and no shape yields that:
     * a scalar shape names the column `channel`; `FK→Peer` decomposes to
     * `constructor, user_id, chat_id, channel_id`; `1:N child` names the column
     * `value`; `peer_type TINYINT + peer_id BIGINT` names it `channel_type`/
     * `channel_id` and only collapses to `peer_type`/`peer_id` for a field
     * literally named `peer_id`, which would build the table
     * `tf_updates_peer_id`. Declaring the nested TYPE's name was the only
     * expression that produced the right TABLE names, at the price of a
     * fabricated COLUMN on each — which is what
     * `test_no_write_reachable_table_lacks_a_column_the_resolver_emits` measures.
     *
     * So the six entries are removed here (that gate is green as a result) and
     * the cost lands in the audit, which then reports 4 COPYING-GAPs on
     * `tf_updates_channel`, `tf_updates_chat`, `tf_updates_from` and
     * `tf_updates_user` — each of which says the same thing: the wire nests no
     * object there, so the child table and its foreign key should not exist.
     * Those four are a REAL defect in the shipped migration that the fabricated
     * entries were MASKING, and the audit's own remedy is to store
     * `channel_id`/`chat_id`/`user_id`/`from_id` as columns on `tf_updates` and
     * drop the child tables. That is a migration, which this side does not own.
     */
    public function test_the_audited_reachable_set_is_the_one_the_write_path_walks(): void
    {
        $tables = $this->writeReachableTables();

        $roots = 0;
        foreach ($tables as $table) {
            if ($table->parentTf === '') {
                $roots++;
            }
        }

        // MEASURED 2026-10-06, not bumped: 36 roots / 191 write-reachable tables. The
        // previous 37/192 included tf_message_entities, DELETED on purpose in
        // da926d87 after measuring 0 rows written across ten decoded payload shapes,
        // while its sibling tf_messages_entities stores every entity losslessly at
        // positions 0,1,2. Both counts fell by exactly one for that one deletion.
        //
        // Re-measured 2026-10-05: 36 roots / 190 write-reachable tables. One CHILD
        // left the reachable set — `tf_saved_star_gifts_gift_num`, dropped by
        // `2026_10_05_000100_fold_saved_star_gift_num_onto_the_saved_gift_fact.php`
        // because `savedStarGift#41df43fc … gift_num:flags.19?int` is a single int
        // and a child table there asserted an object the wire does not nest. The
        // ROOT count is unchanged at 36 because `tf_saved_star_gifts` itself is
        // still a root: the value moved onto it as a column rather than moving
        // out of the graph. Same shape as the deletion above — the reachable count
        // falls by exactly one for exactly one table, and the root count does not
        // move, because the table that left was a child.
        // MOVE 4, 2026-10-11: 228 -> 227, measured. One deliberate removal moves
        // one count by exactly one, and this removal is deliberate:
        // `2026_10_11_000100_fold_bot_infos_description_onto_the_bot_info_fact.php`
        // dropped `tf_bot_infos_description` and moved its value onto
        // `tf_bot_infos.description`, which `mirror-catalog.json` now declares in
        // `tf_bot_infos.base`. `tf_bot_infos_description` was a CHILD, so — exactly
        // as with the saved-star-gifts deletion above — the ROOT count does not
        // move and only the reachable count does. The two genuine unions it wrongly
        // parented, `tf_bot_infos_description_photo` and `_document`, are NOT in
        // this removal: they are re-parented onto `tf_bot_infos` and remain
        // write-reachable, so 227 is 228 minus one table, not minus three.
        //
        // MEASURED 2026-10-09, not bumped: 40 roots / 228 write-reachable tables.
        // See this method's docblock for the four added roots (every one of them
        // a table the dial already shipped, and a blind parent to the audit
        // before it), and for why 36/190 cannot be restored without either
        // re-declaring six columns the dial does not have or editing a
        // migration. The 2026-10-06 note above still governs the SHAPE of an
        // honest move here: one deliberate removal moves one count by exactly
        // one, and a root count only moves when the graph's top level does.
        self::assertSame(40, $roots, 'the mirror catalog\'s top-level table count changed — re-measure the set');
        self::assertSame(227, count($tables), 'the write-reachable table count changed — re-measure the set');
    }

    // ── the reachable set ───────────────────────────────────────────────────

    /**
     * Every table a row can actually be written to: each catalog root, plus
     * each synthesized direct child whose `parentTf` is that root.
     *
     * The `parentTf === $root->tfName` filter is `decomposeChildren()`'s own
     * (`src/Schema/Mirror/MirrorFactDecomposer.php:272-274`): a shared catalog
     * node (`tf_message_medias`, `tf_chats`, …) is written through its OWN
     * ctor route and is skipped as a child, so counting it here would audit a
     * table twice and skip nothing.
     *
     * @return list<MirrorTable>
     */
    private function writeReachableTables(): array
    {
        $scheme = TlParser::parseFile(self::TL);
        $catalog = MirrorCatalog::load(self::CATALOG, $scheme);
        $resolver = new MirrorTableResolver($catalog, $scheme);

        $out = [];
        foreach ($resolver->resolveAll($catalog->tableNames()) as $root) {
            $out[$root->tfName] = $root;
            foreach ($root->children as $child) {
                if ($child->parentTf !== $root->tfName) {
                    continue;
                }
                $out[$child->tfName] = $child;
            }
        }

        return array_values($out);
    }

    // ── the executed DDL ────────────────────────────────────────────────────

    /**
     * Every table the shipped dial creates, with its real columns AND declared
     * SQL types.
     *
     * ALL migration files are executed, not a named subset: a mirror table can
     * live in any of them, and a test that checked two files would miss a third.
     *
     * @return array<string, array<string, string>> table => column => declared SQL type
     */
    private function shippedColumnsByTable(): array
    {
        $files = glob(self::MIGRATIONS.'/*.php');
        self::assertNotSame([], $files, 'the curated dial must not be empty');

        sort($files);

        $db = ShippedStoreSchema::capsuleFor(...$files)->getConnection();
        $grammar = $db->getQueryGrammar();

        $out = [];
        foreach ($db->select("select name from sqlite_master where type='table'") as $table) {
            $name = (string) (is_object($table) ? $table->name : $table['name']);
            foreach ($db->select('pragma table_info('.$grammar->wrapTable($name).')') as $c) {
                $out[$name][(string) (is_object($c) ? $c->name : $c['name'])] = strtoupper((string) (is_object($c) ? $c->type : $c['type']));
            }
        }

        return $out;
    }

    // ── type families ───────────────────────────────────────────────────────

    /**
     * The declared SQL type, collapsed to the family an executed sqlite DDL can
     * actually distinguish.
     *
     * `TINYINT(1)` is the one width sqlite keeps, because that is what
     * `$table->boolean()` emits; a bare `tinyInteger` and a `bigInteger` both
     * arrive as plain `INTEGER`, which is why this returns `int` for all three
     * and the gate cannot speak about width at all.
     */
    private function ddlFamily(string $declared): string
    {
        if (str_contains($declared, 'TINYINT(1)')) {
            return 'bool';
        }
        if (str_contains($declared, 'DOUBLE') || str_contains($declared, 'FLOAT') || str_contains($declared, 'REAL')) {
            return 'float';
        }
        if (str_contains($declared, 'CHAR') || str_contains($declared, 'TEXT') || str_contains($declared, 'CLOB') || str_contains($declared, 'BLOB')) {
            return 'text';
        }
        if (str_contains($declared, 'INT')) {
            return 'int';
        }

        return 'unknown';
    }

    private function resolverFamily(MirrorColumnType $type): string
    {
        return match ($type) {
            MirrorColumnType::Boolean => 'bool',
            MirrorColumnType::BigInt,
            MirrorColumnType::Integer,
            MirrorColumnType::TinyInt => 'int',
            MirrorColumnType::Double => 'float',
            MirrorColumnType::String => 'text',
        };
    }
}
