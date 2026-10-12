<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactDecomposer;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactWriter;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorNestedSourceWalker;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorSchemaException;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorTableResolver;
use MeRezaRezaei\Teleframe\Tests\Store\ShippedStoreSchema;
use PHPUnit\Framework\TestCase;

/**
 * A mirror table the ingest path can never route, because the object it stores
 * only ever arrives NESTED inside another constructor.
 *
 * The ingest seam routes on a payload's TOP-LEVEL `'_'`
 * (`MirrorIngesterFactory`): `tableForCtor($ctor)`, and an early return when
 * that is null. So a TL object is only stored when it IS the update's own
 * constructor. `channelParticipant` never is — it rides inside
 * `updateChannelParticipant.new_participant` (TL 403) — so `tf_channel_participants`
 * was a door in a wall: shipped DDL, a working write path, and no data, ever.
 *
 * The fix is a DECLARED NESTED SOURCE in the catalog: the entry states that it
 * is filled from `updateChannelParticipant.new_participant` /
 * `.prev_participant`, and the walker follows exactly those declared edges.
 *
 * It is NOT enough to route the row, and that is the second half of the defect:
 * the shipped table keys on `primary(['account_id','channel_id','user_id'])`
 * while the `channelParticipant` constructor carries only `user_id` — the
 * `channel_id` lives on the ENCLOSING update. The declaration therefore also
 * names ENCLOSING CONTEXT (`"context": ["channel_id"]`), which the walker
 * overlays under the child's own fields. Note this is why the RPC wrapper is
 * NOT usable: in `channels.channelParticipants` (TL 782) the sibling `chats`
 * is the channel's DISPLAY chat list, not a per-participant id, so that parent
 * genuinely cannot supply the key. `updateChannelParticipant` carries a real
 * `channel_id:long` of its own, which is why this table is provable and
 * `channels.channelParticipants` is not.
 *
 * Why this suite EXECUTES the shipped migration: a test that transcribes the
 * DDL is a second copy of it and drifts — the disease this project has paid for
 * repeatedly (see `ShippedStoreSchema`'s own docblock). Assertions are about
 * ROWS THAT EXIST, never about what the SQL looks like.
 *
 * Every test runs the write path TWICE: once with the real catalog JSON and
 * the `sources`/`context` declarations STRIPPED (the byte-derived "before"),
 * which must DROP the row, and once with the shipped catalog, which must LAND
 * it. A test that only proves the happy path proves nothing about the defect.
 *
 * Provenance: src/Laravel/Migrations/2026_09_14_200031_create_tf_channel_participants_tables.php
 * (tf_channel_participants), schema/sources/TL_telegram_v227.tl line 403
 * (updateChannelParticipant) and line 766 (channelParticipant).
 */
final class MirrorNestedSourcePlacesRowTest extends TestCase
{
    /** Channel-participants migration — the shipped tf_channel_participants DDL. */
    private const MIGRATION = __DIR__.'/../../src/Laravel/Migrations/2026_09_14_200031_create_tf_channel_participants_tables.php';

    private const TL = __DIR__.'/../../schema/sources/TL_telegram_v227.tl';

    private const CATALOG = __DIR__.'/../../src/Schema/schema/mirror-catalog.json';

    private const ACCOUNT = 7;

    private const CHANNEL = 1001234567890;

    /**
     * `updateChannelParticipant` exactly as the wire decodes it (TL 403): the
     * participant is NESTED, and the channel_id that keys the shipped table is
     * a sibling of it on the update — not inside the participant.
     */
    private const UPDATE = [
        '_' => 'updateChannelParticipant',
        'via_chatlist' => false,
        'channel_id' => self::CHANNEL,
        'date' => 1700000000,
        'actor_id' => 42,
        'user_id' => 42,
        'new_participant' => [
            '_' => 'channelParticipant',
            'user_id' => 42,
            'date' => 1700000000,
            'subscription_until_date' => 1800000000,
        ],
        'prev_participant' => [
            '_' => 'channelParticipantSelf',
            'user_id' => 7,
            'date' => 1690000000,
        ],
        'qts' => 5,
    ];

    public function test_nested_participant_row_places_where_top_level_routing_never_will(): void
    {
        // BEFORE — the catalog cannot say where a nested-only table is filled
        // from, so the walker finds nothing and the row never places.
        $preFix = ShippedStoreSchema::capsuleFor(self::MIGRATION)->getConnection();
        $before = $this->ingest($preFix, $this->catalogWithoutNestedSources(), self::UPDATE);
        self::assertSame(
            0,
            $preFix->table('tf_channel_participants')->count(),
            'without a declared nested source the participant row must NOT place — otherwise this test proves nothing',
        );
        self::assertSame(0, $before['inserted']);

        // AFTER — the shipped catalog with the nested-source declaration.
        $db = ShippedStoreSchema::capsuleFor(self::MIGRATION)->getConnection();
        $after = $this->ingest($db, $this->shippedCatalog(), self::UPDATE);
        self::assertSame([], $after['clues'], 'a nested participant must place with no clue at all');
        self::assertSame([], $after['fkClues'], 'a nested participant must place with no write clue at all');

        $rows = $db->table('tf_channel_participants')->where('account_id', self::ACCOUNT)->orderBy('user_id')->get();
        self::assertCount(2, $rows, 'both the new and the previous participant must place');

        // The key that proves the ENCLOSING CONTEXT: channel_id is on the
        // update, not on the participant, and it is part of the shipped PK.
        $new = $rows->firstWhere('user_id', 42);
        self::assertNotNull($new, 'the new_participant row must be present');
        self::assertSame(self::CHANNEL, (int) $new->channel_id, 'channel_id must come from the enclosing update');
        self::assertSame('channelParticipant', $new->constructor, 'the real ctor must be the discriminator');
        self::assertSame(1700000000, (int) $new->date, 'the participant\'s own fields must still be stored');

        $prev = $rows->firstWhere('user_id', 7);
        self::assertNotNull($prev, 'the prev_participant row must be present');
        self::assertSame(self::CHANNEL, (int) $prev->channel_id, 'context must reach the prev_participant source too');
        self::assertSame('channelParticipantSelf', $prev->constructor, 'a different ctor must not be coerced');
    }

    /**
     * The declared source must be CHECKED, not trusted. A source naming a
     * field the parent constructor does not carry is exactly the papering-over
     * this mechanism exists to prevent, so it must fail at catalog LOAD.
     */
    public function test_a_source_field_the_parent_cannot_carry_is_rejected_at_load(): void
    {
        $this->expectExceptionMessage("no constructor carries a field of type ChannelParticipant named 'nope'");

        $this->loadVariant(['sources' => [['ctor' => 'updateChannelParticipant', 'field' => 'nope']]]);
    }

    /**
     * Same guard on the TYPE: naming a field that exists but carries something
     * else is the more dangerous half, because the walk would then hand the
     * wrong constructor to the table.
     */
    public function test_a_source_field_of_the_wrong_type_is_rejected_at_load(): void
    {
        $this->expectExceptionMessage("is declared to carry ChannelParticipant, but updateChannelParticipant declares it as long");

        $this->loadVariant(['sources' => [['ctor' => 'updateChannelParticipant', 'field' => 'channel_id']]]);
    }

    /**
     * An enclosing-context field that the parent does not carry would overlay
     * a value the wire never produced, and the shipped PK would then key on
     * nothing. Rejected at load for the same reason.
     */
    public function test_a_context_field_the_parent_cannot_carry_is_rejected_at_load(): void
    {
        $this->expectExceptionMessage("declares context 'not_a_param', which is not a parameter of updateChannelParticipant");

        $this->loadVariant([
            'sources' => [[
                'ctor' => 'updateChannelParticipant',
                'field' => 'new_participant',
                'context' => ['not_a_param'],
            ]],
        ]);
    }

    /**
     * Naming a SUPERTYPE is refused when its constructors disagree, and the
     * refusal says what to do instead. This is not a formality: `Update` has
     * `updateChannelParticipant.new_participant` (a ChannelParticipant) AND
     * `updateChatParticipant.new_participant` (a ChatParticipant) — the same
     * field NAME carrying different types. A type-level claim cannot be true of
     * all its carriers, and following it blindly would store the wrong object.
     */
    public function test_a_supertype_source_whose_carriers_disagree_is_rejected_at_load(): void
    {
        $this->expectExceptionMessage('name the concrete constructor with "ctor" instead of the whole type');

        $this->loadVariant([
            'sources' => [['type' => 'Update', 'field' => 'new_participant']],
        ]);
    }

    /**
     * Exactly one of "ctor" or "type" must be named — an ambiguous declaration
     * is a catalog error, not a fallback.
     */
    public function test_a_source_naming_no_parent_is_rejected_at_load(): void
    {
        $this->expectExceptionMessage('must name exactly one parent');

        $this->loadVariant(['sources' => [['field' => 'new_participant']]]);
    }

    /**
     * The walker must not wander: a payload whose constructor carries no
     * declared source produces no facts, even when it is full of objects that
     * look like mirror facts. Only declared edges are followed.
     */
    public function test_the_walker_follows_declared_edges_only(): void
    {
        $walker = new MirrorNestedSourceWalker($this->shippedCatalog());

        // A `channelParticipant` arrives at the TOP level: routable by ctor
        // already, and carrying no declared source of its own.
        $topLevel = ['_' => 'channelParticipant', 'user_id' => 42, 'date' => 1700000000];
        self::assertSame([], $walker->collect($topLevel));

        // An update with no declared source yields nothing either.
        $unrelated = ['_' => 'updateDeleteMessages', 'messages' => [1, 2, 3], 'pts' => 5, 'pts_count' => 5];
        self::assertSame([], $walker->collect($unrelated));
    }

    /**
     * Run the real write path for a whole update and report what happened.
     *
     * @param  \Illuminate\Database\ConnectionInterface  $db
     * @param  array<string, mixed>  $update
     * @return array{clues: list<string>, fkClues: list<string>, inserted: int}
     */
    private function ingest($db, MirrorCatalog $catalog, array $update): array
    {
        $scheme = TlParser::parseFile(self::TL);
        $resolver = new MirrorTableResolver($catalog, $scheme);
        $decomposer = new MirrorFactDecomposer($resolver, $catalog);
        $writer = new MirrorFactWriter;

        $clues = [];
        $fkClues = [];
        $inserted = 0;

        foreach ((new MirrorNestedSourceWalker($catalog))->collect($update) as $fact) {
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
     * Load the REAL catalog with one entry's nested-source declaration
     * replaced — so every negative case is exercised against the shipped file,
     * and a "before" state is derived from the real artifact rather than
     * hand-copied.
     *
     * @param  array<string, mixed>  $override
     */
    private function loadVariant(array $override): MirrorCatalog
    {
        $entries = json_decode((string) file_get_contents(self::CATALOG), true, 512, JSON_THROW_ON_ERROR);
        $entries['tf_channel_participants'] = [...$entries['tf_channel_participants'], ...$override];

        $path = tempnam(sys_get_temp_dir(), 'teleframe-catalog-').'.json';
        file_put_contents($path, (string) json_encode($entries, JSON_THROW_ON_ERROR));

        try {
            return MirrorCatalog::load($path, TlParser::parseFile(self::TL));
        } finally {
            @unlink($path);
        }
    }

    /**
     * The real catalog with the nested-source declarations REMOVED — the state
     * the tree shipped before this fix, derived from the real file so it cannot
     * drift into a fiction.
     */
    private function catalogWithoutNestedSources(): MirrorCatalog
    {
        $entries = json_decode((string) file_get_contents(self::CATALOG), true, 512, JSON_THROW_ON_ERROR);
        unset($entries['tf_channel_participants']['sources']);

        $path = tempnam(sys_get_temp_dir(), 'teleframe-catalog-').'.json';
        file_put_contents($path, (string) json_encode($entries, JSON_THROW_ON_ERROR));

        try {
            return MirrorCatalog::load($path, TlParser::parseFile(self::TL));
        } finally {
            @unlink($path);
        }
    }
}
