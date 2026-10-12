<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactDecomposer;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactWriter;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorNestedSourceWalker;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorTableResolver;
use MeRezaRezaei\Teleframe\Tests\Store\ShippedStoreSchema;
use PHPUnit\Framework\TestCase;

/**
 * `messages.getSavedDialogs` is PAGINATED, and its result type has THREE
 * constructors. The catalog declared only ONE of them, so the mirror stored
 * page 1 and silently dropped every page after it.
 *
 * `messages.SavedDialogs` in the live scheme (TL 227) is:
 *   - `messages.savedDialogs`            (line 1809) — `dialogs:Vector<SavedDialog>`
 *   - `messages.savedDialogsSlice`       (line 1810) — `count:int dialogs:Vector<SavedDialog> ...`
 *   - `messages.savedDialogsNotModified` (line 1811) — `count:int`  ← NO `dialogs`
 *
 * The walker follows DECLARED edges only, so a page whose constructor is not
 * declared is a page that places nothing — no clue, no error, no row. That is
 * the defect: page 1 mirrored, every later page was lost with no signal.
 *
 * `messages.savedDialogsNotModified` must NOT be declared, and that is the
 * principled line rather than an omission: the ctor carries no `dialogs`
 * field, so a source claiming it would be rejected at catalog LOAD by
 * `MirrorCatalog::assertNestedSources` (src/Schema/Mirror/MirrorCatalog.php:341)
 * — the guard exists precisely to stop a declaration papering over a field
 * the payload cannot carry. This suite pins that too, by asserting the
 * rejection rather than the silence.
 *
 * Why this suite EXECUTES the shipped migration: a test that transcribes the
 * DDL is a second copy of it and drifts (see `ShippedStoreSchema`'s own
 * docblock). Assertions are about ROWS THAT EXIST, never about SQL text.
 *
 * Every test runs the write path TWICE: once with the real catalog JSON and
 * the `messages.savedDialogsSlice` source STRIPPED (the byte-derived
 * "before"), which must DROP the row, and once with the shipped catalog, which
 * must LAND it. A test that only proves the happy path proves nothing.
 *
 * Provenance: src/Laravel/Migrations/2026_09_14_200050_create_tf_misc_tables.php
 * (tf_saved_dialogs), schema/sources/TL_telegram_v227.tl lines 1807-1811.
 */
final class SavedDialogsSlicePlacesRowTest extends TestCase
{
    /** Misc-tables migration — the shipped tf_saved_dialogs DDL. */
    private const MIGRATION = __DIR__.'/../../src/Laravel/Migrations/2026_09_14_200050_create_tf_misc_tables.php';

    private const TL = __DIR__.'/../../schema/sources/TL_telegram_v227.tl';

    private const CATALOG = __DIR__.'/../../src/Schema/schema/mirror-catalog.json';

    private const ACCOUNT = 7;

    /**
     * Page 2 of `messages.getSavedDialogs`, exactly as the wire decodes it
     * (TL 1810): the slice carries its own `count` and then the `dialogs`
     * vector the very same way page 1 does.
     */
    private const SLICE = [
        '_' => 'messages.savedDialogsSlice',
        'count' => 2,
        'dialogs' => [
            [
                '_' => 'savedDialog',
                'pinned' => true,
                'peer' => [
                    '_' => 'peerUser',
                    'user_id' => 4242,
                    'access_hash' => 999888777,
                ],
                'top_message' => 777,
            ],
            [
                '_' => 'monoForumDialog',
                'unread_mark' => true,
                'peer' => [
                    '_' => 'peerChannel',
                    'channel_id' => 1001234567890,
                    'access_hash' => 555,
                ],
                'top_message' => 888,
            ],
        ],
        'messages' => [],
        'chats' => [],
        'users' => [],
    ];

    /**
     * The defect, as it presented: page 1 lands, page 2 is silently lost. The
     * two runs use the SAME payload shape and differ only in which parent
     * constructor the catalog declares.
     */
    public function test_a_slice_page_places_rows_where_the_single_declared_ctor_places_none(): void
    {
        // BEFORE — only `messages.savedDialogs` is declared, so a SLICE page
        // matches no declared source and inserts 0 rows. No clue is emitted
        // either, which is exactly why the loss was silent.
        $preFixDb = ShippedStoreSchema::capsuleFor(self::MIGRATION)->getConnection();
        $before = $this->ingest($preFixDb, $this->catalogWithoutSliceSource(), self::SLICE);
        self::assertSame(
            0,
            $preFixDb->table('tf_saved_dialogs')->count(),
            'without a declared slice source the page must NOT place — otherwise this test proves nothing',
        );
        self::assertSame(0, $before['inserted'], 'the undeclared page must insert nothing');
        self::assertSame([], $before['clues'], 'and must emit no clue at all — the loss was silent');

        // AFTER — the shipped catalog, which declares the slice carrier too.
        $db = ShippedStoreSchema::capsuleFor(self::MIGRATION)->getConnection();
        $after = $this->ingest($db, $this->shippedCatalog(), self::SLICE);
        self::assertGreaterThanOrEqual(1, $after['inserted'], 'a slice page must now place at least one row');
        self::assertSame([], $after['clues'], 'a slice page must place with no clue at all');
        self::assertSame([], $after['fkClues'], 'a slice page must place with no write clue at all');

        $rows = $db->table('tf_saved_dialogs')->where('account_id', self::ACCOUNT)->get();
        self::assertCount(2, $rows, 'BOTH dialogs on the slice page must place, not just the first');

        $user = $rows->firstWhere('peer_id', 4242);
        self::assertNotNull($user, 'the peerUser dialog must be present');
        self::assertSame('savedDialog', $user->constructor, 'the real ctor must be the discriminator');
        self::assertSame(1, (int) $user->peer_type, 'peer_type must be expanded from the peer field');
        self::assertSame(777, (int) $user->top_message, 'top_message must come from the child payload');
        self::assertSame(1, (int) $user->pinned, 'a set flag must be stored');

        $channel = $rows->firstWhere('peer_id', 1001234567890);
        self::assertNotNull($channel, 'the monoForumDialog peer must be present');
        self::assertSame('monoForumDialog', $channel->constructor, 'a different ctor must not be coerced');
        self::assertSame(888, (int) $channel->top_message, 'the second row\'s own fields must be stored');
    }

    /**
     * The exclusion must be CHECKED, not merely absent. `messages.
     * savedDialogsNotModified` is a real sibling of the same result type, and
     * an implementer "covering all three constructors" would reach for it —
     * but it carries NO `dialogs` field, so declaring it is a catalog error
     * that `MirrorCatalog::assertNestedSources` must reject at LOAD. Without
     * this test the correct exclusion is indistinguishable from an oversight.
     */
    public function test_declaring_the_not_modified_sibling_is_rejected_at_load(): void
    {
        $this->expectExceptionMessage("no constructor carries a field of type SavedDialog named 'dialogs'");

        $this->catalogWithExtraSource('messages.savedDialogsNotModified');
    }

    /**
     * Run the real write path for a whole payload and report what happened.
     *
     * @param  \Illuminate\Database\ConnectionInterface  $db
     * @param  array<string, mixed>  $payload
     * @return array{clues: list<string>, fkClues: list<string>, inserted: int}
     */
    private function ingest($db, MirrorCatalog $catalog, array $payload): array
    {
        $scheme = TlParser::parseFile(self::TL);
        $resolver = new MirrorTableResolver($catalog, $scheme);
        $decomposer = new MirrorFactDecomposer($resolver, $catalog);
        $writer = new MirrorFactWriter;

        $clues = [];
        $fkClues = [];
        $inserted = 0;

        foreach ((new MirrorNestedSourceWalker($catalog))->collect($payload) as $fact) {
            $decomposed = $decomposer->decompose($fact['table'], self::ACCOUNT, $fact['payload']);
            $write = $writer->write($db, $decomposed['rows']);
            $clues = [...$clues, ...$decomposed['clues']];
            $fkClues = [...$fkClues, ...$write['fkClues']];
            $inserted += $write['inserted'];
        }

        return ['clues' => $clues, 'fkClues' => $fkClues, 'inserted' => $inserted];
    }

    private function shippedCatalog(): MirrorCatalog
    {
        return MirrorCatalog::load(self::CATALOG, TlParser::parseFile(self::TL));
    }

    /**
     * The real catalog with the slice source REMOVED — the state the tree
     * shipped before this fix, derived from the real file so it cannot drift
     * into a fiction.
     */
    private function catalogWithoutSliceSource(): MirrorCatalog
    {
        $entries = json_decode((string) file_get_contents(self::CATALOG), true, 512, JSON_THROW_ON_ERROR);
        $entries['tf_saved_dialogs']['sources'] = array_values(array_filter(
            $entries['tf_saved_dialogs']['sources'],
            static fn (array $source): bool => ($source['ctor'] ?? null) !== 'messages.savedDialogsSlice',
        ));

        return $this->loadEntries($entries);
    }

    /**
     * The real catalog with one EXTRA source appended — so a negative case is
     * exercised against the shipped file rather than a hand-copied catalog.
     */
    private function catalogWithExtraSource(string $ctor): MirrorCatalog
    {
        $entries = json_decode((string) file_get_contents(self::CATALOG), true, 512, JSON_THROW_ON_ERROR);
        $entries['tf_saved_dialogs']['sources'][] = ['ctor' => $ctor, 'field' => 'dialogs'];

        return $this->loadEntries($entries);
    }

    /**
     * @param  array<string, mixed>  $entries
     */
    private function loadEntries(array $entries): MirrorCatalog
    {
        $path = tempnam(sys_get_temp_dir(), 'teleframe-catalog-').'.json';
        file_put_contents($path, (string) json_encode($entries, JSON_THROW_ON_ERROR));

        try {
            return MirrorCatalog::load($path, TlParser::parseFile(self::TL));
        } finally {
            @unlink($path);
        }
    }
}
