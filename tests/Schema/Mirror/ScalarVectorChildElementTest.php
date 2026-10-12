<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Mirror;

use Illuminate\Container\Container;
use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlScheme;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactDecomposer;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactWriter;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorNestedSourceWalker;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorTable;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorTableResolver;
use MeRezaRezaei\Teleframe\Tests\Store\ShippedStoreSchema;
use PHPUnit\Framework\TestCase;

/**
 * A `Vector<long>` ELEMENT is a scalar, and `childRow()` demanded an array.
 *
 * ## THE FATAL, reproduced against the shipped catalog and scheme (2026-10-04)
 *
 * `tf_message_actions` declares `base: [["users", "1:N child"]]`.
 * `messageActionChatCreate#bd47cbad title:string users:Vector<long> =
 * MessageAction` (`src/Schema/schema/sources/api_full.tl:145`) makes that
 * vector a list of user ids. Running the shipped walker and the shipped
 * decomposer over one real `messageService` carrying that action raises:
 *
 *     TypeError: MirrorFactDecomposer::childRow(): Argument #5 ($item) must be
 *     of type array, int given, called in
 *     src/Schema/Mirror/MirrorFactDecomposer.php on line 294
 *       at src/Schema/Mirror/MirrorFactDecomposer.php:317
 *
 * `MirrorFactWriter::write()` catches `QueryException` and nothing else
 * (`src/Schema/Mirror/MirrorFactWriter.php:639`), and a `TypeError` is not one,
 * so the exception leaves the write and takes the whole PAGE with it — the same
 * blast radius as the `Duplicate entry` failures the writer's own docblock
 * records for the page transaction in
 * `src/Laravel/MirrorIngesterFactory.php:237`.
 *
 * Why every shipped test missed it: the one fixture that reaches this path,
 * `tests/Ingest/NestedEdgePlacesRealValuesTest::payloadWithServiceAction()`,
 * carries `'users' => []` — an EMPTY list, so the loop that would raise never
 * executes. A `Vector<long>` that Telegram actually fills is the case no test
 * had.
 *
 * ## WHY THE `base` DECLARATION IS NOT THE DEFECT, AND `childRow()` IS
 *
 * Both halves of the declaration are load-bearing and CORRECT, which is what
 * makes the `base` declaration the wrong thing to "fix":
 *
 *  - `MirrorTableResolver::resolveVectorChild()` routes the shape
 *    (`src/Schema/Mirror/MirrorTableResolver.php:376-378`) to
 *    `resolveVectorFromType()`, whose own docblock states the rule in the
 *    first paragraph (lines 458-460): "scalar elements (`Vector<long>`,
 *    `Vector<string>`, ...) become a positioned child holding a single `value`
 *    column so no element data is dropped." The scalar branch that implements
 *    it is at lines 523-528, and `scalarValueColumn()` (lines 534-545) names
 *    that column `value` for every scalar element type.
 *  - The shipped DDL already agrees. `tf_message_actions_users` declares
 *    `position` + `value` and keys `(account_id, message_action_id, position)`
 *    (`src/Laravel/Migrations/2026_09_29_000100_create_tf_mirror_gap_tables.php:211-218`).
 *
 * So the resolver and the DDL agree completely, and the ONE layer that cannot
 * cope is the decomposer, whose `childRow()` signature demands `array $item`
 * from a wire element that is an `int`. Removing or reshaping the `base` entry
 * would throw away a correct declaration to work around a missing branch.
 *
 * ## AND THE DECOMPOSER'S OWN CONTRACT ALREADY SAYS: CLUE, NEVER THROW
 *
 * The class docblock states it (lines 22-24): "a constructor the catalog does
 * not know, or a payload that cannot fill a keying/peer column, is reported as
 * an ingest clue — NOT swallowed." `castValue()` implements it for an
 * uncastable value (lines 491-497), `fillPeerHalf()` for an unresolvable peer
 * (lines 453-471), and the positioned arm of `decomposeChildren()` ALREADY has
 * the precedent one arm away: a positioned child whose payload is not a list
 * records a clue and continues (lines 285-289) rather than throwing. The
 * RESOLVER is the layer that throws on a catalog bug (`deriveNaturalKey()`
 * rule 5); the decomposer is the layer that clues. This was the only place
 * that broke the promise.
 *
 * ## NOT A CLUE-AND-DROP: THE VALUE MUST LAND
 *
 * Degrading to "record a clue and skip the element" would be a SILENT DATA LOSS
 * dressed as a fix, which is the defect this test is written against. The
 * resolver already built the column that holds the value (`value`), so a scalar
 * element is wrapped under that column name and the row places with NO clue at
 * all — zero clues being the standard `tests/Schema/Mirror` holds everything
 * else to. A clue is recorded only for an element that is genuinely neither an
 * object nor a storable scalar, and that clue says what actually happened.
 *
 * ## THE PATTERN REPEATS — IT IS NOT ONE TABLE
 *
 * Measured over all 74 fact-shape declarations in the shipped catalog
 * (`base` + `children`, shape `FK→` or `1:N child`), cross-referenced against
 * the real `TlScheme`: exactly TWO name a wire field whose TL kind is a vector
 * of scalars, and BOTH raise this identical `TypeError` today:
 *
 *   - `tf_message_actions` `base` `users` — `Vector<long>` (api_full.tl:145)
 *   - `tf_saved_star_gifts` `children` `collection_id` — `Vector<int>`
 *     (`savedStarGift#41df43fc … collection_id:flags.15?Vector<int> …`,
 *     api_full.tl:2024), whose child table
 *     `tf_saved_star_gifts_collection_id` ships at
 *     `src/Laravel/Migrations/2026_09_14_200040_create_tf_stars_business_tables.php:465-471`
 *
 * Neither declaration is wrong, so neither is corrected here. The fix is at
 * the shared branch both reach, and both are asserted below so a THIRD
 * declaration cannot reintroduce the fatal silently.
 */
final class ScalarVectorChildElementTest extends TestCase
{
    private const PACKAGE_ROOT = __DIR__.'/../../..';

    private const TL = self::PACKAGE_ROOT.'/schema/sources/TL_telegram_v227.tl';

    private const CATALOG = self::PACKAGE_ROOT.'/src/Schema/schema/mirror-catalog.json';

    private const MIG_GAP = self::PACKAGE_ROOT.'/src/Laravel/Migrations/2026_09_29_000100_create_tf_mirror_gap_tables.php';

    private const ACCOUNT = 7;

    private const MESSAGE_ID = 5000112;

    private const PEER_ID = 1232026269;

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
     * RED, and the reason it is red is the whole point: this raised a
     * `TypeError` out of `childRow()` before the fix. The assertion is
     * deliberately on the VALUES and not on "no exception", because "no
     * exception" is also what a clue-and-drop would produce.
     */
    public function test_a_vector_long_element_places_its_value_in_the_value_column(): void
    {
        [$decomposer, $catalog, $tl] = $this->pipeline();

        $out = $decomposer->decompose(
            'tf_message_actions',
            self::ACCOUNT,
            $this->actionPayload($catalog, [4242, 7777, 8888]),
        );

        self::assertSame(
            [],
            $out['clues'],
            'a `Vector<long>` element is exactly what `MirrorTableResolver::resolveVectorFromType()` '
            .'built a positioned `value` column FOR (src/Schema/Mirror/MirrorTableResolver.php:523-528), '
            .'and the shipped DDL declares it — so a filled users list is a modelled fact and must clue '
            .'nothing. A clue here would be noise on every group-create action Telegram ever sends.',
        );

        self::assertSame(
            [
                ['position' => 0, 'value' => 4242],
                ['position' => 1, 'value' => 7777],
                ['position' => 2, 'value' => 8888],
            ],
            $this->childRows($out['rows'], 'tf_message_actions_users'),
            'each element of the wire list becomes one positioned row carrying its OWN id, in wire order — '
            .'the ordinal is `position` and the id is `value`, which is the column the resolver names for a '
            .'scalar element (scalarValueColumn(), src/Schema/Mirror/MirrorTableResolver.php:534-545)',
        );
    }

    /**
     * The second of the two measured declarations, proving the fix is at the
     * shared branch and not at one table's name. This raised the identical
     * `TypeError` before the fix.
     *
     * The parent row is NOT asserted clue-free, and the reason is measured, not
     * convenient: this table's key is `keyFrom: {"id": "gift.id"}`, so the
     * payload must carry a nested `StarGift` for the key to land at all, and
     * `StarGift` pulls in a large union whose own fillability is a SEPARATE
     * finding that belongs to another lane. The assertion is therefore scoped to
     * this defect: no clue may name the vector child, and the child's rows must
     * carry the values.
     */
    public function test_the_second_measured_declaration_places_too(): void
    {
        [$decomposer] = $this->pipeline();

        $out = $decomposer->decompose('tf_saved_star_gifts', self::ACCOUNT, [
            '_' => 'savedStarGift',
            'date' => 1712345702,
            'gift' => [
                '_' => 'starGift',
                'id' => 555001,
                'stars' => 25,
                'convert_stars' => 50,
                'upgrade_stars' => 0,
            ],
            'collection_id' => [11, 22, 33],
        ]);

        self::assertSame(
            [],
            array_values(array_filter(
                $out['clues'],
                static fn (string $clue): bool => str_contains($clue, 'tf_saved_star_gifts_collection_id'),
            )),
            'no clue may name the vector child: `collection_id:flags.15?Vector<int>` '
            .'(`savedStarGift#41df43fc`, api_full.tl:2024) is the second of exactly TWO scalar-vector '
            .'declarations in the catalog, and before this fix it raised the identical TypeError — so the '
            .'branch, not the table, was the defect',
        );

        self::assertSame(
            [
                ['position' => 0, 'value' => 11],
                ['position' => 1, 'value' => 22],
                ['position' => 2, 'value' => 33],
            ],
            $this->childRows($out['rows'], 'tf_saved_star_gifts_collection_id'),
            'the `Vector<int>` elements reach their positioned rows with their own values, on the one column '
            .'the resolver made for a scalar element',
        );
    }

    /**
     * What the fix must NOT do: silently skip an element it cannot place.
     *
     * This is the arm the brief demands — a clue that says WHAT ACTUALLY
     * HAPPENED. The element here is `null`, which is neither the payload object
     * `childRow()` was written for nor a storable scalar, so there is nowhere to
     * put it; the honest outcome is a named, positioned clue and a page that
     * survives.
     */
    public function test_an_element_that_is_neither_object_nor_scalar_clues_and_survives(): void
    {
        [$decomposer, $catalog] = $this->pipeline();

        $out = $decomposer->decompose(
            'tf_message_actions',
            self::ACCOUNT,
            $this->actionPayload($catalog, [4242, null, 8888]),
        );

        self::assertCount(
            1,
            $out['clues'],
            'exactly ONE clue, for the one element that cannot be placed — the two good ids still place, '
            .'because a single unplaceable element must not cost the page (that is the whole difference '
            .'between this and the TypeError it replaces)',
        );
        self::assertStringContainsString(
            'tf_message_actions_users',
            $out['clues'][0],
            'the clue must name the table whose row was lost, or the operator reading the ledger cannot act on it',
        );
        self::assertStringContainsString(
            "'users'",
            $out['clues'][0],
            'the clue must name the wire FIELD, so the reader knows which fact is under-modelled',
        );
        self::assertStringContainsString(
            'element 1',
            $out['clues'][0],
            'the clue must name the POSITION within the list — three elements and no index is not a diagnosis',
        );
        self::assertStringContainsString(
            'null',
            $out['clues'][0],
            'the clue must state the TYPE it actually got, which is the fact that distinguishes this from a '
            .'decode fault and from a schema fault',
        );

        self::assertSame(
            [
                ['position' => 0, 'value' => 4242],
                ['position' => 2, 'value' => 8888],
            ],
            $this->childRows($out['rows'], 'tf_message_actions_users'),
            'the ORDINALS ARE NOT RENUMBERED — a dropped element must not shift the wire position of every '
            .'element after it, or the surviving rows describe a list that never existed. Position 1 stays a '
            .'hole, and the hole is what the clue reports.',
        );
    }

    /**
     * End to end, against the REAL shipped DDL — the assertion that makes the
     * other three unforgeable. A decomposer that emits a perfect row which the
     * writer then drops as unmapped would satisfy all of them.
     *
     * `tf_message_actions_users` ships `value` as `bigInteger NOT NULL DEFAULT
     * 0` (`2026_09_29_000100_create_tf_mirror_gap_tables.php:215`), so a row
     * that lands with the wrong value is indistinguishable from one that landed
     * on the default — which is why the stored value is read back rather than
     * the insert merely being counted.
     */
    public function test_the_rows_land_in_the_shipped_table_with_their_real_values(): void
    {
        [$decomposer, $catalog, $tl] = $this->pipeline();

        $out = $decomposer->decompose(
            'tf_message_actions',
            self::ACCOUNT,
            $this->actionPayload($catalog, [4242, 7777, 8888]),
        );

        $db = ShippedStoreSchema::capsuleFor(self::MIG_GAP)->getConnection();
        $write = (new MirrorFactWriter)->write($db, $out['rows']);

        self::assertSame([], $write['fkClues'], 'no write clue: the parent key is covered and every value is storable');
        self::assertSame([], $write['valueClues'], 'no value clue: `value` is BIGINT and the ids fit it');

        $stored = $db->table('tf_message_actions_users')->orderBy('position')->get();
        self::assertCount(3, $stored, 'all three members of the users list must reach the shipped child table');
        self::assertSame(
            [4242, 7777, 8888],
            array_map('intval', $stored->pluck('value')->all()),
            'the STORED ids are the wire ids — not 0, which is the column default and would pass a '
            .'row-count-only assertion',
        );
        self::assertSame(
            [self::MESSAGE_ID, self::MESSAGE_ID, self::MESSAGE_ID],
            array_map('intval', $stored->pluck('message_action_id')->all()),
            'the child row links to its parent through the overlayed enclosing-message id, unchanged',
        );
    }

    /**
     * The shape the fix keys off, asserted rather than assumed: a scalar-vector
     * child is EXACTLY the parent key plus a `position` ordinal plus ONE value
     * column. `childElement()` may only wrap an element under that column when
     * there is provably exactly one candidate — otherwise it would guess where
     * the value goes, which is the silent-corruption failure mode.
     *
     * `MirrorResolverKeyMatchesShippedDdlTest` recomputes resolver keys against
     * the DDL for every catalogue table; this pins the column SHAPE the scalar
     * branch depends on, which that suite does not look at.
     */
    public function test_a_scalar_vector_child_has_exactly_one_value_column(): void
    {
        [, $catalog, $tl] = $this->pipeline();
        $resolver = new MirrorTableResolver($catalog, $tl);

        foreach ([
            ['tf_message_actions', 'tf_message_actions_users'],
            ['tf_saved_star_gifts', 'tf_saved_star_gifts_collection_id'],
        ] as [$parent, $childTfName]) {
            $child = $this->childOf($resolver->resolveAll([$parent])[0], $childTfName);

            self::assertNotNull($child, "{$childTfName} must resolve as a child of {$parent}");
            self::assertTrue($child->positioned, "{$childTfName} is a Vector child and carries a position ordinal");
            self::assertSame(
                ['value'],
                $this->valueCandidates($child),
                "{$childTfName} is a scalar-vector child, so `childElement()` wraps a scalar element under the "
                .'single non-key column the resolver made for it (scalarValueColumn(), '
                .'src/Schema/Mirror/MirrorTableResolver.php:534-545). If this ever names more than one column, '
                .'the wrap becomes a guess and must degrade to a clue instead.',
            );
        }
    }

    // ── fixtures & helpers ──────────────────────────────────────────────────

    /**
     * The FACT payload for `tf_message_actions`, produced by the SHIPPED
     * walker from a realistic decoded `messageService` — not hand-built.
     *
     * The walker is in the path for a measured reason, not ceremony: the
     * decomposer is handed the NESTED fact, whose `_` is
     * `messageActionChatCreate`, with the enclosing message's `id` overlaid in
     * by `sources.context` (MirrorNestedSourceWalker::walk(),
     * src/Schema/Mirror/MirrorNestedSourceWalker.php:80-85). Handing
     * `decompose()` the outer `messageService` instead fails the ctor gate at
     * `src/Schema/Mirror/MirrorFactDecomposer.php:150` with "constructor
     * 'messageService' is not in catalog ctors" — a red test for a reason that
     * has nothing to do with the defect.
     *
     * Transcribed from the shipped scheme: `messageService#7a800e0a
     * … id:int … peer_id:Peer … action:MessageAction`
     * (src/Schema/schema/sources/api_full.tl:122) and
     * `messageActionChatCreate#bd47cbad title:string users:Vector<long> =
     * MessageAction` (src/Schema/schema/sources/api_full.tl:145).
     *
     * @param  list<mixed>  $users
     * @return array<string, mixed>
     */
    private function actionPayload(MirrorCatalog $catalog, array $users): array
    {
        $facts = (new MirrorNestedSourceWalker($catalog))->collect([
            '_' => 'messages.messages',
            'messages' => [[
                '_' => 'messageService',
                'id' => self::MESSAGE_ID,
                'peer_id' => ['_' => 'peerChat', 'chat_id' => self::PEER_ID],
                'date' => 1712345701,
                'action' => [
                    '_' => 'messageActionChatCreate',
                    'title' => 'brand new group',
                    'users' => $users,
                ],
            ]],
            'chats' => [],
            'users' => [],
        ]);

        self::assertCount(1, $facts, 'the shipped walker must return exactly the one nested action fact');
        self::assertSame('tf_message_actions', $facts[0]['table']);

        return $facts[0]['payload'];
    }

    /**
     * The child's positioned rows, reduced to the two columns that carry a
     * vector element's identity, so an assertion on them is about the ELEMENT
     * and not about which parent-key columns happen to be on the row today.
     *
     * @param  list<array{table: string, row: array<string, int|string|null>}>  $rows
     * @return list<array{position: int, value: mixed}>
     */
    private function childRows(array $rows, string $childTfName): array
    {
        $out = [];
        foreach ($rows as $row) {
            if ($row['table'] !== $childTfName) {
                continue;
            }
            $out[] = [
                'position' => (int) $row['row']['position'],
                'value' => $row['row']['value'] ?? null,
            ];
        }

        return $out;
    }

    private function childOf(MirrorTable $parent, string $tfName): ?MirrorTable
    {
        foreach ($parent->children as $child) {
            if ($child->tfName === $tfName) {
                return $child;
            }
        }

        return null;
    }

    /**
     * The child's columns that are neither the parent key nor the ordinal.
     *
     * @return list<string>
     */
    private function valueCandidates(MirrorTable $child): array
    {
        $out = [];
        foreach ($child->columns as $column) {
            if ($column->name === 'account_id' || $column->name === 'position') {
                continue;
            }
            if (in_array($column->name, $child->keyColumns, true)) {
                continue;
            }
            $out[] = $column->name;
        }

        return $out;
    }

    /** @return array{MirrorFactDecomposer, MirrorCatalog, TlScheme} */
    private function pipeline(): array
    {
        $tl = TlParser::parseFile(self::TL);
        $catalog = MirrorCatalog::load(self::CATALOG, $tl);

        return [new MirrorFactDecomposer(new MirrorTableResolver($catalog, $tl), $catalog), $catalog, $tl];
    }
}
