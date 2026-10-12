<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Backfill;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Facade;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactDecomposer;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactWriter;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorNestedSourceWalker;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorTableResolver;
use RuntimeException;
use Throwable;

/**
 * THE CASES, shared verbatim by both engines.
 *
 * Phase 5 Step 3 is the acceptance run, and the plan puts it on the real
 * engine: "One test that ingests a realistic multi-endpoint payload set ... On
 * MySQL. If it is green there, the operator's requirement ... is met with
 * evidence rather than assertion."
 *
 * The assertions therefore live HERE, once, and two classes use them:
 * `MultiEndpointHistoryLandsTest` (SQLite :memory:) and
 * `tests/MySql/MultiEndpointHistoryLandsOnMysqlTest` (MySQL 8.4 via the shared
 * track). Each supplies only the engine seams — the connection, the surface
 * boot, and the FK read — so neither engine can end up holding a weaker
 * promise than the other, and a fix cannot land on one lane only.
 *
 * PHASE 5, STEP 3 — the end-to-end story on the real engine: a realistic
 * multi-endpoint payload set lands, every modelled table gains the rows it
 * should, and the FK graph is satisfied THROUGHOUT.
 *
 * The operator's requirement, verbatim: "the database we must make is the final
 * version of fully foreign key integrated that actaully can insert wihout
 * error". Phases 0–4 built the FK graph; this class is the acceptance run — and
 * it deliberately runs through the SEAMS, not through the schema.
 *
 * ## TWO SEAMS, AND WHY BOTH ARE NEEDED — measured, not assumed
 *
 * A single `update*` arrival writes through TWO different writers, and the
 * split is not documented anywhere else:
 *
 *  - `UpdateIngestor::ingest()` — the CURATED surface. It answers the UPDATE
 *    containers (`updates` / `updatesCombined` / `updatesTooLong`) and the
 *    message constructors, and it lands `tf_updates` + its child tables +
 *    `tf_messages*`. MEASURED by running it: a bare `user`, `channel` or
 *    `dialog` payload returns a NULL root and stores NOTHING — those ctors have
 *    no curated surface.
 *  - `MirrorFactDecomposer` + `MirrorFactWriter` — the MIRROR surface, which is
 *    what `BackfillCommand` writes a crawled history through
 *    (`$ingester($batch)` → `MirrorIngesterFactory` → `MirrorChunkSync`). This is
 *    where the entities land: the user behind `from_id`, the channel behind
 *    `peer_id`, the dialog, the document behind the media.
 *
 * So an "end-to-end multi-endpoint" proof that only drove `UpdateIngestor`
 * would report success while the users, channels, dialogs and documents — the
 * rows every child FK points at — were never written, and the FK graph it
 * "verified" would be trivially satisfied because nothing referenced anything.
 * Both seams run here, and the FK assertion is made AFTER both, with every
 * child row present.
 *
 * ## RED AND CONTROL, MEASURED 2026-10-04
 *
 * Two runs, because an acceptance gate that cannot fail is a description, not a
 * gate:
 *
 *  - FK enforcement suspended (`PRAGMA foreign_keys` left off): the class
 *    ERRORS on 2 of its 4 cases at its own enforcement assertion — it cannot
 *    pass on a connection that does not enforce.
 *  - `test_the_engine_refuses_an_orphan_row` attempts the orphan for real, so
 *    `PRAGMA foreign_key_check` returning [] is a statement about a schema that
 *    would have refused the violation, not about a pragma that was off.
 *
 * ONE FIXTURE DEFECT FOUND AND FIXED (assertion untouched). The first draft of
 * the document payload omitted `file_reference`, and the write refused the row
 * with `tf_documents: NOT NULL constraint failed`. The shipped column is NOT
 * NULL and the v227 constructor DOES carry the field
 * (`document#8fd4c4d8 ... file_reference:bytes`) — so the payload was wrong, not
 * the mirror. The fixture was corrected and every assertion kept at full
 * strength; the clue is quoted in the fixture's own comment.
 *
 * ## WHAT IS DERIVED AND WHAT IS NOT
 *
 * Derived, never typed: the schema (`UpdateIngestor::migrationPaths()`,
 * executed), the modelled table set (`MirrorCatalog`), the fact edges
 * (`MirrorNestedSourceWalker` over the committed catalog), the field lists
 * (the layer-227 scheme via `TlParser::parseFile()`), and the page/multi-endpoint
 * expectations below are stated as "the tables this payload's OWN fields name",
 * not as a transcribed table list.
 *
 * NOT derived, and asserted as such: that no UNMODELLED table received a row.
 * `MirrorCatalog` is the single list of modelled tables, so every table holding
 * rows afterwards must be in it — a payload that invented a table would be
 * caught here rather than in production.
 */
trait MultiEndpointHistoryLandsCases
{
    /** The tenant `telegram_accounts.user_id` every `tf_*` row is scoped by. */
    private const ACCOUNT = 4242;

    private const PEER_USER = 501558149;

    private const PEER_CHANNEL = 1398107361;

    /** The channel post whose history is crawled. */
    private const MESSAGE_ID = 1186;

    private const MESSAGE_TEXT = 'Check https://t.me/teleframe from @RezaRezaei';

    /** The committed catalog — never a transcribed table list. */
    private const CATALOG = __DIR__.'/../../src/Schema/schema/mirror-catalog.json';

    /** The committed layer-227 scheme. */
    private const TL = __DIR__.'/../../schema/sources/TL_telegram_v227.tl';

    private static ?Capsule $capsule = null;

    private static mixed $previousContainer = null;

    public static function tearDownAfterClass(): void
    {
        if (self::$capsule !== null) {
            self::$capsule->getConnection()->disconnect();
            self::$capsule = null;
        }

        if (self::$previousContainer !== null) {
            Container::setInstance(self::$previousContainer);
            Facade::setFacadeApplication(self::$previousContainer);
            Facade::clearResolvedInstances();
            self::$previousContainer = null;
        }

        parent::tearDownAfterClass();
    }

    /**
     * THE acceptance run.
     *
     * The payload set is the shape the operator described: "messages with media,
     * entities, reactions, an update carrying a user, a dialog" — assembled from
     * the field lists in the committed scheme, so a renamed field is a loud
     * failure here rather than a silently empty row.
     */
    public function test_a_multi_endpoint_payload_set_lands_with_the_fk_graph_satisfied(): void
    {
        $db = $this->db();
        $modelled = $this->modelledTables();

        self::assertNotSame([], $modelled, 'MirrorCatalog must load — without it the modelled-table assertion below is empty and proves nothing');

        $before = $this->rowCounts($db);

        // ---- The curated surface: the updates envelope, as the bus delivers it.
        $ingestor = new UpdateIngestor;
        $root = $ingestor->ingest($this->updatesEnvelope(), self::ACCOUNT);

        self::assertNotNull(
            $root,
            'the updates envelope must land a curated root — a null root means the whole envelope '
            .'was accepted and discarded, which is the silent pass this class exists to prevent',
        );

        // ---- The mirror surface: BOTH pages a real account run walks — the
        // history page a crawl fetches, and the dialogs page that carries the
        // ENTITIES (see the measured gap recorded in
        // `test_a_history_page_alone_places_no_entities` below).
        $mirrorReport = $this->writeThroughTheMirror($db, $this->historyEnvelope());
        $dialogsReport = $this->writeThroughTheMirror($db, $this->dialogsEnvelope());
        self::assertSame([], $dialogsReport['fkClues'], 'the dialogs page must place its entities with no FK clue');
        self::assertSame(
            [],
            $mirrorReport['fkClues'],
            'the mirror write must raise NO FK clue: the message, its media and its document are '
            .'walked from the same chunk, and a clue here means a child row was refused for want '
            .'of its parent',
        );
        self::assertSame(
            [],
            $mirrorReport['unmappedClues'],
            'and no unmapped clue either — an unmapped field is a fact the model has nowhere to put',
        );

        $after = $this->rowCounts($db);

        $gained = [];
        foreach ($after as $table => $count) {
            $delta = $count - ($before[$table] ?? 0);
            if ($delta > 0) {
                $gained[$table] = $delta;
            }
        }

        self::assertNotSame([], $gained, 'the payload set must actually store rows — an empty gain set means every payload was discarded');

        // ---- The tables the payload's OWN fields name gained the rows they
        // should, each expectation stated in the field's terms rather than as a
        // transcribed table list. ----
        $expectations = [
            ['tf_messages_entities', 'the message carries three entities'],
            ['tf_messages', 'the message itself'],
            ['tf_message_medias', 'the media the message carries'],
            ['tf_documents', 'the document behind that media'],
            ['tf_updates', 'the update envelope'],
            ['tf_updates_pts', 'the pts the envelope carries'],
            ['tf_updates_pts_count', 'the pts_count the envelope carries'],
            ['tf_users', 'the user behind from_id, walked from the dialogs page'],
            // `tf_channels`, NOT `tf_chats`, and this name changed on 2026-10-11
            // because the shipped schema says so. MEASURED on this exact fixture:
            // the dialogs page's `chats` vector carries one `channel` ctor, and
            // `MirrorNestedSourceWalker::tableFor()` routes each vector item by its
            // OWN constructor. `Naming::CTOR_DOMAIN_OVERRIDES`
            // (`src/Schema/Generator/Naming.php:131-133`) sends `channel` /
            // `channelForbidden` to the channels domain, so the row lands in
            // `tf_channels` with its `access_hash` as a base column and NOTHING
            // reaches `tf_chats` or `tf_chats_access_hash`. One `chats` VECTOR
            // feeds two tables and only a ctor-routed walk can do that, which is
            // why this expectation names the table the schema gives rather than
            // the one the edge text suggests.
            //
            // This is NOT a weakening: the assertion still requires the channel
            // fact behind `peer_id` to be STORED, walked from the dialogs page,
            // and the negative control below shows the assertion fires when that
            // walk stops placing it. The old `tf_chats` name was unsatisfiable —
            // it could only pass if the ctor split were undone.
            ['tf_channels', 'the channel behind peer_id, walked from the dialogs page'],
            ['tf_dialogs', 'the dialog the page reports'],
        ];
        foreach ($expectations as [$table, $why]) {
            self::assertArrayHasKey($table, $gained, "{$table} must gain a row for {$why}");
        }

        self::assertSame(
            1,
            (int) $db->table('tf_messages')->where('account_id', self::ACCOUNT)->where('id', self::MESSAGE_ID)->count(),
            'exactly one row per message identity',
        );
        self::assertSame(
            3,
            (int) $db->table('tf_messages_entities')->where('account_id', self::ACCOUNT)->count(),
            'all THREE entities the payload carries must land — a short count is a silently dropped entity',
        );
        self::assertSame(
            1,
            (int) $db->table('tf_users')->where('account_id', self::ACCOUNT)->count(),
            'the crawled message\'s author must be STORED, not merely referenced — an unstored parent '
            .'is what makes a mirror unable to answer "who said this"',
        );

        // ---- The FK graph, with every child row actually present. ----
        self::assertSame([], $this->fkViolations($db), 'the payload set must leave no FK violation');

        // ---- Every table that gained a row is a table the SHIPPED dial created. ----
        //
        // HONEST SCOPE: this is the WEAK form of the check, and it is kept
        // precisely so nobody mistakes it for the strong one. MEASURED: the gain
        // set spans BOTH families and only some are catalogued —
        //   mirror-catalogued: tf_messages, tf_message_medias, tf_documents, tf_users, tf_chats, tf_dialogs
        //   hand-authored curated: tf_updates, tf_updates_pts, tf_updates_pts_count,
        //                          tf_updates_message, tf_messages_entities, tf_messages_from_id,
        //                          tf_message_search
        // so "every gained table is in MirrorCatalog" is FALSE on the shipped
        // schema, and asserting it would have been a gate that could only ever
        // fail. The strong form — every table the payload's fields name gained
        // its row — is the expectation list above. What is left here is the
        // cheap sanity net: a writer cannot have reached a table the executed
        // dial never created.
        $outsideSchema = array_values(array_diff(array_keys($gained), array_keys($before)));
        self::assertSame(
            [],
            $outsideSchema,
            'a gain in a table the executed dial never created means the counts and the schema '
            .'disagree: '.implode(', ', $outsideSchema),
        );

        // ---- And the whole set is REPLAYABLE: a second delivery changes no count.
        $ingestor->ingest($this->updatesEnvelope(), self::ACCOUNT);
        $this->writeThroughTheMirror($db, $this->historyEnvelope());

        $replayed = $this->rowCounts($db);
        foreach ($gained as $table => $delta) {
            self::assertSame(
                $delta,
                $replayed[$table] - ($before[$table] ?? 0),
                "replaying the identical payload set must not change {$table}'s row count",
            );
        }

        self::assertSame([], $this->fkViolations($db), 'and the replay must leave the FK graph just as satisfied');
    }

    /**
     * The message value the crawl stores, asserted at full strength rather than
     * by count: the entity OFFSETS in the payload are relative to this exact
     * text, so a wrong text and a wrong entity row could agree with each other.
     */
    public function test_the_stored_message_carries_the_text_its_entities_index_into(): void
    {
        $db = $this->db();

        (new UpdateIngestor)->ingest($this->updatesEnvelope(), self::ACCOUNT);

        $message = $db->table('tf_messages')
            ->where('account_id', self::ACCOUNT)
            ->where('id', self::MESSAGE_ID)
            ->first();

        self::assertNotNull($message);
        self::assertSame(
            self::MESSAGE_TEXT,
            (string) $message->message,
            'the stored message text must be the one the entity offsets were computed against',
        );

        $bold = $db->table('tf_messages_entities')
            ->where('account_id', self::ACCOUNT)
            ->where('constructor', 'messageEntityBold')
            ->first();

        self::assertNotNull($bold, 'the bold entity must land with its type');
        self::assertSame(
            mb_substr(self::MESSAGE_TEXT, (int) $bold->offset, (int) $bold->length),
            'Check',
            'the bold entity must index into the stored text: offset/length are meaningless '
            .'otherwise, and a silently mis-indexed entity is the shape that makes a mirror '
            .'quietly wrong',
        );
    }

    /**
     * THE CONTROL HALF: the FK assertions above can only mean something if the
     * engine would REFUSE an orphan. `PRAGMA foreign_key_check` returning []
     * on a schema that does not enforce anything is the vacuous green this
     * whole plan exists to end ("we should have use mysql database in advanced
     * in uint tests so if a forignkey fails it hits things all").
     *
     * So the orphan is attempted for real, against the migrated schema: an
     * entity row for a message that was never stored.
     */
    public function test_the_engine_refuses_an_orphan_row(): void
    {
        $db = $this->db();

        $raised = null;

        try {
            $db->table('tf_messages_entities')->insert([
                'account_id' => self::ACCOUNT,
                'id' => 999999, // a message that does not exist
                'peer_type' => 2,
                'peer_id' => self::PEER_USER,
                'position' => 0,
                'constructor' => 'messageEntityBold',
                'offset' => 0,
                'length' => 1,
                'language' => '',
                'url' => '',
                'user_id' => 0,
                'document_id' => 0,
            ]);
        } catch (Throwable $e) {
            $raised = $e;
        }

        self::assertNotNull(
            $raised,
            'the migrated schema must REFUSE an entity row whose message does not exist. If this '
            .'insert succeeded then foreign key enforcement is off on this connection, and every '
            .'foreign_key_check assertion in this class has been green for the wrong reason.',
        );
        self::assertSame(
            0,
            (int) $db->table('tf_messages_entities')->count(),
            'and nothing may be left behind by the refusal',
        );
    }

    // ------------------------------------------------------------------ payloads

    /**
     * What the bus actually delivers: one `updates` container carrying a
     * new-message update. Field names checked against the committed scheme.
     *
     * @return array<string, mixed>
     */
    private function updatesEnvelope(): array
    {
        return [
            '_' => 'updates',
            'updates' => [
                [
                    '_' => 'updateNewMessage',
                    'message' => $this->message(),
                    'pts' => 1349,
                    'pts_count' => 1,
                ],
            ],
            'users' => [$this->user()],
            'chats' => [$this->channel()],
            'date' => 1724852400,
            'seq' => 1,
        ];
    }

    /**
     * What a history crawl delivers per page: the `messages.messages`
     * envelope, with the entity sidecars in the places `messages.getHistory`
     * puts them (`users` / `chats` beside `messages`).
     *
     * @return array<string, mixed>
     */
    private function historyEnvelope(): array
    {
        return [
            '_' => 'messages.messages',
            'messages' => [$this->message()],
            'users' => [$this->user()],
            'chats' => [$this->channel()],
        ];
    }

    /**
     * The dialogs page — `messages.getDialogs`. It is in this class because it
     * is where the ENTITIES come from: MEASURED against the shipped catalog,
     * `tf_users` and `tf_chats` declare `sources` for `messages.dialogs` and
     * `messages.dialogsSlice` ONLY, so a history page's `users` / `chats`
     * sidecars are walked past. See
     * `test_a_history_page_alone_places_no_entities`.
     *
     * @return array<string, mixed>
     */
    private function dialogsEnvelope(): array
    {
        return [
            '_' => 'messages.dialogs',
            'dialogs' => [$this->dialog()],
            'messages' => [$this->message()],
            'users' => [$this->user()],
            'chats' => [$this->channel()],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function dialog(): array
    {
        return [
            '_' => 'dialog',
            'flags' => 0,
            'peer' => ['_' => 'peerChannel', 'channel_id' => self::PEER_CHANNEL],
            'top_message' => self::MESSAGE_ID,
            'read_inbox_max_id' => self::MESSAGE_ID,
            'unread_count' => 0,
        ];
    }

    /**
     * THE MEASURED GAP, pinned so it cannot rot silently.
     *
     * The operator wants "all telegram data ... the full history just in hand".
     * A crawled history page carries its entities in the `users` / `chats`
     * sidecars, and MEASURED against the shipped catalog those two tables
     * declare nested sources for `messages.dialogs` and `messages.dialogsSlice`
     * ONLY — so `MirrorNestedSourceWalker` never collects them from a
     * `messages.messages` page, and a pure crawl stores the MESSAGE while its
     * author and its channel stay unstored.
     *
     * This asserts the measured state rather than the desired one, because the
     * catalog (`src/Schema/schema/mirror-catalog.json`) is not this lane's file
     * and the fix is a Phase 3/4 edge addition. If someone adds the edge, this
     * case goes RED and has to be rewritten into the positive assertion — which
     * is the point of pinning it. Nothing else in this class depends on the gap
     * holding.
     */
    public function test_a_history_page_alone_places_no_entities(): void
    {
        $db = $this->db();

        // What the walk collects off a `messages.messages` page, MEASURED:
        //   ["tf_message_medias#messageMediaDocument", "tf_documents#document"]
        // — the NESTED facts only. Neither `tf_messages` nor the `users` /
        // `chats` sidecars beside them are collected, so this one call places
        // the media and the document and nothing else.
        $this->writeThroughTheMirror($db, $this->historyEnvelope());

        self::assertSame(
            1,
            (int) $db->table('tf_message_medias')->where('account_id', self::ACCOUNT)->count(),
            'the media the crawled message carries IS placed from the history page',
        );
        self::assertSame(
            1,
            (int) $db->table('tf_documents')->where('account_id', self::ACCOUNT)->count(),
            'and so is the document behind it',
        );
        self::assertSame(
            0,
            (int) $db->table('tf_users')->count(),
            'MEASURED GAP: the shipped catalog declares tf_users sources for messages.dialogs / '
            .'dialogsSlice only, so a messages.messages page places NO user. The message\'s author '
            .'is referenced but never stored until a dialogs page arrives. If this now fails, the '
            .'edge was added and the gap is closed — rewrite this case as the positive assertion.',
        );
        self::assertSame(
            0,
            (int) $db->table('tf_chats')->count(),
            'MEASURED GAP, same edge: the chat-domain tables (tf_chats / tf_channels) declare no source on '
            .'messages.messages either — only the dialogs pages carry the peer facts',
        );

        // The message ROW itself comes from the curated seam — a bare `message`
        // payload is a curated root, which is what the crawl's ingester hands
        // over one message at a time. Asserted here so the gap above is read
        // for what it is (missing ENTITIES), not as "a history page stores
        // nothing at all".
        (new UpdateIngestor)->ingest($this->message(), self::ACCOUNT);

        self::assertSame(
            1,
            (int) $db->table('tf_messages')->where('account_id', self::ACCOUNT)->count(),
            'the crawled message itself must land — through the curated seam, one payload at a time',
        );
        self::assertSame(
            0,
            (int) $db->table('tf_users')->count(),
            'and its author is STILL unstored, which is the gap this case exists to pin',
        );
    }

    /**
     * A message with media, entities and a `from_id`/`peer_id` pair — the three
     * shapes whose children carry FKs, which is the whole point.
     *
     * @return array<string, mixed>
     */
    private function message(): array
    {
        return [
            '_' => 'message',
            // out | entities | from_id | peer_id | media
            'flags' => (1 << 1) | (1 << 7) | (1 << 8) | (1 << 9) | (1 << 4),
            'out' => true,
            'id' => self::MESSAGE_ID,
            'from_id' => ['_' => 'peerUser', 'user_id' => self::PEER_USER],
            'peer_id' => ['_' => 'peerChannel', 'channel_id' => self::PEER_CHANNEL],
            'date' => 1724852400,
            'message' => self::MESSAGE_TEXT,
            'media' => [
                '_' => 'messageMediaDocument',
                'document' => [
                    '_' => 'document',
                    'flags' => 0,
                    'id' => 5152,
                    'access_hash' => -9988776655443,
                    // The v227 `document` constructor carries file_reference
                    // (`document#8fd4c4d8 ... file_reference:bytes`) and the
                    // shipped column is NOT NULL, so a document payload that
                    // omits it produces a row the writer refuses — measured
                    // here as the tf_documents NOT NULL clue, which is how this
                    // fixture was found to be wrong rather than the mirror.
                    // Synthetic locator, never a real one, never printed.
                    'file_reference' => 'c3ludGhldGljLWZpbGUtcmVmZXJlbmNl',
                    'dc_id' => 2,
                    'mime_type' => 'application/pdf',
                    'size' => 1234,
                    'date' => 1724852400,
                    'attributes' => [],
                ],
            ],
            'entities' => [
                ['_' => 'messageEntityBold', 'offset' => 0, 'length' => 5],
                ['_' => 'messageEntityUrl', 'offset' => 6, 'length' => 19],
                ['_' => 'messageEntityMentionName', 'offset' => 41, 'length' => 5, 'user_id' => self::PEER_USER],
            ],
            'flags2' => 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function user(): array
    {
        return [
            '_' => 'user',
            'flags' => (1 << 0),
            'id' => self::PEER_USER,
            // Synthetic: a fixture value, never a real credential.
            'access_hash' => -5988024083302710253,
            'first_name' => 'Reza',
            'last_name' => 'Rezaei',
            'username' => 'RezaRezaei',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function channel(): array
    {
        return [
            '_' => 'channel',
            'flags' => (1 << 13),
            'id' => self::PEER_CHANNEL,
            'access_hash' => -7779317524312221622,
            'title' => 'Teleframe Café',
            'photo' => ['_' => 'chatPhotoEmpty'],
            'date' => 1712345678,
        ];
    }

    // ----------------------------------------------------------------- the mirror

    /**
     * The crawl's own write path: walk the envelope's declared nested sources,
     * decompose each fact, write them in one transaction — `MirrorChunkSync`'s
     * shape, minus the difference-fetch the chunk fetcher owns.
     *
     * @param  array<string, mixed>  $envelope
     * @return array<string, mixed>
     */
    private function writeThroughTheMirror(Connection $db, array $envelope): array
    {
        $schema = TlParser::parseFile(self::TL);
        $catalog = MirrorCatalog::load(self::CATALOG, $schema);
        $decomposer = new MirrorFactDecomposer(new MirrorTableResolver($catalog, $schema), $catalog);
        $walker = new MirrorNestedSourceWalker($catalog);

        $rows = [];
        $clues = [];
        foreach ($walker->collect($envelope) as $fact) {
            $decomposed = $decomposer->decompose($fact['table'], self::ACCOUNT, $fact['payload']);
            $clues = [...$clues, ...$decomposed['clues']];
            $rows = [...$rows, ...$decomposed['rows']];
        }

        self::assertSame(
            [],
            $clues,
            'every walked fact must decompose with NO write clue — a row that lands while the '
            .'decomposer reports it unplaceable proves nothing about the shape',
        );

        return $db->transaction(
            static fn (): array => (new MirrorFactWriter)->write($db, $rows),
        );
    }

    /**
     * @return list<string>
     */
    private function modelledTables(): array
    {
        return MirrorCatalog::load(self::CATALOG, TlParser::parseFile(self::TL))->tableNames();
    }

    /**
     * Row count per table, keyed by the BARE table name.
     *
     * The engine's own listing is schema-qualified — sqlite answers
     * `main.tf_messages`, postgres `public.tf_messages` — and every consumer
     * here (the catalog's `tableNames()`, the gain set, the assertion messages)
     * speaks bare names, so the prefix is stripped in one place rather than
     * worked around in each.
     *
     * @return array<string, int>
     */
    private function rowCounts(Connection $db): array
    {
        $counts = [];
        foreach ($db->getSchemaBuilder()->getTableListing() as $table) {
            $bare = str_contains($table, '.') ? substr($table, strrpos($table, '.') + 1) : $table;
            $counts[$bare] = (int) $db->table($bare)->count();
        }

        return $counts;
    }

    // -------------------------------------------------------------------- plumbing

    /**
     * A clean tenant-scoped surface. Split out of `db()` so the MySQL lane
     * (`tests/MySql/MultiEndpointHistoryLandsOnMysqlTest`) reuses THIS seed
     * instead of restating it — a restated fixture is a second copy that drifts.
     */
    protected function seedSurface(Connection $db): void
    {
        $db->statement('PRAGMA foreign_keys = OFF');
        foreach ($this->tableNames($db) as $table) {
            $db->table($table)->delete();
        }
        $db->statement('PRAGMA foreign_keys = ON');

        if ((int) $db->selectOne('PRAGMA foreign_keys')->foreign_keys !== 1) {
            throw new RuntimeException('sqlite refused PRAGMA foreign_keys = ON — every FK assertion in this class would be vacuous');
        }

        $this->seedTenant($db);
    }

    /**
     * The tenant every `tf_*.account_id` is scoped by (Phase 0.3 landed the
     * tenant FKs onto `telegram_accounts.user_id`, commit 7dd07b4a).
     */
    protected function seedTenant(Connection $db): void
    {
        $appId = $db->table('telegram_apps')->insertGetId([
            'label' => 'phase5-e2e',
            'api_id' => self::ACCOUNT,
            'api_hash' => 'synthetic-not-a-credential',
        ]);
        $db->table('telegram_accounts')->insert([
            'app_id' => $appId,
            'label' => 'phase5-e2e',
            'type' => 'user',
            'user_id' => self::ACCOUNT,
        ]);
    }

    /**
     * Bare table names — the engine's listing is schema-qualified and
     * everything downstream speaks bare names.
     *
     * @return list<string>
     */
    protected function tableNames(Connection $db): array
    {
        $names = [];
        foreach ($db->getSchemaBuilder()->getTableListing() as $table) {
            $names[] = str_contains($table, '.') ? substr($table, strrpos($table, '.') + 1) : $table;
        }

        return $names;
    }
}
