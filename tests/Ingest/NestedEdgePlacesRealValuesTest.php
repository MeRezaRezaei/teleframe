<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Database\ConnectionInterface;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactDecomposer;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactWriter;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorNestedSourceWalker;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorTableResolver;
use MeRezaRezaei\Teleframe\Tests\Store\ShippedStoreSchema;
use PHPUnit\Framework\TestCase;

/**
 * Every edge this task adds must land a ROW whose VALUES came off the wire.
 *
 * ## What the two halves of this suite are for
 *
 * `EveryCataloguedTableIsReachableTest` proves the walker REACHES a table. That
 * is not the same as a row landing, and the gap between them is where `media`
 * already cost three fixes: an edge, a descent that ENTERS the node, and a
 * fillable key. Two of those three were invisible because every shipped parent
 * was top-level, so no fixture exercised the descent.
 *
 * So this suite runs the REAL write path — `MirrorNestedSourceWalker` →
 * `MirrorFactDecomposer` → `MirrorFactWriter`, the same triple
 * `MirrorIngesterFactory::build()` calls in the same order — against the
 * EXECUTED shipped migration, and asserts the VALUE that identifies the fact.
 *
 * ## Why VALUES and never counts
 *
 * A count-only assertion passes with a zero row and proves nothing. Worse, it
 * passes on a COLLISION: the writer converges a primary-key repeat by `upsert`
 * and keeps the last value, so a sibling set that shared one key would return
 * `inserted=N` while holding a single row, and `inserted` alone would read as
 * success. So each test asserts the `access_hash` / `dc_id` / `url` / `text`
 * that could only come from this particular payload, and several assert the row
 * COUNT too, together.
 *
 * (This paragraph used to name `tf_message_entities` as the colliding table.
 * It was deleted from the catalog and the shipped dial on 2026-10-04 — nothing
 * ever wrote it, and its entities already ship in `tf_messages_entities`, whose
 * key carries `position` and therefore keeps all of them. The hazard it
 * illustrated is unchanged; it is now illustrated by `tf_message_actions`,
 * which `MessageActionPeerScopeBiteTest` measures on the shipped path.)
 *
 * ## Provenance
 *
 * TL: `messageMediaPhoto#e216eb63` (125), `messageMediaDocument#52d8ccd9` (129),
 * `messageMediaWebPage#ddf10c3b` (130), `messageMediaToDo#8a53b014` (141),
 * `messageService#7a800e0a` (122), `dialogFolder#71bd134c` (213),
 * `messages.messages#1d73e7ea` (283), `channelFull#a04e8d3a` (108),
 * `todoList#49b92a26` (2067), `attachMenuBots` / `messages.botApp` /
 * `messages.botResults` / `messages.savedReactionTags`.
 *
 * Every value here is SYNTHETIC. No session string, no real `access_hash`, no
 * DB password — `access_hash` is a legitimately named COLUMN, and the numbers
 * standing in for it here were chosen by hand.
 */
final class NestedEdgePlacesRealValuesTest extends TestCase
{
    private const TL = __DIR__.'/../../schema/sources/TL_telegram_v227.tl';

    private const CATALOG = __DIR__.'/../../src/Schema/schema/mirror-catalog.json';

    private const ACCOUNT = 42;

    /**
     * The migrations whose DDL these rows land in. Named as FILES so the shipped
     * DDL is executed rather than transcribed — a test that re-types the columns
     * is a second copy of them, and a second copy drifts.
     *
     * ALL of them run into ONE in-memory connection: a payload like
     * `updateNewMessage` carrying a `messageMediaDocument` walks TWO facts
     * (`tf_message_medias` and `tf_documents`) in a single pass, so a per-test
     * connection holding only one migration would fail on the other table — a
     * fixture bug that reads exactly like a mirror bug.
     *
     * @var list<string>
     */
    private const MIGRATIONS = [
        __DIR__.'/../../src/Laravel/Migrations/2026_09_29_000100_create_tf_mirror_gap_tables.php',
        __DIR__.'/../../src/Laravel/Migrations/2026_09_14_200020_create_tf_documents_tables.php',
        __DIR__.'/../../src/Laravel/Migrations/2026_09_14_200021_create_tf_photos_tables.php',
        __DIR__.'/../../src/Laravel/Migrations/2026_09_14_200022_create_tf_web_pages_tables.php',
        __DIR__.'/../../src/Laravel/Migrations/2026_09_14_200050_create_tf_misc_tables.php',
        __DIR__.'/../../src/Laravel/Migrations/2026_09_14_200041_create_tf_bots_tables.php',
        __DIR__.'/../../src/Laravel/Migrations/2026_09_14_200023_create_tf_sticker_sets_tables.php',
        __DIR__.'/../../src/Laravel/Migrations/2026_09_14_200004_create_tf_dialogs_tables.php',
    ];

    /** Synthetic access_hash values — chosen by hand, no real account's. */
    private const DOC_HASH = 111222333444555666;

    private const PHOTO_HASH = 777888999000111222;

    // ── GROUP A: the media descent (the biggest live loss) ──────────────────

    /**
     * `tf_documents` was the single biggest silent loss: `tf_messages` filled
     * while `tf_documents` sat at zero, because `tf_message_medias` declares
     * `photo` / `ttl_seconds` / `video` as CHILDREN and NOT `document` — the
     * field `messageMediaDocument` actually carries.
     *
     * Three fixes, not one, and this test walks all three:
     *   1. the edge            — `messageMediaDocument.document`
     *   2. the DESCENT         — the walker must STAND on `messageMediaDocument`,
     *                            which needs `MessageMedia` to be a declared
     *                            parent type; declaring this edge is what makes
     *                            it one, via
     *                            `MirrorCatalog::tlTypeOfParentLabel()`
     *   3. the KEY             — `document.id` is a real constructor param, so
     *                            the derived `primary([account_id, id])` fills
     */
    public function test_a_document_behind_a_message_media_places_with_its_access_hash_and_dc_id(): void
    {
        $db = $this->shipped();

        $report = $this->ingest($db, $this->payloadWithDocument());

        $row = $db->table('tf_documents')->where('id', 9002)->first();
        self::assertNotNull(
            $row,
            'the Document behind a messageMediaDocument must place — without this, tf_documents stays '
            .'empty forever while tf_messages fills, which is exactly the silent gap this edge closes',
        );

        // The VALUE, not the count: these three can only have come off this payload.
        self::assertSame(self::DOC_HASH, (int) $row->access_hash, 'access_hash must be the one the wire carried');
        self::assertSame(4, (int) $row->dc_id, 'dc_id is the document DC and must survive the write');
        self::assertSame('application/pdf', $row->mime_type);
        self::assertSame(40960, (int) $row->size);
        self::assertSame('document', $row->constructor);
        self::assertSame(self::ACCOUNT, (int) $row->account_id);

        self::assertSame(1, (int) $db->table('tf_documents')->count(), 'exactly one Document, no phantom rows');
        self::assertGreaterThan(0, $report['inserted'], 'the writer must report the row it placed');
    }

    /**
     * `tf_photos` — the `Photo` behind `messageMediaPhoto`, same descent as the
     * Document but a different parent ctor, so it is a second proof that the
     * descent is TYPE-driven and not one hard-coded field.
     */
    public function test_a_photo_behind_a_message_media_places_with_its_access_hash(): void
    {
        $db = $this->shipped();

        $this->ingest($db, $this->payloadWithPhoto());

        $row = $db->table('tf_photos')->where('id', 9001)->first();
        self::assertNotNull($row, 'the Photo behind a messageMediaPhoto must place');
        self::assertSame(self::PHOTO_HASH, (int) $row->access_hash);
        self::assertSame(2, (int) $row->dc_id);
        self::assertSame('photo', $row->constructor);
        self::assertSame(1, (int) $db->table('tf_photos')->count());
    }

    /**
     * `tf_web_pages` — link previews, and the one whose `url` is a CHILD table
     * (`tf_web_pages_url`), so it asserts the child value too: a `web_page` row
     * whose URL never landed would look identical to a working one by count.
     */
    public function test_a_web_page_preview_places_with_its_url(): void
    {
        $db = $this->shipped();

        $this->ingest($db, $this->payloadWithWebPage());

        $row = $db->table('tf_web_pages')->where('id', 9003)->first();
        self::assertNotNull($row, 'the WebPage behind a messageMediaWebPage must place');
        self::assertSame(1700000000, (int) $row->date);
        self::assertSame('webPage', $row->constructor);

        $url = $db->table('tf_web_pages_url')->where('id', 9003)->first();
        self::assertNotNull($url, 'tf_web_pages_url is a CHILD of the fact — it must land with it');
        self::assertSame('https://example.invalid/a-post', $url->url, 'the preview URL must be the wire value');
    }

    // ── GROUP B: service-message actions and dialog folders ─────────────────

    /**
     * `tf_message_actions` via `messageService.action` — NOT `message.action`,
     * which the catalog validator rejects (`message` carries no `action`; only
     * `messageService` does). Measured: "no constructor carries a field of type
     * MessageAction named 'action'".
     *
     * It also needs the THIRD fix. The shipped DDL keys
     * `primary([account_id, message_action_id])`, a synthetic column no
     * `MessageAction` constructor carries — exactly the shape
     * `MirrorCatalog::assertDerivable()`'s context arm was added for, so the
     * entry declares `key: ["message_action_id"]` +
     * `keyFrom: {message_action_id: "id"}` and the edge carries
     * `context: ["id"]`, taking the value from the enclosing messageService.
     */
    public function test_a_service_message_action_places_under_its_own_message_id(): void
    {
        $db = $this->shipped();

        $this->ingest($db, $this->payloadWithServiceAction());

        $row = $db->table('tf_message_actions')->first();
        self::assertNotNull($row, 'the action behind messageService.action must place');
        self::assertSame(104, (int) $row->message_action_id, 'the synthetic key must be the enclosing message id');
        self::assertSame('New Group', $row->title, 'the action\'s own field must survive the overlay');
        self::assertSame('messageActionChatCreate', $row->constructor);
        self::assertSame(1, (int) $db->table('tf_message_actions')->count());
    }

    /**
     * `tf_folders` via `dialogFolder.folder` — the brief called for
     * "`messages.dialogs.dialogs` + folder-id resolution", and the TL says the
     * resolution is a named field: `dialogFolder#71bd134c` carries
     * `folder:Folder` directly, so no id arithmetic is needed.
     *
     * `messages.getDialogs` is in `MirrorResultPolicy::DEFAULT_METHODS`, so this
     * edge is live the first time that method is mirrored.
     */
    public function test_a_dialog_folder_places_with_its_title(): void
    {
        $db = $this->shipped();

        $this->ingest($db, $this->payloadWithFolders());

        $rows = $db->table('tf_folders')->orderBy('id')->get();
        self::assertCount(2, $rows, 'both folders carried by messages.Dialogs.dialogs must place');
        self::assertSame([2, 3], $rows->pluck('id')->map(fn ($i) => (int) $i)->all());
        self::assertSame('Clients', $rows->firstWhere('id', 2)->title);
        self::assertSame('Bots', $rows->firstWhere('id', 3)->title);
        self::assertSame(self::ACCOUNT, (int) $rows->firstWhere('id', 3)->account_id);
    }

    // ── GROUP C: two tables the brief declared unreachable ──────────────────

    /**
     * `tf_forum_topics` via `messages.messages.topics`.
     *
     * The brief recorded this edge as REJECTED — "`messages.messages` has no
     * `topics` in v227 — does not exist". **That is false.** TL line 283 reads
     * `messages.messages#1d73e7ea messages:Vector<Message> topics:Vector<ForumTopic>
     * chats:Vector<Chat> users:Vector<User> = messages.Messages;`, the real
     * `MirrorCatalog::load()` ACCEPTS the edge, and the row lands. Recorded here
     * because a comment carrying a wrong claim is a defect in its own right, and
     * this one would have kept a recoverable table empty forever.
     */
    public function test_a_forum_topic_places_from_the_get_history_envelope(): void
    {
        $db = $this->shipped();

        $this->ingest($db, $this->payloadWithForumTopic());

        $row = $db->table('tf_forum_topics')->where('id', 10)->first();
        self::assertNotNull($row, 'the forum topic behind messages.messages.topics must place');
        self::assertSame('forumTopic', $row->constructor);
        self::assertSame(1, (int) $db->table('tf_forum_topics')->count());
    }

    /**
     * `tf_sticker_sets` via `channelFull.stickerset`.
     *
     * The brief also recorded this as non-existent ("`channelFull` carries no
     * `stickerset` in v227"). **Also false** — `channelFull#a04e8d3a` carries
     * BOTH `stickerset:StickerSet` and `emojiset:StickerSet`, the validator
     * accepts, and the row lands. `channels.getFullChannel` is already in
     * `MirrorResultPolicy::DEFAULT_METHODS`, so this is the cheapest recovery in
     * the whole phase.
     */
    public function test_a_channel_sticker_set_places_with_its_short_name(): void
    {
        $db = $this->shipped();

        $this->ingest($db, $this->payloadWithStickerSet());

        $row = $db->table('tf_sticker_sets')->where('id', 9009)->first();
        self::assertNotNull($row, 'the sticker set behind channelFull.stickerset must place');
        self::assertSame('pack', $row->short_name);
        self::assertSame(12, (int) $row->count);
        self::assertSame(444555666777888999, (int) $row->access_hash);
    }

    // ── GROUP D: the remaining no-edge tables ───────────────────────────────

    public function test_a_bot_app_from_the_get_bot_app_result_places(): void
    {
        $db = $this->shipped();

        $this->ingest($db, $this->payloadWithBotApp());

        $row = $db->table('tf_bot_apps')->where('id', 9010)->first();
        self::assertNotNull($row, 'the bot app behind messages.botApp.app must place');
        self::assertSame('app', $row->short_name);
        self::assertSame('An App', $row->title);
    }

    public function test_a_bot_inline_result_from_the_get_bot_results_result_places(): void
    {
        $db = $this->shipped();

        $this->ingest($db, $this->payloadWithBotResults());

        $row = $db->table('tf_bot_inline_results')->where('id', 'result-1')->first();
        self::assertNotNull($row, 'the inline result behind messages.botResults.results must place');
        self::assertSame('article', $row->type);
        self::assertSame('botInlineResult', $row->constructor);
    }

    public function test_an_attach_menu_bot_from_the_get_attach_menu_bots_result_places(): void
    {
        $db = $this->shipped();

        $this->ingest($db, $this->payloadWithAttachMenuBots());

        $row = $db->table('tf_attach_menu_bots')->where('bot_id', 7)->first();
        self::assertNotNull($row, 'the attach-menu bot behind attachMenuBots.bots must place');
        self::assertSame('menu', $row->short_name);
    }

    /**
     * `tf_todo_items` places, and it is the DEEPEST edge in the catalog —
     * `updateNewMessage` → `message` → `media` → `todo` → `list` → `todoItem`
     * is four wrapper levels, which is the strongest available proof that the
     * type-driven descent in `declaredParentTypesFor()` is general rather than
     * one special case.
     *
     * The edge is `todoList.list`, and `TL:2065` gives `TodoItem` a REAL
     * `id:int`, so the derived `primary(['account_id','id'])` fills with a
     * value the wire genuinely carried. That is exactly what distinguishes
     * this table from its sibling `tf_todo_lists` (excused below): the
     * constructor carries the identity or it does not.
     */
    public function test_a_todo_item_behind_a_message_media_todo_places_with_its_own_id(): void
    {
        $db = $this->shipped();

        $report = $this->ingest($db, $this->payloadWithTodo());

        // `todoItem#cba9a52f id:int` (TL:2065) carries a REAL id, so the
        // derived `primary(['account_id','id'])` fills from the wire — which is
        // precisely what separates this table from `tf_todo_lists` below.
        $item = $db->table('tf_todo_items')->where('id', 9007)->first();
        self::assertNotNull($item, 'the TodoItem behind todoList.list must place — four wrapper levels deep');
        self::assertSame(self::ACCOUNT, (int) $item->account_id);
        self::assertSame(1, (int) $db->table('tf_todo_items')->count(), 'exactly one item, no phantom rows');
        self::assertGreaterThan(0, $report['inserted'], 'the writer must report the row it placed');
    }

    /**
     * The `tf_todo_items_title` CHILD places — and carries its wire VALUE.
     *
     * ## This test used to PIN THE DEFECT. It is inverted, not weakened.
     *
     * It was named `test_the_todo_title_child_is_blocked_by_a_migration_column_
     * name_mismatch` and asserted `assertSame(0, …count())` — it recorded the
     * LOSS as the expected state, because the loss was real and its file
     * belonged to another lane. `c3bb84f9` then corrected the DDL and made
     * this the suite's only failure. The old assertion was a bare count of
     * ZERO, so it could never have carried a value; the one below carries the
     * string that only exists in this payload, which is strictly more than it
     * did.
     *
     * ## What the defect WAS
     *
     * The migration declared the flattened column under the PARENT's field
     * name. A `FK→TextWithEntities` child does not decompose to the parent's
     * field name at all — it decomposes to the UNION'S OWN single scalar,
     * `textWithEntities#751f3146 text:string` (TL:1668), so `todoItem#cba9a52f
     * title:TextWithEntities` (TL:2065) yields a column called `text`. So
     * `2026_09_14_200050_create_tf_misc_tables.php` declared
     * `$table->text('title')` where the resolver emits `text` (that line was
     * `:150` before the fix; the renamed `$table->text('text')` is at `:171`
     * today), and the loss was DOUBLY silent: the value was unset as
     * unmodelled AND the then-empty NOT NULL column failed.
     *
     * ## MEASURED before/after, against the EXECUTED shipped DDL
     *
     * Both numbers below are from running this file's real walk → decompose →
     * write path over `payloadWithTodo()` against the two DDL revisions, not
     * from a reading of either file:
     *
     *  - **BEFORE** the rename (`c3bb84f9^`): `tf_todo_items` **1** row,
     *    `tf_todo_items_title` **0** rows; the table's columns were
     *    `account_id, id, title` — there was no `text` column at all;
     *    `unmappedClues` carried "column 'text' is not in the shipped DDL —
     *    field not modelled, skipped" and `fkClues` carried "NOT NULL
     *    constraint failed: tf_todo_items_title.title".
     *  - **AFTER** (`HEAD`): `tf_todo_items` **1** row, `tf_todo_items_title`
     *    **1** row carrying `text = 'buy milk'`; the columns are
     *    `account_id, id, text`; both clue buckets are EMPTY.
     *
     * ## The RESOLVER was right; the migration was the outlier
     *
     * `tf_dialog_filters_title` already declared `text`
     * (`2026_09_29_000100_create_tf_mirror_gap_tables.php:188`) and the
     * resolver placed into it — which is the evidence that the naming rule was
     * never in question and the two migrations were simply wrong. That is why
     * the FIX was to rename the columns and not to teach the resolver to emit
     * `title`, and why the assertions below are about a placed VALUE: a
     * resolver that had been bent to match a wrong DDL would also have made
     * this test green, with the same silent loss.
     *
     * ## The stars sibling, and why there is no second case here
     *
     * `tf_saved_star_gifts_message` had the IDENTICAL defect —
     * `2026_09_14_200040_create_tf_stars_business_tables.php` declared
     * `$table->text('message')` where the resolver emits `text` (that line was
     * `:391` before the fix; the renamed `$table->text('text')` is at `:412`
     * today) — and measured `tf_saved_star_gifts` **1** /
     * `tf_saved_star_gifts_message` **0** before and **1** / **1** after, with
     * `text = 'congrats on the gift'`. This file has NO sibling assertion for
     * it, and does not need one: this file's `MIGRATIONS` deliberately omit
     * the stars migration, and the stars side already has its own behavioural
     * proof in
     * `tests/Laravel/Migrations/TextWithEntitiesChildCarriesItsTextTest`, which
     * runs the same real write path against the same DDL. A third copy here
     * would be a second copy of a second copy — the drift this suite's header
     * is written against.
     */
    public function test_a_todo_item_title_child_places_its_text_value_off_the_wire(): void
    {
        $db = $this->shipped();

        $ledger = $this->ingest($db, $this->payloadWithTodo());

        // The parent DID place, which is what makes the child's column and
        // nothing else the thing under test: if the parent were empty the
        // child's state would mean something entirely different.
        $parent = $db->table('tf_todo_items')->where('id', 9007)->first();
        self::assertNotNull(
            $parent,
            'the TodoItem must place — it carries a real id:int (todoItem#cba9a52f, TL:2065), so the '
            .'derived primary([account_id, id]) fills off the wire',
        );
        self::assertSame(1, (int) $db->table('tf_todo_items')->count(), 'exactly one TodoItem, no phantom rows');

        // The VALUE, not the count. 'buy milk' is the `text` off this payload's
        // `todoItem.title` and exists in no other fixture in the suite, so a
        // row that placed with an empty column — the failure mode a column
        // DEFAULT would introduce, since it would assert that Telegram sent a
        // title in a row whose whole reason for existing is that it did —
        // cannot pass here.
        $child = $db->table('tf_todo_items_title')->where('id', 9007)->first();
        self::assertNotNull(
            $child,
            'tf_todo_items_title must place: the resolver emits column `text` (a FK→TextWithEntities '
            .'child decomposes to the union\'s own scalar) and 2026_09_14_200050 declares that column '
            .'at :171. A zero row here is the silent loss this test now exists to prevent — and the '
            .'state this test asserted until the DDL was corrected.',
        );
        self::assertSame('buy milk', $child->text, 'the child must carry the textWithEntities string off the wire');
        self::assertSame(self::ACCOUNT, (int) $child->account_id, 'the child is tenant-scoped like every fact');
        self::assertSame(
            1,
            (int) $db->table('tf_todo_items_title')->count(),
            'exactly one title child, no phantom rows',
        );

        // The mechanism is gone, not merely relocated: a row placed by some
        // other path could still leave these two clues behind, and both of them
        // were what made the original loss silent rather than loud.
        self::assertSame(
            [],
            $ledger['unmappedClues'],
            'nothing may be skipped as unmodelled once the declared column matches the resolver',
        );
        self::assertSame(
            [],
            $ledger['fkClues'],
            'nothing may fail NOT NULL once the declared column matches the resolver',
        );
    }

    /**
     * `tf_todo_lists` and `tf_saved_reaction_tags` get NO edge, and this is the
     * test that says so with the mechanism rather than with silence.
     *
     * MEASURED 2026-10-02 (takeover): both edges VALIDATE against the catalog
     * and both walk — the walker returns the fact. The rows still cannot land,
     * because each table's shipped primary key names a synthetic id column that
     * no constructor carries and no enclosing context supplies:
     *
     *  - `tf_todo_lists` → `todo_list_id`. `TL:2067` — `todoList#49b92a26
     *    flags:# others_can_append:flags.0?true others_can_complete:flags.0?true
     *    title:TextWithEntities list:Vector<TodoItem>` — carries NO id at all.
     *    Its only reachable parent is `messageMediaToDo#8a53b014` (`TL:141`),
     *    which carries `todo:TodoList completions:flags.0?Vector<TodoCompletion>`
     *    and likewise no id, so a `context: ["id"]` overlay has nothing to
     *    read. MEASURED: with the edge installed the walker yields the
     *    `tf_todo_lists` fact, and `MirrorFactDecomposer::decompose()` still
     *    cannot fill `todo_list_id`.
     *  - `tf_saved_reaction_tags` → `saved_reaction_tag_id`. `TL:1814` —
     *    `savedReactionTag#cb6ff828 flags:# reaction:Reaction
     *    title:flags.0?string count:int` — carries no id; its only carrier is
     *    `messages.savedReactionTags#3259950a` (`TL:1817`), which carries
     *    `tags:Vector<SavedReactionTag> hash:long` and no id either.
     *
     * `tf_todo_items` is the CONTROL: the same payload, one level deeper, and
     * it lands — because `TL:2065 todoItem#cba9a52f id:int …` DOES carry an
     * id. The difference between the two is the whole argument.
     *
     * Fixing either is a MIGRATION change (Task 2.2 owns migrations): the key
     * must become something the wire carries — `(message identity, position)`
     * for a todo list, or the tag's own `reaction` for a saved tag.
     */
    public function test_a_table_whose_shipped_key_names_a_synthetic_id_gets_no_edge(): void
    {
        $catalog = $this->shippedCatalog();

        foreach ([
            'tf_todo_lists' => 'todo_list_id — TodoList (TL:2067) carries no id and neither does '
                .'its only reachable parent messageMediaToDo (TL:141)',
            'tf_saved_reaction_tags' => 'saved_reaction_tag_id — SavedReactionTag (TL:1814) carries '
                .'no id and neither does messages.savedReactionTags (TL:1817)',
        ] as $table => $reason) {
            self::assertNull(
                $catalog->table($table)->sources,
                "{$table} keys on a synthetic id nothing carries ({$reason}); declaring an edge for it "
                .'would promise rows that can never land — an edge that reaches nothing is worse than '
                .'no edge, because it reads as fixed while placing no row',
            );
        }

        // `tf_admin_log_events` is excused in
        // EveryCataloguedTableIsReachableTest::UNREACHABLE for a DIFFERENT
        // mechanism — a MISSING SCOPE rather than a synthetic id — so it gets its
        // own assertion rather than a strained entry in the loop above. It needs
        // the pin MORE than the other two do, not less, and this is why:
        //
        //   * the edge it must not declare, `channels.adminLogResults.events`,
        //     VALIDATES and WALKS (measured: 1 fact), so unlike the two tables
        //     above nothing about the scheme makes the edge illegal;
        //   * `MirrorResultPolicy.php:80-84` names the exact failure — "the
        //     moment somebody adds that edge, this list goes from safe to
        //     actively wrong with no other signal firing" — because
        //     `channels.getAdminLog` is DELIBERATELY absent from
        //     `MirrorResultPolicy::DEFAULT_METHODS` and the structural reason
        //     (`channelAdminLogEvent#1fad68cd`, api_full.tl:1116, carries no
        //     chat id, channel id or peer; the channel lives on the METHOD's
        //     `channel:InputChannel` parameter, so the shipped
        //     `primary(['account_id', 'id'])` would collide across every
        //     channel this account administers) lives in prose that no
        //     assertion reads.
        //
        // Without the assertion below, adding that edge is a silent green build:
        // the method stays refused, so no row ever lands, so nothing fails — and
        // the table keeps its "unreachable" label while the catalog now claims
        // to reach it. Excusing a table in writing is only meaningful if adding
        // the thing the excuse forbids turns the suite RED, which is what this
        // pins.
        self::assertNull(
            $catalog->table('tf_admin_log_events')->sources,
            'tf_admin_log_events must declare NO edge — it is UNREACHABLE BY POLICY, and '
            .'channels.adminLogResults.events VALIDATES and WALKS (measured: 1 fact), so nothing '
            .'in the scheme forbids declaring it. Its scope is what is missing: '
            .'channelAdminLogEvent#1fad68cd (api_full.tl:1116) is `id:long date:int user_id:long '
            .'action:ChannelAdminLogEventAction` — no chat id, no channel id, no peer — and its '
            .'container channels.adminLogResults#ed8af74d names none either; the channel lives on '
            .'the RPC method\'s `channel:InputChannel` parameter, so the shipped '
            .'primary([\'account_id\', \'id\']) would collide across every channel this account '
            .'administers. channels.getAdminLog is deliberately absent from '
            .'MirrorResultPolicy::DEFAULT_METHODS and that exclusion is pinned by '
            .'tests/Schema/MirrorScopedKeyCorrectnessTest::test_the_admin_log_method_is_not_admitted. '
            .'MirrorResultPolicy.php:83 names the danger this assertion exists for: "the moment '
            .'somebody adds that edge, this list goes from safe to actively wrong with no other '
            .'signal firing". The fix is a resolver threading the RPC chat parameter into the key '
            .'— not an edge.',
        );

        // The control, so the assertions above cannot pass for the wrong reason:
        // the payload's DEEPEST carrier does place, on the very same walk.
        $db = $this->shipped();
        $this->ingest($db, $this->payloadWithTodo());
        self::assertSame(
            1,
            (int) $db->table('tf_todo_items')->count(),
            'tf_todo_items must place from the same payload — if it did not, the two excuses above '
            .'would be excusing a broken walk rather than an unkeyable table',
        );
    }

    // ── the negative half: WITHOUT the edge, the row must NOT place ─────────

    /**
     * A test that only proves the happy path proves nothing about the defect.
     * This is the byte-derived "before": the REAL catalog with this task's edges
     * stripped, which must drop every row the new edges are responsible for.
     */
    public function test_without_the_new_edges_every_one_of_these_rows_is_lost(): void
    {
        $stripped = $this->catalogWithout(['tf_documents', 'tf_photos', 'tf_web_pages', 'tf_message_actions',
            'tf_folders', 'tf_forum_topics', 'tf_sticker_sets', 'tf_bot_apps', 'tf_bot_inline_results',
            'tf_attach_menu_bots', 'tf_todo_lists', 'tf_todo_items', 'tf_saved_reaction_tags', ]);

        $cases = [
            [$this->payloadWithDocument(), 'tf_documents'],
            [$this->payloadWithPhoto(), 'tf_photos'],
            [$this->payloadWithWebPage(), 'tf_web_pages'],
            [$this->payloadWithServiceAction(), 'tf_message_actions'],
            [$this->payloadWithFolders(), 'tf_folders'],
            [$this->payloadWithForumTopic(), 'tf_forum_topics'],
            [$this->payloadWithStickerSet(), 'tf_sticker_sets'],
            [$this->payloadWithBotApp(), 'tf_bot_apps'],
            [$this->payloadWithBotResults(), 'tf_bot_inline_results'],
            [$this->payloadWithAttachMenuBots(), 'tf_attach_menu_bots'],
            [$this->payloadWithTodo(), 'tf_todo_lists'],
            [$this->payloadWithTodo(), 'tf_todo_items'],
            [$this->payloadWithSavedReactionTags(), 'tf_saved_reaction_tags'],
        ];

        foreach ($cases as [$payload, $table]) {
            $db = $this->shipped();
            $this->ingest($db, $payload, $stripped);
            self::assertSame(
                0,
                (int) $db->table($table)->count(),
                "without a declared edge, {$table} must place NOTHING — otherwise the row above proves nothing",
            );
        }
    }

    // ── payloads: shaped as the wire decodes them, values synthetic ─────────

    /** @return array<string, mixed> */
    private function payloadWithDocument(): array
    {
        return [
            '_' => 'updateNewMessage', 'pts' => 42, 'pts_count' => 1,
            'message' => [
                '_' => 'message', 'id' => 102, 'peer_id' => ['_' => 'peerChat', 'chat_id' => 7],
                'date' => 1700000000, 'message' => 'doc post',
                'media' => ['_' => 'messageMediaDocument', 'document' => [
                    '_' => 'document', 'id' => 9002, 'access_hash' => self::DOC_HASH,
                    'file_reference' => "\x05\x06\x07\x08", 'date' => 1700000000,
                    'mime_type' => 'application/pdf', 'size' => 40960, 'dc_id' => 4,
                ]],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function payloadWithPhoto(): array
    {
        return [
            '_' => 'updateNewMessage', 'pts' => 41, 'pts_count' => 1,
            'message' => [
                '_' => 'message', 'id' => 101, 'peer_id' => ['_' => 'peerChat', 'chat_id' => 7],
                'date' => 1700000000, 'message' => 'photo post',
                'media' => ['_' => 'messageMediaPhoto', 'photo' => [
                    '_' => 'photo', 'id' => 9001, 'access_hash' => self::PHOTO_HASH,
                    'file_reference' => "\x01\x02\x03\x04", 'date' => 1700000000,
                    'dc_id' => 2, 'has_stickers' => false,
                    'sizes' => [['_' => 'photoSize', 'type' => 'x', 'w' => 90, 'h' => 90, 'size' => 1234]],
                ]],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function payloadWithWebPage(): array
    {
        return [
            '_' => 'updateNewMessage', 'pts' => 43, 'pts_count' => 1,
            'message' => [
                '_' => 'message', 'id' => 103, 'peer_id' => ['_' => 'peerChat', 'chat_id' => 7],
                'date' => 1700000000, 'message' => 'link post',
                'media' => ['_' => 'messageMediaWebPage', 'webpage' => [
                    '_' => 'webPage', 'id' => 9003, 'date' => 1700000000,
                    'url' => 'https://example.invalid/a-post', 'site_name' => 'Example', 'type' => 'simple',
                ]],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function payloadWithServiceAction(): array
    {
        return [
            '_' => 'updateNewMessage', 'pts' => 44, 'pts_count' => 1,
            'message' => [
                '_' => 'messageService', 'id' => 104, 'peer_id' => ['_' => 'peerChat', 'chat_id' => 7],
                'date' => 1700000000,
                'action' => ['_' => 'messageActionChatCreate', 'title' => 'New Group', 'users' => []],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function payloadWithFolders(): array
    {
        return [
            '_' => 'messages.dialogs',
            'dialogs' => [
                $this->dialogFolder(2, 'Clients', ['_' => 'peerUser', 'user_id' => 7], 104),
                $this->dialogFolder(3, 'Bots', ['_' => 'peerChat', 'chat_id' => 9], 0),
            ],
            'messages' => [], 'chats' => [], 'users' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $peer
     * @return array<string, mixed>
     */
    private function dialogFolder(int $id, string $title, array $peer, int $topMessage): array
    {
        return [
            '_' => 'dialogFolder', 'pinned' => false,
            'folder' => [
                '_' => 'folder', 'id' => $id, 'title' => $title,
                'photo' => ['_' => 'chatPhotoEmpty'],
                'autofill_new_broadcasts' => false, 'autofill_public_groups' => false,
                'autofill_new_correspondents' => false,
            ],
            'peer' => $peer, 'top_message' => $topMessage,
            'unread_muted_peers_count' => 0, 'unread_unmuted_peers_count' => 0,
            'unread_muted_messages_count' => 0, 'unread_unmuted_messages_count' => 0,
        ];
    }

    /** @return array<string, mixed> */
    private function payloadWithForumTopic(): array
    {
        return [
            '_' => 'messages.messages', 'messages' => [],
            'topics' => [[
                '_' => 'forumTopic', 'id' => 10, 'date' => 1700000000,
                'peer' => ['_' => 'peerChannel', 'channel_id' => 555000111], 'top_message' => 101,
            ]],
            'chats' => [], 'users' => [],
        ];
    }

    /** @return array<string, mixed> */
    private function payloadWithStickerSet(): array
    {
        return [
            '_' => 'channelFull', 'id' => 555000111, 'access_hash' => 999000111222333444,
            'title' => 'A Channel',
            'bot_info' => ['_' => 'botInfo', 'user_id' => 7, 'description' => 'a bot', 'commands' => []],
            'stickerset' => [
                '_' => 'stickerSet', 'id' => 9009, 'access_hash' => 444555666777888999,
                'title' => 'Pack', 'short_name' => 'pack', 'count' => 12, 'hash' => 0,
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function payloadWithBotApp(): array
    {
        return [
            '_' => 'messages.botApp',
            'app' => ['_' => 'botApp', 'id' => 9010, 'access_hash' => 222000333444555666,
                'short_name' => 'app', 'title' => 'An App', 'description' => 'does things', 'hash' => 0],
        ];
    }

    /** @return array<string, mixed> */
    private function payloadWithBotResults(): array
    {
        return [
            '_' => 'messages.botResults', 'query' => '', 'cache_time' => 0,
            'results' => [[
                '_' => 'botInlineResult', 'id' => 'result-1', 'type' => 'article', 'title' => 'A Result',
                'input' => ['_' => 'botInputMessageText', 'text' => 'hi', 'offset' => 0],
            ]],
        ];
    }

    /** @return array<string, mixed> */
    private function payloadWithAttachMenuBots(): array
    {
        return [
            '_' => 'attachMenuBots', 'hash' => 42,
            'bots' => [[
                '_' => 'attachMenuBot', 'bot_id' => 7, 'short_name' => 'menu',
                'peer_types' => [],
                'icons' => [['_' => 'attachMenuBotIcon', 'name' => 'icon',
                    'icon' => ['_' => 'documentEmpty', 'id' => 0]]],
            ]],
            'users' => [],
        ];
    }

    /** @return array<string, mixed> */
    private function payloadWithTodo(): array
    {
        return [
            '_' => 'updateNewMessage', 'pts' => 45, 'pts_count' => 1,
            'message' => [
                '_' => 'message', 'id' => 105, 'peer_id' => ['_' => 'peerChat', 'chat_id' => 7],
                'date' => 1700000000,
                'media' => ['_' => 'messageMediaToDo', 'todo' => [
                    '_' => 'todoList', 'others_can_append' => false, 'others_can_complete' => false,
                    'title' => ['_' => 'textWithEntities', 'text' => 'Shopping', 'entities' => []],
                    // `title:TextWithEntities` (TL:1668) — a nested OBJECT, not a
                    // string. Spelling it as a bare string is the same class of
                    // fixture bug as `attachMenuBotBot`: it silently produces a
                    // child row with no `title`, which reads as "the edge placed"
                    // while proving nothing about the value.
                    'list' => [['_' => 'todoItem', 'id' => 9007,
                        'title' => ['_' => 'textWithEntities', 'text' => 'buy milk', 'entities' => []]]],
                ]],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function payloadWithSavedReactionTags(): array
    {
        return [
            '_' => 'messages.savedReactionTags',
            'tags' => [[
                // Byte-shaped: `savedReactionTag#cb6ff828 flags:# reaction:Reaction
                // title:flags.0?string count:int` (api_full.tl:1814) carries NO
                // `id`. Fabricating one would let a future edge on
                // `messages.savedReactionTags.tags` land a row for the wrong
                // reason — the whole point of excusing this table is that
                // nothing carries the id its key names.
                '_' => 'savedReactionTag', 'title' => 'Thumbs',
                'reaction' => ['_' => 'reactionEmoji', 'emoticon' => "\xF0\x9F\x91\x8D"], 'count' => 3,
            ]],
        ];
    }

    // ── the write path ─────────────────────────────────────────────────────

    /**
     * The REAL write path, in the order `MirrorIngesterFactory::build()` calls
     * it: walk → decompose → write.
     *
     * It returns the writer's two DEGRADATION buckets alongside `inserted`,
     * because "the row landed" and "the row landed for the right reason" are
     * different claims: a value dropped as unmodelled and a NOT NULL failure
     * are both silent, and a test that can only see the row count cannot tell
     * them from a clean write. The buckets were previously discarded here,
     * which is why the column-name defect could sit behind a green build.
     *
     * @param  ConnectionInterface  $db
     * @param  array<string, mixed>  $payload
     * @return array{inserted: int, unmappedClues: list<string>, fkClues: list<string>}
     */
    private function ingest($db, array $payload, ?MirrorCatalog $catalog = null): array
    {
        $catalog ??= $this->shippedCatalog();
        $scheme = TlParser::parseFile(self::TL);
        $decomposer = new MirrorFactDecomposer(new MirrorTableResolver($catalog, $scheme), $catalog);

        $totals = ['inserted' => 0, 'unmappedClues' => [], 'fkClues' => []];
        foreach ((new MirrorNestedSourceWalker($catalog))->collect($payload) as $fact) {
            $decomposed = $decomposer->decompose($fact['table'], self::ACCOUNT, $fact['payload']);
            $report = (new MirrorFactWriter)->write($db, $decomposed['rows']);
            $totals['inserted'] += $report['inserted'];
            foreach (['unmappedClues', 'fkClues'] as $bucket) {
                $totals[$bucket] = array_merge($totals[$bucket], $report[$bucket]);
            }
        }

        return $totals;
    }

    /**
     * A fresh in-memory connection whose schema is the EXECUTED shipped DDL.
     */
    private function shipped(): ConnectionInterface
    {
        return ShippedStoreSchema::capsuleFor(...self::MIGRATIONS)->getConnection();
    }

    private function shippedCatalog(): MirrorCatalog
    {
        return MirrorCatalog::load(self::CATALOG, TlParser::parseFile(self::TL));
    }

    /**
     * The REAL catalog with the named tables' `sources` stripped — the state
     * this task's tree shipped in, derived from the real file so the "before"
     * cannot drift into a fiction.
     *
     * ## Why `key` and `keyFrom` go WITH the edge
     *
     * A `keyFrom` whose only supply is a `sources` `context` overlay becomes
     * UNDERIVABLE the moment the edge is removed:
     * `MirrorCatalog::assertDerivable()`'s context arm requires the field to be
     * declared in the `context` of one of this entry's own sources, so stripping
     * `sources` alone makes `MirrorCatalog::load()` throw
     * *"'id' is not a parameter of any constructor of MessageAction, nor a
     * declared context field of any of its sources"*.
     *
     * That throw is the validator WORKING — it refuses a key nothing can fill.
     * But it means the negative half cannot be built by stripping the edge
     * alone, so the pre-task state has to be reconstructed whole: the edge AND
     * the key the edge's overlay fed. `tf_message_actions` is the only entry
     * that needs this — it is the only one whose `key`/`keyFrom` this task
     * added — and it is stripped back to exactly what `01c70dcc` shipped
     * (`key: null, keyFrom: null, sources: null`, verified by reading that
     * commit's blob), which is what makes this a real "before" rather than a
     * convenient one.
     *
     * @param  list<string>  $tables
     */
    private function catalogWithout(array $tables): MirrorCatalog
    {
        $entries = json_decode((string) file_get_contents(self::CATALOG), true, 512, JSON_THROW_ON_ERROR);
        foreach ($tables as $table) {
            unset($entries[$table]['sources'], $entries[$table]['key'], $entries[$table]['keyFrom']);
        }

        $path = tempnam(sys_get_temp_dir(), 'teleframe-catalog-').'.json';
        file_put_contents($path, (string) json_encode($entries, JSON_THROW_ON_ERROR));

        try {
            return MirrorCatalog::load($path, TlParser::parseFile(self::TL));
        } finally {
            @unlink($path);
        }
    }
}
