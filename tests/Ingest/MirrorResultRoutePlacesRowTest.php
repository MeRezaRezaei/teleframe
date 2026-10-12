<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use MeRezaRezaei\Teleframe\Ingest\MirrorResultIngester;
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
 * The RPC-RESULT ingest route places a mirror row the update route never can.
 *
 * The mirror had exactly one ingest surface, `MirrorIngesterFactory::build()`,
 * and it is fed only by the Redis bus — so it only ever saw UPDATES. A
 * constructor Telegram delivers ONLY as an RPC result was therefore a door in a
 * wall: correct shipped DDL, a correct model, a correct write path, and no data,
 * ever. `tf_bot_infos` is the cleanest instance — `botInfo` (TL 648) is never an
 * update's own top-level constructor, it rides NESTED inside the
 * `users.getFullUser` result (`userFull.bot_info`, TL 268), and `chatFull` /
 * `channelFull` carry it too. So the table had nothing that could ever fill it.
 *
 * The fix is TWO pieces that are useless alone, which is why this test drives
 * both and proves each one:
 *
 *  - a DECLARED NESTED SOURCE in the catalog. `MirrorNestedSourceWalker` follows
 *    only declared edges, so without the declaration the walker is blind to
 *    `bot_info` however rich the payload is. (Asserted as the "before" below.)
 *  - an RPC-RESULT ROUTE (`MirrorResultIngester`). A `userFull` never arrives on
 *    the bus, so no amount of catalog declaration gets one delivered. This is
 *    the half that makes the declaration reachable at all.
 *
 * Note the update callable DOES call `ingestNestedSources()` before its
 * top-level early-return (`MirrorIngesterFactory::build`, line 81), so a
 * `userFull` that somehow reached the bus would place. That is not a
 * substitute: the bus carries updates, and `userFull` is not an update. The
 * defect is that the value never arrives, not that the walker would drop it.
 *
 * WHY THIS SUITE EXECUTES THE SHIPPED MIGRATION: a test that transcribes the
 * DDL is a second copy of it and silently drifts — the disease this project has
 * paid for repeatedly (see `ShippedStoreSchema`'s own docblock). Assertions are
 * about ROWS THAT EXIST, never about what the SQL looks like.
 *
 * Every test runs the write path against the REAL catalog and a catalog derived
 * from the REAL artifact with one declaration removed, so the "before" state is
 * byte-derived from what ships rather than hand-copied fiction. A test that only
 * proves the happy path proves nothing about the defect.
 *
 * Provenance: src/Laravel/Migrations/2026_09_14_200041_create_tf_bots_tables.php
 * (tf_bot_infos, primary(['account_id','id']));
 * schema/sources/TL_telegram_v227.tl line 263 (`userFull#6cbe645`, field
 * `bot_info:flags.3?BotInfo`), line 107 (`chatFull`, `bot_info:flags.3?Vector<BotInfo>`),
 * line 108 (`channelFull`, `bot_info:Vector<BotInfo>`).
 */
final class MirrorResultRoutePlacesRowTest extends TestCase
{
    /** Bots migration — the shipped tf_bot_infos DDL and its child tables. */
    private const MIGRATION = __DIR__.'/../../src/Laravel/Migrations/2026_09_14_200041_create_tf_bots_tables.php';

    /**
     * The fold that drops `tf_bot_infos_description` and adds `tf_bot_infos.description`.
     *
     * 🔴 THIS SUITE MUST EXECUTE IT, and not 200041 alone. The schema a test builds
     * is the schema its assertions describe: with 200041 alone this suite still had
     * `tf_bot_infos_description` and no `tf_bot_infos.description` column, which is a
     * table the shipped dial DROPS. Reading `value('description')` off a parent that
     * has no such column then returns the LITERAL STRING `'description'` on SQLite
     * rather than raising — MEASURED, and it turns a re-pointed assertion into one that
     * can never fail. That is worse than a red test: it is a green one that proves
     * nothing. Running the fold keeps the schema the shipped end-state and keeps the
     * assertion load-bearing.
     */
    private const FOLD = __DIR__.'/../../src/Laravel/Migrations/2026_10_11_000100_fold_bot_infos_description_onto_the_bot_info_fact.php';

    private const TL = __DIR__.'/../../schema/sources/TL_telegram_v227.tl';

    private const CATALOG = __DIR__.'/../../src/Schema/schema/mirror-catalog.json';

    private const ACCOUNT = 7;

    private const BOT = 424242;

    /**
     * A REALISTIC `users.getFullUser` result for a bot, as the transport
     * decodes it (TL 263). `bot_info` is flags.3-gated, so it is present only
     * on a bot's full user — which is precisely why no update ever carries it.
     * The flagless nulls are omitted, exactly as a real decode omits an absent
     * optional field.
     *
     * @var array<string, mixed>
     */
    private const GET_FULL_USER_RESULT = [
        '_' => 'userFull',
        'id' => self::BOT,
        'about' => 'a bot that does things',
        'bot_info' => [
            '_' => 'botInfo',
            'user_id' => self::BOT,
            'description' => 'does things, loudly',
            'commands' => [
                ['_' => 'botCommand', 'command' => 'start', 'description' => 'begin'],
            ],
            'privacy_policy_url' => 'https://example.test/privacy',
            'has_preview_medias' => false,
        ],
    ];

    public function test_a_bot_info_row_places_from_a_real_rpc_result_where_no_row_existed(): void
    {
        // BEFORE — the catalog cannot say a result-only table is filled from
        // anywhere, so the walker finds nothing and the row never places. This
        // is the state the tree shipped: correct DDL, correct model, no data.
        $preFix = ShippedStoreSchema::capsuleFor(self::MIGRATION, self::FOLD)->getConnection();
        $before = $this->route($this->catalogWithoutResultSources())->ingest(
            $preFix,
            self::ACCOUNT,
            self::GET_FULL_USER_RESULT,
        );
        self::assertSame(
            0,
            $preFix->table('tf_bot_infos')->count(),
            'without a declared nested source the bot-info row must NOT place — otherwise this test proves nothing',
        );
        self::assertSame(0, $before['inserted']);

        // AFTER — the shipped catalog, driven through the RPC-result route.
        $db = ShippedStoreSchema::capsuleFor(self::MIGRATION, self::FOLD)->getConnection();
        $after = $this->route($this->shippedCatalog())->ingest(
            $db,
            self::ACCOUNT,
            self::GET_FULL_USER_RESULT,
        );
        self::assertSame([], $after['fkClues'], 'a bot_info from a real result must place with no write clue at all');

        $row = $db->table('tf_bot_infos')->where('account_id', self::ACCOUNT)->first();
        self::assertNotNull($row, 'the bot-info row must now place');
        self::assertSame(self::BOT, (int) $row->id, 'the key must be derived from user_id, not invented');
        self::assertSame(0, (int) $row->has_preview_medias, 'the declared bool must be stored as 0, not dropped');

        // The children are what make this a real fact rather than a stub row:
        // the PK is (account_id, id) and every child hangs off that derived key.
        self::assertSame(
            self::BOT,
            (int) $db->table('tf_bot_infos_user_id')->where('account_id', self::ACCOUNT)->value('user_id'),
            'the 1:1 child must be keyed by the SAME derived id',
        );
        self::assertSame(
            'does things, loudly',
            $db->table('tf_bot_infos')->where('account_id', self::ACCOUNT)->value('description'),
            'the description text must be the real payload text, on the folded parent column',
        );
        self::assertSame(
            'https://example.test/privacy',
            $db->table('tf_bot_infos_privacy_policy_url')->where('account_id', self::ACCOUNT)->value('privacy_policy_url'),
            'the NOT NULL child must place, proving the whole fact decomposed',
        );
    }

    /**
     * A `channelFull` result carries `bot_info` as a VECTOR, and the route must
     * handle that shape too — a different walker branch (isVector) from the
     * single-object `userFull` case above, and the shape a real channel fetch
     * returns.
     */
    public function test_a_vector_carrier_result_places_one_row_per_bot_info(): void
    {
        $db = ShippedStoreSchema::capsuleFor(self::MIGRATION, self::FOLD)->getConnection();

        $this->route($this->shippedCatalog())->ingest($db, self::ACCOUNT, [
            '_' => 'channelFull',
            'id' => 1001234567890,
            'about' => 'a channel',
            'participants_count' => 3,
            'bot_info' => [
                [
                    '_' => 'botInfo',
                    'user_id' => 111,
                    'description' => 'first',
                    'privacy_policy_url' => 'https://example.test/one',
                    'has_preview_medias' => true,
                ],
                [
                    '_' => 'botInfo',
                    'user_id' => 222,
                    'description' => 'second',
                    'privacy_policy_url' => 'https://example.test/two',
                    'has_preview_medias' => false,
                ],
            ],
        ]);

        self::assertSame(2, $db->table('tf_bot_infos')->count(), 'each vector element must place its own row');
        self::assertSame(
            [111, 222],
            $db->table('tf_bot_infos')->orderBy('id')->pluck('id')->map(fn ($id): int => (int) $id)->all(),
            'rows must be keyed by each element\'s own user_id',
        );
        self::assertSame(
            1,
            (int) $db->table('tf_bot_infos')->where('id', 111)->value('has_preview_medias'),
            'a true bool must be stored as 1',
        );
    }

    /**
     * The empirical rule this whole declaration set rests on, pinned as a
     * regression: a `sources` parent must name a wire CONSTRUCTOR, never a
     * METHOD name.
     *
     * `payments.savedStarGifts#95f389b1` and `users.userFull#6cbe645` are
     * CONSTRUCTORS — they have a bare TL id and a payload, and they are the
     * objects the decoder hands back. `payments.getSavedStarGifts` and
     * `users.getFullUser` are METHODS: they exist in the `.tl` file only
     * because the schema file also declares RPCs, they never appear in a
     * decoded payload, and `assertNestedSources()` looks constructors up in the
     * scheme, so a method name can never resolve. Building the declaration set
     * from result constructors — not from method names — is what makes the
     * remaining category-C tables wireable at all.
     *
     * This is asserted rather than asserted-in-prose because getting it wrong is
     * silent: the catalog would load, the row would simply never land.
     */
    public function test_a_source_naming_a_method_instead_of_a_constructor_is_rejected_at_load(): void
    {
        $this->expectException(MirrorSchemaException::class);
        $this->expectExceptionMessage("no constructor carries a field of type SavedStarGift named 'gifts'");

        $this->loadVariant([
            'tf_saved_star_gifts' => [['ctor' => 'payments.getSavedStarGifts', 'field' => 'gifts']],
        ]);
    }

    /**
     * The same declaration expressed with the RESULT CONSTRUCTOR does resolve.
     * Without this half, the test above would only prove the mechanism rejects
     * things — not that the correct form is reachable.
     */
    public function test_a_source_naming_the_result_constructor_resolves(): void
    {
        $catalog = $this->loadVariant([
            'tf_saved_star_gifts' => [['ctor' => 'payments.savedStarGifts', 'field' => 'gifts']],
        ]);

        self::assertNotEmpty(
            $catalog->nestedSourcesFor('payments.savedStarGifts'),
            'the result constructor must resolve as a carrier',
        );
    }

    /**
     * A result that yields no declared fact writes nothing. Absence of a fact
     * is absence of a row, never a placeholder row.
     */
    public function test_a_result_carrying_no_declared_fact_writes_nothing(): void
    {
        $db = ShippedStoreSchema::capsuleFor(self::MIGRATION, self::FOLD)->getConnection();

        $result = $this->route($this->shippedCatalog())->ingest($db, self::ACCOUNT, [
            '_' => 'messages.affectedMessages',
            'pts' => 5,
            'pts_count' => 1,
        ]);

        self::assertSame(0, $result['inserted']);
        self::assertSame(0, $db->table('tf_bot_infos')->count());
    }

    /**
     * Build the real result route from the REAL catalog — the same triple
     * `MirrorIngesterFactory::buildResultRoute()` builds, so the test exercises
     * the shipped collaborator wiring rather than a private arrangement.
     */
    private function route(MirrorCatalog $catalog): MirrorResultIngester
    {
        $scheme = TlParser::parseFile(self::TL);

        return new MirrorResultIngester(
            new MirrorNestedSourceWalker($catalog),
            new MirrorFactDecomposer(new MirrorTableResolver($catalog, $scheme), $catalog),
            new MirrorFactWriter,
        );
    }

    private function shippedCatalog(): MirrorCatalog
    {
        return MirrorCatalog::load(self::CATALOG, TlParser::parseFile(self::TL));
    }

    /**
     * The real catalog with one table's nested-source declarations removed — the
     * state the tree shipped before this fix, derived from the real file so it
     * cannot drift into a hand-copied fiction.
     */
    private function catalogWithoutResultSources(): MirrorCatalog
    {
        $entries = json_decode((string) file_get_contents(self::CATALOG), true, 512, JSON_THROW_ON_ERROR);
        unset($entries['tf_bot_infos']['sources']);

        return $this->loadEntries($entries);
    }

    /**
     * Load the REAL catalog with one entry's nested-source declaration replaced,
     * so every negative case is exercised against the shipped file rather than a
     * transcribed copy of it.
     *
     * @param  array<string, mixed>  $override
     */
    private function loadVariant(array $override): MirrorCatalog
    {
        $entries = json_decode((string) file_get_contents(self::CATALOG), true, 512, JSON_THROW_ON_ERROR);
        foreach ($override as $table => $sources) {
            $entries[$table]['sources'] = $sources;
        }

        return $this->loadEntries($entries);
    }

    /**
     * @param  array<string, array>  $entries
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
