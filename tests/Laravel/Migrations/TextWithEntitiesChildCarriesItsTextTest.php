<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel\Migrations;

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
 * A `TextWithEntities` child must land its `text` VALUE in the shipped DDL.
 *
 * ## The defect this closes
 *
 * Two shipped child tables declared the flattened column under the PARENT's
 * field name (`title`, `message`) while the resolver emits `text` — the name
 * the single-scalar `TextWithEntities` ctor decomposes to. Both losses were
 * SILENT and doubly so: the value was unset as unmodelled
 * (`unmappedClues`: "column 'text' is not in the shipped DDL — field not
 * modelled, skipped") AND the then-empty NOT NULL column made the insert fail
 * (`fkClues`: "NOT NULL constraint failed: …"), so `tf_todo_items` placed 1 row
 * beside a child that placed 0, and `tf_saved_star_gifts` placed beside
 * `tf_saved_star_gifts_message` at 0.
 *
 * ## Why the RESOLVER is right and the migrations are wrong
 *
 * `tf_dialog_filters_title` (2026_09_29_000100:188) declares `text` and the
 * resolver places into it. Task 3.1's note that this is "DDL-level evidence,
 * not behavioural" is precisely the gap this suite closes: `tf_dialog_filters_title`
 * has no wire-reachable parent in these fixtures, so nothing yet proved a
 * `TextWithEntities` child actually WROTE a `text` value. These two tests do —
 * they run the real walk → decompose → write path against the EXECUTED shipped
 * DDL and assert the value off the wire.
 *
 * ## Why VALUES and never counts
 *
 * A count-only assertion passes with a zero row. Every assertion here names the
 * string that could only have come from this payload, plus the parent row that
 * proves the loss was the child's column and not a broken edge.
 *
 * ## Fixture hygiene
 *
 * Every value is SYNTHETIC: hand-chosen integers, no session string, no real
 * `access_hash`, no DB password. `access_hash` never appears.
 *
 * Provenance: `todoItem#cba9a52f id:int title:TextWithEntities` (TL:2065),
 * `todoList#49b92a26` (TL:2067), `messageMediaToDo#8a53b014` (TL:141),
 * `textWithEntities#751f3146 text:string entities:Vector<MessageEntity>`
 * (TL:1668), `savedStarGift#41df43fc … message:flags.2?TextWithEntities
 * gift:StarGift` (TL:2024), `payments.savedStarGifts` `gifts:Vector<SavedStarGift>`.
 */
final class TextWithEntitiesChildCarriesItsTextTest extends TestCase
{
    private const TL = __DIR__.'/../../../schema/sources/TL_telegram_v227.tl';

    private const CATALOG = __DIR__.'/../../../src/Schema/schema/mirror-catalog.json';

    private const ACCOUNT = 42;

    /**
     * Named as FILES so the shipped DDL is EXECUTED rather than transcribed — a
     * test that re-types the columns is a second copy of them, and a second copy
     * drifts. That is exactly the disease that let this defect ship: the two
     * tables' real columns lived only in the migration.
     */
    /**
     * The todo walk lands THREE facts in one pass — `tf_message_medias` (the
     * `messageMediaToDo` itself), `tf_todo_items` and its title child — so the
     * connection must carry the media table too. A per-test connection holding
     * only the misc migration fails on `no such table: tf_message_medias`,
     * which is a FIXTURE bug that reads exactly like a mirror bug.
     *
     * `tf_message_medias` is NOT in `2026_09_14_200012` (that file creates
     * `tf_messages_media`, a different table) — it arrives with the gap-tables
     * migration. Named here so the next reader does not go looking for it.
     *
     * @var list<string>
     */
    private const TODO_MIGRATIONS = [
        __DIR__.'/../../../src/Laravel/Migrations/2026_09_29_000100_create_tf_mirror_gap_tables.php',
        __DIR__.'/../../../src/Laravel/Migrations/2026_09_14_200050_create_tf_misc_tables.php',
    ];

    /** @var list<string> */
    private const STARS_MIGRATIONS = [
        __DIR__.'/../../../src/Laravel/Migrations/2026_09_14_200040_create_tf_stars_business_tables.php',
    ];

    /** The `textWithEntities` value that must survive, spelled once. */
    private const ITEM_TITLE = 'buy milk';

    private const GIFT_MESSAGE = 'congrats on the gift';

    /**
     * `tf_todo_items_title` — `todoItem#cba9a52f` carries `title:TextWithEntities`
     * (TL:2065), which decomposes to a `text` column.
     *
     * RED before the fix: parent `tf_todo_items` 1 row, child 0 rows, and the
     * ledger carries BOTH clues — "column 'text' is not in the shipped DDL —
     * field not modelled, skipped" and "NOT NULL constraint failed:
     * tf_todo_items_title.title".
     */
    public function test_a_todo_item_title_child_places_its_text_value(): void
    {
        $db = $this->shipped(self::TODO_MIGRATIONS);

        $ledger = $this->ingest($db, $this->payloadWithTodo());

        // The parent DID place, which is what makes this a COLUMN-NAME defect
        // and not a broken edge: if the parent were empty the child failure
        // would mean something else entirely.
        $parent = $db->table('tf_todo_items')->where('id', 9007)->first();
        self::assertNotNull($parent, 'the TodoItem must place — it carries a real id:int (TL:2065)');
        self::assertSame(1, (int) $db->table('tf_todo_items')->count(), 'exactly one TodoItem, no phantom rows');

        // The VALUE, not a count: 'buy milk' can only have come off this payload.
        $child = $db->table('tf_todo_items_title')->where('id', 9007)->first();
        self::assertNotNull(
            $child,
            'tf_todo_items_title must place: the resolver emits column `text` (a TextWithEntities FK '
            .'decomposes to the union\'s own scalar) and 2026_09_14_200050 declares that column. A zero '
            .'row here is the silent loss this test exists to prevent.',
        );
        self::assertSame(self::ITEM_TITLE, $child->text, 'the title must be the textWithEntities string off the wire');
        self::assertSame(self::ACCOUNT, (int) $child->account_id);
        self::assertSame(1, (int) $db->table('tf_todo_items_title')->count());

        // A placed child must leave no residue in either degradation bucket.
        self::assertSame([], $ledger['unmappedClues'], 'nothing may be skipped as unmodelled once the column matches');
        self::assertSame([], $ledger['fkClues'], 'nothing may fail NOT NULL once the column matches');
    }

    /**
     * `tf_saved_star_gifts_message` — `savedStarGift#41df43fc` carries
     * `message:flags.2?TextWithEntities` (TL:2024), the identical mechanism on
     * the stars side, so it is a second proof that the fix is the COLUMN and
     * not a one-off edit.
     *
     * RED before the fix: parent `tf_saved_star_gifts` 2 rows (the gift itself
     * plus its flattened StarGift child), `tf_saved_star_gifts_message` 0.
     */
    public function test_a_saved_star_gift_message_child_places_its_text_value(): void
    {
        $db = $this->shipped(self::STARS_MIGRATIONS);

        $ledger = $this->ingest($db, $this->payloadWithSavedStarGift());

        // The parent placed — keyed by the NESTED StarGift.id (TL:2024 carries
        // no own id), which is what makes the child failure a naming defect.
        $parent = $db->table('tf_saved_star_gifts')->where('id', 55501)->first();
        self::assertNotNull($parent, 'the savedStarGift must place under its derived gift.id key');
        self::assertSame(1712345678, (int) $parent->date, 'the parent date must be the wire value');

        // The VALUE, not a count.
        $child = $db->table('tf_saved_star_gifts_message')->where('id', 55501)->first();
        self::assertNotNull(
            $child,
            'tf_saved_star_gifts_message must place: the resolver emits column `text` and '
            .'2026_09_14_200040 declares that column. A zero row here is the silent loss this test '
            .'exists to prevent.',
        );
        self::assertSame(self::GIFT_MESSAGE, $child->text, 'the message must be the textWithEntities string off the wire');
        self::assertSame(self::ACCOUNT, (int) $child->account_id);
        self::assertSame(1, (int) $db->table('tf_saved_star_gifts_message')->count());

        self::assertSame([], $ledger['unmappedClues'], 'nothing may be skipped as unmodelled once the column matches');
        self::assertSame([], $ledger['fkClues'], 'nothing may fail NOT NULL once the column matches');
    }

    /**
     * WHY NEITHER COLUMN GETS A DEFAULT — the question the rename forces.
     *
     * `tf_dialog_filters_title` carries `->default(DB::raw("('')"))`, so the
     * obvious move is to copy that onto the two columns just renamed. That would
     * be wrong, and this test is the measurement that says so.
     *
     * `savedStarGift#41df43fc` (TL:2024) carries `message:flags.2?TextWithEntities`
     * — OPTIONAL. So a gift with no message is a real wire shape. If the
     * decomposer materialised `tf_saved_star_gifts_message` anyway, the empty
     * NOT NULL column would fire and a default would look necessary. It does
     * not materialise: the child row exists IFF the fact exists, so there is
     * nothing to default.
     *
     * `tf_dialog_filters_title` needs its default for a DIFFERENT and much
     * sharper reason: `tf_dialog_filters`' ctors include `dialogFilterDefault#363293ae`,
     * which is an EMPTY constructor (`= DialogFilter;` — no fields at all, not
     * even `title`). A row for that ctor can never carry `text`, so that table
     * MUST tolerate its absence. `tf_todo_items`' only ctor is `todoItem#cba9a52f`,
     * whose `title:TextWithEntities` is REQUIRED, and `tf_saved_star_gifts`' only
     * ctor is `savedStarGift#41df43fc` — so both tables are the `dialogFilterChatlist`
     * shape, not the `dialogFilterDefault` shape.
     *
     * AND the decisive argument, which is about the failure mode itself: a
     * default would have MASKED the defect this whole task exists to remove.
     * With `default('')`, the same mismatched column would have written a child
     * row with `text = ''` — fabricated data asserting that Telegram sent a
     * message, in a row whose whole reason for existing is that a message was
     * sent. A loud "NOT NULL constraint failed" is the better outcome by a wide
     * margin, and removing it would trade a diagnosable bug for silent loss.
     */
    public function test_a_saved_star_gift_without_a_message_writes_no_message_child_and_needs_no_default(): void
    {
        $db = $this->shipped(self::STARS_MIGRATIONS);

        // Same gift, the OPTIONAL `message:flags.2?TextWithEntities` absent.
        $ledger = $this->ingest($db, [
            '_' => 'payments.savedStarGifts', 'count' => 1,
            'gifts' => [[
                '_' => 'savedStarGift', 'date' => 1712345678,
                'gift' => ['_' => 'starGift', 'id' => 55502, 'stars' => 25],
            ]],
            'next_offset' => '', 'chats' => [], 'users' => [],
        ]);

        // The PARENT still places — the absent optional field costs nothing.
        self::assertNotNull(
            $db->table('tf_saved_star_gifts')->where('id', 55502)->first(),
            'a savedStarGift carrying no message must still place its parent row',
        );

        // And NO child row: row existence IS the fact's existence for a 1:1
        // child. A row here with text = '' — from a default, or from any other
        // source — would claim Telegram sent a message it did not send.
        self::assertSame(
            0,
            (int) $db->table('tf_saved_star_gifts_message')->where('id', 55502)->count(),
            'a 1:1 child row must exist only when the fact exists; an empty-text row would be fabricated data',
        );
        self::assertSame([], $ledger['fkClues'], 'an absent optional fact must not fail NOT NULL');
    }

    // ── fixtures ────────────────────────────────────────────────────────────

    /**
     * `messageMediaToDo.todo` → `todoList.title` + `todoList.list[]`.
     *
     * `title` on BOTH levels is spelled as a real `textWithEntities` OBJECT, not
     * a bare string. That is deliberate: a bare string decomposes with no `text`
     * column at all, so the child would place with an empty value and the test
     * would pass while proving nothing about the value — the fixture bug
     * Task 3.1 documented.
     *
     * @return array<string, mixed>
     */
    private function payloadWithTodo(): array
    {
        return [
            '_' => 'updateNewMessage', 'pts' => 45, 'pts_count' => 1,
            'message' => [
                '_' => 'message', 'id' => 105, 'peer_id' => ['_' => 'peerChat', 'chat_id' => 7],
                'date' => 1700000000, 'message' => 'shopping list',
                'media' => ['_' => 'messageMediaToDo', 'todo' => [
                    '_' => 'todoList', 'others_can_append' => false, 'others_can_complete' => false,
                    'title' => ['_' => 'textWithEntities', 'text' => 'Shopping', 'entities' => []],
                    'list' => [[
                        '_' => 'todoItem', 'id' => 9007,
                        'title' => ['_' => 'textWithEntities', 'text' => self::ITEM_TITLE, 'entities' => []],
                    ]],
                ]],
            ],
        ];
    }

    /**
     * `payments.savedStarGifts.gifts[]` — one `savedStarGift` carrying the
     * optional `message:flags.2?TextWithEntities`. The array form carries no
     * flag word: field PRESENCE is the flag, which is how the catalog-driven
     * decomposer reads it.
     *
     * @return array<string, mixed>
     */
    private function payloadWithSavedStarGift(): array
    {
        return [
            '_' => 'payments.savedStarGifts', 'count' => 1,
            'gifts' => [[
                '_' => 'savedStarGift', 'date' => 1712345678,
                'gift' => ['_' => 'starGift', 'id' => 55501, 'stars' => 25],
                'message' => ['_' => 'textWithEntities', 'text' => self::GIFT_MESSAGE, 'entities' => []],
            ]],
            'next_offset' => '',
            'chats' => [], 'users' => [],
        ];
    }

    // ── the write path ──────────────────────────────────────────────────────

    /**
     * The REAL write path, in the order `MirrorIngesterFactory::build()` calls
     * it: walk → decompose → write.
     *
     * @param  ConnectionInterface  $db
     * @param  array<string, mixed>  $payload
     * @return array{inserted: int, unmappedClues: list<string>, fkClues: list<string>}
     */
    private function ingest($db, array $payload): array
    {
        $catalog = MirrorCatalog::load(self::CATALOG, TlParser::parseFile(self::TL));
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
     * A fresh in-memory connection whose schema IS the executed shipped DDL.
     *
     * @param  list<string>  $migrations
     */
    private function shipped(array $migrations): ConnectionInterface
    {
        return ShippedStoreSchema::capsuleFor(...$migrations)->getConnection();
    }
}
