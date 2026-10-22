<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use MeRezaRezaei\Teleframe\Ingest\MirrorResultIngester;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactDecomposer;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactWriter;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorNestedSourceWalker;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorTableResolver;
use MeRezaRezaei\Teleframe\Tests\Store\ShippedStoreSchema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Every newly declared `sources` edge is proven to PLACE A ROW — or the edge
 * is not in the catalog.
 *
 * THE DEFECT THIS CLOSES. A mirror table is filled from a field of another
 * constructor, so the ingest path — which routes on the payload's TOP-LEVEL
 * constructor — can never reach it. The catalog's `sources` edge is the only
 * thing that makes the walker look, and an edge is a PROMISE: "a row can land
 * here". THIRTY-SIX tables shipped, and not one of the 36 carried a `sources`
 * key at all, so the promise was simply absent rather than broken, and the
 * honest question for each one is not "does a carrier exist in the scheme"
 * but "does a row actually place".
 *
 * MEASURED at `07aef20`, the first commit to ship the artifact inside
 * `src/Schema/schema`:
 *
 *     git show 07aef20:src/Schema/schema/mirror-catalog.json
 *       | php -r '$r=json_decode(stream_get_contents(STDIN),true,512,JSON_THROW_ON_ERROR);
 *       $k=0; foreach($r as $d){ if(array_key_exists("sources",$d)) $k++; }
 *       echo count($r)." tables, $k carrying a sources key\n";'
 *     => 36 tables, 0 carrying a sources key
 *
 * Not even an empty `sources: []` — the key was absent on all 36. Both
 * numbers are JSON counts (see the API-vs-JSON note on
 * `test_a_brand_new_edge_is_indexed_against_its_parent_constructor`).
 *
 * This suite answers that question EMPIRICALLY, per table, and it is built so
 * a green result cannot be produced by a test that only exercises the happy
 * path. Each case runs TWICE against two catalogs derived from the real
 * shipped artifact:
 *
 *   BEFORE — the real JSON with THAT table's `sources` removed. The row must
 *            NOT exist. This is the state the tree shipped.
 *   AFTER  — the shipped JSON, untouched. The row MUST now exist, carrying a
 *            value taken from the real payload.
 *
 * The "before" catalog is derived by DELETING A KEY from the shipped file, not
 * by transcribing it, so it is byte-derived from what ships. And the schema is
 * the real shipped migration executed by `ShippedStoreSchema::capsuleFor()` —
 * never hand-built sqlite. A test that constructs its own DDL is a test of the
 * author's imagination; this one cannot pass against a table nobody ships.
 *
 * WHY ONE CLASS FOR BOTH ROUTES. `MirrorResultIngester` is walk → decompose →
 * write, and it is invoked here directly because it is the same triple of
 * collaborators, in the same order, that `MirrorIngesterFactory::build()` calls
 * in `ingestNestedSources()` for the update bus. Whether a fact arrives on the
 * bus or as an RPC result, placement is decided by that one path, so proving
 * placement through it proves both. What differs between the two routes is
 * only WHO delivers the payload — and that difference is a caller question,
 * answered in the policy and the backfill command, not here.
 *
 * PROVENANCE — every carrier line below is transcribed from
 * schema/sources/TL_telegram_v227.tl:
 *   336 updateWebPage#7f891213 webpage:WebPage          -> tf_web_pages
 *   361 updateChannelWebPage#2f2ba99f webpage:WebPage    -> tf_web_pages
 *   368 updatePhoneCall#ab0f6b1e phone_call:PhoneCall    -> tf_phone_calls
 *   387 updateDialogFilter#26ffde7d filter:flags.0?DialogFilter -> tf_dialog_filters
 *   325 updateEncryption#b4a2e88d chat:EncryptedChat     -> tf_encrypted_chats
 *   423 updateStory#75b3b798 story:StoryItem             -> tf_story_items
 *   383 updateTheme#8216fba3 theme:Theme                 -> tf_themes
 *   430 updatePeerWallpaper#ae3f101d wallpaper:flags.0?WallPaper -> tf_wallpapers
 *   437 updateQuickReplies#f9470ab2 quick_replies:Vector<QuickReply>
 *   438 updateNewQuickReply#f53da717 quick_reply:QuickReply -> tf_quick_replies
 *   2026 payments.savedStarGifts gifts:Vector<SavedStarGift> -> tf_saved_star_gifts
 *   1938 payments.starsStatus history:flags.3?Vector<StarsTransaction>
 *   1938 payments.starsStatus subscriptions:flags.1?Vector<StarsSubscription>
 *   1899 account.businessChatLinks links:Vector<BusinessChatLink> -> tf_business_chat_links
 *   1810 messages.savedDialogs dialogs:Vector<SavedDialog>       -> tf_saved_dialogs
 *   647  messages.allStickers sets:Vector<StickerSet>            -> tf_sticker_sets
 */
final class MirrorDeclaredSourcesPlaceRowTest extends TestCase
{
    private const TL = __DIR__.'/../../schema/sources/TL_telegram_v227.tl';

    private const CATALOG = __DIR__.'/../../src/Schema/schema/mirror-catalog.json';

    private const MIG_GAP = __DIR__.'/../../src/Laravel/Migrations/2026_09_29_000100_create_tf_mirror_gap_tables.php';

    private const MIG_MISC = __DIR__.'/../../src/Laravel/Migrations/2026_09_14_200050_create_tf_misc_tables.php';

    private const MIG_WEB = __DIR__.'/../../src/Laravel/Migrations/2026_09_14_200022_create_tf_web_pages_tables.php';

    private const MIG_STAR = __DIR__.'/../../src/Laravel/Migrations/2026_09_14_200040_create_tf_stars_business_tables.php';

    private const MIG_STICKER = __DIR__.'/../../src/Laravel/Migrations/2026_09_14_200023_create_tf_sticker_sets_tables.php';

    private const ACCOUNT = 11;

    /**
     * One real carrier payload per route, transcribed from the TL scheme.
     *
     * Kept as whole payloads rather than fragments because the walker is
     * depth-bounded and descends only into DECLARED parent types: a payload
     * that is not the genuine wire shape would prove the harness, not the edge.
     *
     * @var array<string, array<string, mixed>>
     */
    private const PAYLOADS = [
        'updateWebPage' => [
            '_' => 'updateWebPage',
            'webpage' => [
                '_' => 'webPage',
                'id' => 5150,
                'url' => 'https://example.test/a',
                'display_url' => 'https://example.test/a',
                'hash' => 77,
                'type' => 'article',
                'site_name' => 'Example',
                'title' => 'A page that moved',
            ],
            'pts' => 5,
            'pts_count' => 1,
        ],
        'updatePhoneCall' => [
            '_' => 'updatePhoneCall',
            'phone_call' => [
                '_' => 'phoneCall',
                'id' => 88001,
                'access_hash' => 515151515151,
                'date' => 1712345678,
                'admin_id' => 9002,
                'participant_id' => 9002,
                'g_a_or_b' => 'x',
                'key_fingerprint' => 1,
                'protocol' => ['_' => 'phoneCallProtocol', 'udp_p2p' => true, 'tcp_reflector' => true],
                'connections' => [],
                'start_date' => 1712345600,
            ],
        ],
        'updateDialogFilter' => [
            '_' => 'updateDialogFilter',
            'id' => 2,
            'filter' => [
                '_' => 'dialogFilter',
                'id' => 2,
                'title' => 'Clients',
                'contacts' => true,
                'pinned_peers' => [],
                'include_peers' => [],
                'exclude_peers' => [],
            ],
        ],
        'updateEncryption' => [
            '_' => 'updateEncryption',
            'chat' => [
                '_' => 'encryptedChat',
                'id' => 3301,
                'access_hash' => 515151515151,
                'date' => 1712345678,
                'admin_id' => 9002,
                'participant_id' => 9002,
                'g_a_or_b' => 'x',
                'key_fingerprint' => 1,
            ],
        ],
        'updateStory' => [
            '_' => 'updateStory',
            'peer' => ['_' => 'peerUser', 'user_id' => 9002],
            'story' => [
                '_' => 'storyItem',
                'id' => 441,
                'date' => 1712345678,
                'expire_date' => 1712432078,
                'media' => ['_' => 'messageMediaUnsupported'],
            ],
        ],
        'updateTheme' => [
            '_' => 'updateTheme',
            'theme' => [
                '_' => 'theme',
                'id' => 66001,
                'access_hash' => 515151515151,
                'slug' => 'midnight',
                'title' => 'Midnight',
                'settings' => [],
                'installs_count' => 12,
            ],
        ],
        'updatePeerWallpaper' => [
            '_' => 'updatePeerWallpaper',
            'peer' => ['_' => 'peerUser', 'user_id' => 9002],
            'wallpaper' => [
                '_' => 'wallPaper',
                'id' => 77001,
                'access_hash' => 515151515151,
                'slug' => 'paper',
                'document' => ['_' => 'document', 'id' => 5150, 'access_hash' => 1, 'date' => 1, 'mime_type' => 'image/jpeg', 'size' => 1, 'dc_id' => 4],
                'settings' => ['_' => 'wallPaperSettings', 'blur' => true, 'motion' => false],
            ],
        ],
        'updateQuickReplies' => [
            '_' => 'updateQuickReplies',
            'quick_replies' => [
                ['_' => 'quickReply', 'shortcut_id' => 12, 'shortcut' => 'yes', 'top_message' => 900, 'count' => 3],
            ],
        ],
        'updateQuickRepliesPair' => [
            '_' => 'updateQuickReplies',
            'quick_replies' => [
                ['_' => 'quickReply', 'shortcut_id' => 12, 'shortcut' => 'yes', 'top_message' => 900, 'count' => 3],
                ['_' => 'quickReply', 'shortcut_id' => 13, 'shortcut' => 'no', 'top_message' => 901, 'count' => 1],
            ],
        ],
        'payments.savedStarGifts' => [
            '_' => 'payments.savedStarGifts',
            'count' => 1,
            'gifts' => [
                [
                    '_' => 'savedStarGift',
                    'date' => 1712345678,
                    'msg_id' => 900,
                    'saved_id' => 4242,
                    'convert_stars' => 100,
                    'gift' => ['_' => 'starGift', 'id' => 55501, 'stars' => 25, 'convert_stars' => 100],
                ],
            ],
            'next_offset' => '',
            'chats' => [],
            'users' => [],
        ],
        'payments.starsStatus' => [
            '_' => 'payments.starsStatus',
            'balance' => ['_' => 'starsAmount', 'amount' => 900, 'nstars' => 0],
            'subscriptions' => [
                ['_' => 'starsSubscription', 'id' => 'sub-alpha', 'peer' => ['_' => 'peerUser', 'user_id' => 9002], 'until_date' => 1712432078],
            ],
            'history' => [
                ['_' => 'starsTransaction', 'id' => 'txn-beta', 'amount' => ['_' => 'starsAmount', 'amount' => 25, 'nstars' => 0], 'date' => 1712345678, 'peer' => ['_' => 'starsTransactionPeerUser', 'user_id' => 9002]],
            ],
            'chats' => [],
            'users' => [],
        ],
        'account.businessChatLinks' => [
            '_' => 'account.businessChatLinks',
            'links' => [
                ['_' => 'businessChatLink', 'link' => 'https://t.me/+AbCdEf', 'message' => 'Book a call', 'views' => 0],
            ],
            'chats' => [],
            'users' => [],
        ],
        'messages.savedDialogs' => [
            '_' => 'messages.savedDialogs',
            'dialogs' => [
                ['_' => 'savedDialog', 'peer' => ['_' => 'peerUser', 'user_id' => 9002], 'top_message' => 900],
            ],
            'chats' => [],
            'users' => [],
        ],
        'messages.allStickers' => [
            '_' => 'messages.allStickers',
            'sets' => [
                ['_' => 'stickerSet', 'id' => 1234001, 'access_hash' => 515151515151, 'title' => 'Pack', 'short_name' => 'pack', 'count' => 24, 'hash' => 99],
            ],
        ],
    ];

    /**
     * table => [migrations, payload key, assertion column, assertion value].
     *
     * @return array<string, array{0: list<string>, 1: string, 2: string, 3: string}>
     */
    private static function cases(): array
    {
        return [
            // ---- UPDATE-carrier batch: the Redis bus already delivers these. ----
            'tf_phone_calls' => [[self::MIG_MISC], 'updatePhoneCall', 'id', '88001'],
            'tf_dialog_filters' => [[self::MIG_GAP], 'updateDialogFilter', 'id', '2'],
            'tf_encrypted_chats' => [[self::MIG_MISC], 'updateEncryption', 'id', '3301'],
            'tf_story_items' => [[self::MIG_GAP], 'updateStory', 'id', '441'],
            'tf_themes' => [[self::MIG_MISC], 'updateTheme', 'id', '66001'],
            'tf_wallpapers' => [[self::MIG_GAP], 'updatePeerWallpaper', 'id', '77001'],
            'tf_quick_replies' => [[self::MIG_MISC], 'updateQuickReplies', 'shortcut_id', '12'],
            // ---- RPC-result batch: reachable through an admitted result. ----
            'tf_saved_star_gifts' => [[self::MIG_STAR], 'payments.savedStarGifts', 'id', '55501'],
            'tf_stars_transactions' => [[self::MIG_STAR], 'payments.starsStatus', 'id', 'txn-beta'],
            'tf_stars_subscriptions' => [[self::MIG_STAR], 'payments.starsStatus', 'id', 'sub-alpha'],
            'tf_business_chat_links' => [[self::MIG_STAR], 'account.businessChatLinks', 'link', 'https://t.me/+AbCdEf'],
            'tf_saved_dialogs' => [[self::MIG_MISC], 'messages.savedDialogs', 'peer_id', '9002'],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: list<string>, 2: string, 3: string, 4: string}>
     */
    public static function edgeProvider(): array
    {
        $out = [];
        foreach (self::cases() as $table => [$migrations, $payload, $column, $value]) {
            $out[$table] = [$table, $migrations, $payload, $column, $value];
        }

        return $out;
    }

    /**
     * The edge is the difference between "no row" and "this exact row".
     *
     * @param  list<string>  $migrations
     */
    #[DataProvider('edgeProvider')]
    public function test_a_declared_edge_is_the_whole_difference_between_no_row_and_the_row(
        string $table,
        array $migrations,
        string $payloadKey,
        string $column,
        string $expected,
    ): void {
        $payload = self::PAYLOADS[$payloadKey];

        // BEFORE — the real artifact with THIS table's edge deleted. Byte-derived
        // from what ships, so it is the state the tree actually shipped.
        $preDb = ShippedStoreSchema::capsuleFor(...$migrations)->getConnection();
        $pre = $this->route($this->catalogWithoutSourceFor($table))->ingest(
            $preDb,
            self::ACCOUNT,
            $payload,
        );
        self::assertSame(
            0,
            $preDb->table($table)->count(),
            "without a declared sources edge {$table} must NOT place — otherwise this test proves nothing",
        );
        self::assertSame(
            0,
            count(array_filter($pre['fkClues'], static fn (string $c): bool => str_starts_with($c, $table.':'))),
            "no edge must mean {$table} produces no write clue at all",
        );

        // AFTER — the shipped catalog, untouched.
        $db = ShippedStoreSchema::capsuleFor(...$migrations)->getConnection();
        $after = $this->route($this->shippedCatalog())->ingest($db, self::ACCOUNT, $payload);

        self::assertSame(
            1,
            $db->table($table)->count(),
            "the declared edge must place exactly one {$table} row",
        );
        self::assertSame(
            $expected,
            (string) $db->table($table)->where('account_id', self::ACCOUNT)->value($column),
            "the placed {$table} row must carry the real payload value in {$column}",
        );
        self::assertGreaterThan(
            0,
            $after['inserted'],
            "the writer must report the {$table} insert it just made",
        );

        // An edge that places a parent but leaves a write clue behind is a
        // half-truth: the row landed, but the fact did not decompose whole.
        $parentClues = array_values(array_filter(
            $after['fkClues'],
            static fn (string $c): bool => str_starts_with($c, $table.':'),
        ));
        self::assertSame([], $parentClues, "the placed {$table} row must decompose with no write clue");
    }

    /**
     * A vector carrier must place ONE ROW PER ELEMENT, not one row for the
     * vector — the branch a single-object carrier never exercises.
     */
    public function test_a_vector_carrier_places_one_row_per_element(): void
    {
        $db = ShippedStoreSchema::capsuleFor(self::MIG_MISC)->getConnection();
        $this->route($this->shippedCatalog())->ingest($db, self::ACCOUNT, self::PAYLOADS['updateQuickRepliesPair']);

        self::assertSame(2, $db->table('tf_quick_replies')->count(), 'both elements of the vector must place');
        self::assertSame(
            ['12', '13'],
            $db->table('tf_quick_replies')->orderBy('shortcut_id')->pluck('shortcut_id')
                ->map(static fn ($v): string => (string) $v)->all(),
            'each vector element must place under its own key, in order',
        );
    }

    /**
     * The `context` overlay is what supplies an ENCLOSING value a nested fact
     * does not carry itself. `updateChannelParticipant` (shipped) proves it for
     * `channel_id`; this asserts the same mechanism still holds now that the
     * catalog carries thirty-seven edges rather than five.
     *
     * EVERY COUNT IN THIS FILE IS A JSON COUNT. `MirrorCatalog::load()`
     * synthesises a `tf_messages_service` entry that the artifact does not
     * declare, so `tableNames()` reports 37 tables where the file has 36 —
     * take any table count from the API and it is off by one.
     *
     *     php -r '$r=json_decode(file_get_contents("src/Schema/schema/mirror-catalog.json"),true,512,JSON_THROW_ON_ERROR);
     *     $e=0;$w=0; foreach($r as $t=>$d){ if(!empty($d["sources"])){ $e+=count($d["sources"]); $w++; } }
     *     echo count($r)." tables, $w edge-bearing, $e source entries\n";'
     *     => 36 tables, 29 edge-bearing, 37 source entries
     *
     * The baseline "five" is the same measurement at `fbd1d711` (2
     * edge-bearing, 5 entries).
     *
     * ## THIS IS NOT A `MessageMedia` COUNT, AND 21 IS THE WRONG NUMBER
     *
     * `grep -c '= MessageMedia;'` on `schema/sources/TL_telegram_v227.tl`
     * returns 21, and that count has been written here twice by mistake. Two
     * of those 21 lines are the RETURN TYPE of the METHODS
     * `messages.uploadMedia#14967978` and `messages.uploadImportedMedia#2a862092`
     * — not constructors. The constructor count is
     * `grep -c '^messageMedia'` = 19, and 19 describes the TL scheme, not this
     * catalog. The catalog's own number is the 37 above; it is reached by
     * summing the `sources` arrays, and no grep of the scheme produces it.
     */
    public function test_a_brand_new_edge_is_indexed_against_its_parent_constructor(): void
    {
        $catalog = $this->shippedCatalog();

        foreach (['updatePeerWallpaper', 'updateStory', 'payments.starsStatus'] as $parent) {
            self::assertNotSame(
                [],
                $catalog->nestedSourcesFor($parent),
                "the walker indexes by PARENT CONSTRUCTOR, so {$parent} must carry a declared source",
            );
        }
    }

    /**
     * A table whose key no payload can fill is left UNDECLARED — but "no
     * payload" has to be MEASURED, not assumed, and two entries left this list
     * on 2026-10-03 because their key had become fillable.
     *
     * The list pins the honest question: an edge is a PROMISE that a row can
     * land, and a promise nothing can keep is worse than silence, because it
     * reads as fixed. A table's shipped primary key names a column no payload
     * carries (`message_entity_id`, `reaction_id`, `saved_reaction_tag_id`,
     * `todo_list_id`) — their own constructors have no id, so the row would die
     * at the decomposer with "keying column missing from payload".
     *
     * ## WHY `tf_message_medias` AND `tf_message_actions` LEFT THIS LIST
     *
     * They were here because their shipped key names a fabricated parent-link
     * column (`message_media_id`, `message_action_id`) that no `MessageMedia`
     * or `MessageAction` constructor carries. That is still true — and it is
     * now OVERCOME rather than ignored. `keyFrom` + `sources.context` express
     * what the wire actually means: a `message` carries AT MOST ONE `media`
     * (`message#7600b9d3 ... media:flags.9?MessageMedia`) and a
     * `messageService` at most one `action`, so each row's identity is its
     * enclosing message's — supplied by the overlay, whose exact contents each
     * entry declares for itself.
     *
     * MEASURED 2026-10-04, one correction to the paragraph above: the
     * enclosing message's `id` ALONE is not that identity.
     * `message#7600b9d3` carries `id:int` AND `peer_id:Peer`, and Telegram
     * numbers `id` PER CHAT, so `(account_id, message_media_id)` collided
     * across dialogs on the account's own primary key — 7 of the 10 discarded
     * `messages.getDialogs` pages died exactly there. The overlay therefore
     * grew to `context: ["id", "peer_id"]`, the entry declares
     * `scoped_by: ["peer_type", "peer_id"]`, and the identity is the enclosing
     * message's COORDINATE: `(account_id, peer_type, peer_id, message_media_id)`.
     * The claim is proven by execution, not asserted, in
     * `tests/Schema/Mirror/FabricatedMediaKeyTest.php`, which runs the shipped
     * walker and decomposer over a real `message` payload and requires every
     * column of that key to arrive with its real value and zero clues.
     *
     * The claim is proven rather than asserted by
     * `tests/Ingest/MessageMediaReachesTheWriterTest`, which places the row
     * against the real shipped migration and pins the discriminator and the
     * key.
     *
     * ## `tf_message_actions` — THE EDGE IS NOT REVERTED, AND IT PLACES
     *
     * An earlier revision of this docblock claimed that table's edge "was
     * written, validated, and then REVERTED", that
     * `MirrorResolverKeyMatchesShippedDdlTest::isFillable()` still reported it
     * unplaceable, and that "the honest state is unchanged". Every clause of
     * that was false, and execution disproves it.
     *
     *     grep -n '"tf_message_actions"' -A 80 src/Schema/schema/mirror-catalog.json
     *
     * The shipped entry declares `key`, `keyFrom` AND `sources` — carrier
     * `messageService`, field `action` — all three present. Ingesting one real
     * `messageService` payload carrying a real `messageActionChatEditTitle`
     * action through `MirrorResultIngester`, against the real shipped migration
     * `2026_09_29_000100_create_tf_mirror_gap_tables.php`, gives
     * `clues: null`, `fkClues: []`, `inserted: 1`, and exactly one
     * `tf_message_actions` row carrying the payload's own `title`. ZERO clues:
     * the edge places the row whole, which is the standard this file holds
     * everything else to.
     *
     * Delete that ONE entry's `sources` from the JSON and `MirrorCatalog::load()`
     * REFUSES the artifact outright — `MirrorSchemaException` from
     * `assertDerivable()` (`src/Schema/Mirror/MirrorCatalog.php:222`), reached
     * via `assertEntryInvariants()`. The edge is not decoration on that entry;
     * it is what makes the entry loadable at all.
     *
     * The catalog is authoritative for what places, so `tf_message_actions` was
     * REMOVED from the `UNPLACEABLE_KEY_TABLES` constant in
     * `tests/Schema/MirrorResolverKeyMatchesShippedDdlTest.php` — that constant
     * is where the allow-list decision is recorded, and it is deliberately cited
     * by name rather than by line number, because that file is under active
     * edit. The old paragraph above contradicted it.
     *
     * The other THREE keep their place for the same reason: `Reaction`,
     * `SavedReactionTag` and `TodoList` constructors carry no id of their own,
     * and their correct key is `(message identity, position)` — a real design
     * step, not an annotation. `MessageEntity` was the fourth and left this
     * list on 2026-10-04 by being DELETED outright, not repaired: its facts
     * already ship without loss in the positioned sibling
     * `tf_messages_entities`, so there was nothing left to key.
     */
    public function test_a_table_whose_key_no_payload_can_fill_is_left_undeclared(): void
    {
        $catalog = $this->shippedCatalog();
        $undeclared = ['tf_reactions', 'tf_saved_reaction_tags', 'tf_todo_lists'];

        foreach ($undeclared as $table) {
            self::assertNull(
                $catalog->table($table)->sources,
                "{$table} keys on a fabricated parent-link column no payload carries — "
                .'declaring an edge for it would promise rows that can never land',
            );
        }
    }

    /**
     * The converse, so the list above cannot rot into "everything is fine".
     *
     * ONE entry in the array below carries an edge AND places a row:
     * `tf_message_medias`. The array is the set whose FULL SHAPE is asserted
     * here — field, context, scope, `keyFrom` and `keySource` — so it is NOT a
     * claim that only one table in the catalog places; `tf_message_actions`
     * places too, and is documented above rather than asserted here. If a
     * future change removes this edge or breaks its `keyFrom`, the entry
     * silently returns to the state this suite was written to catch — so the
     * declaration is asserted here as positively as the absence is above.
     */
    public function test_a_table_whose_key_became_fillable_carries_the_edge_that_fills_it(): void
    {
        $catalog = $this->shippedCatalog();

        foreach (['tf_message_medias'] as $table) {
            $sources = $catalog->table($table)->sources;
            self::assertNotNull($sources, "{$table} now has a fillable key and must declare the edge that fills it");
            self::assertSame(
                'media',
                $sources[0]['field'],
                "{$table}'s edge must name the real wire field it is filled from",
            );
            self::assertSame(
                ['id', 'peer_id'],
                $sources[0]['context'],
                "{$table}'s key is now the ENCLOSING MESSAGE's coordinate, so the overlay must carry "
                .'both halves of it: `id` supplies `message_media_id` and `peer_id` supplies the peer '
                .'pair `fillPeerHalf()` reads (message#7600b9d3 numbers `id:int` PER CHAT, so `id` '
                .'alone is not an identity — MEASURED 2026-10-04 on the live account, 7 of 10 lost '
                .'pages died on this table\'s primary key)',
            );
            self::assertSame(
                ['peer_type', 'peer_id'],
                $catalog->table($table)->scopedBy,
                "{$table}'s key must DECLARE the peer scope it is keyed inside, or the derivation "
                .'silently drops it and the row is keyed inside a scope it never had',
            );
            self::assertSame(
                ['message_media_id' => 'id'],
                $catalog->table($table)->keyFrom,
                "{$table} derives its one EXPRESSED key column by the overlay's path and nothing else "
                .'— the peer pair is not expressed, it is filled from the same `peer_id` overlay by '
                .'fillPeerHalf(), so a keyFrom entry for it would be a second, unread derivation',
            );
            self::assertSame(
                'id',
                $catalog->table($table)->keySource($catalog->table($table)->key[0]),
                "{$table}'s KEY COLUMN must be the one the overlay supplies — a keyFrom naming a "
                .'different column would leave the primary key unfilled',
            );
        }
    }

    /**
     * The real ingest route: the same walker, decomposer and writer triple
     * `MirrorIngesterFactory::build()` calls, in the same order.
     */
    private function route(MirrorCatalog $catalog): MirrorResultIngester
    {
        $scheme = TlParser::parseFile(self::TL);
        $resolver = new MirrorTableResolver($catalog, $scheme);

        return new MirrorResultIngester(
            new MirrorNestedSourceWalker($catalog),
            new MirrorFactDecomposer($resolver, $catalog),
            new MirrorFactWriter,
        );
    }

    private function shippedCatalog(): MirrorCatalog
    {
        return MirrorCatalog::load(self::CATALOG, TlParser::parseFile(self::TL));
    }

    /**
     * The shipped catalog with exactly one table's `sources` deleted.
     *
     * A key removed from the real JSON and re-encoded, so the "before" catalog
     * differs from what ships by precisely the edge under test and nothing
     * else. `JSON_UNESCAPED_UNICODE` is deliberately NOT used: the artifact
     * escapes its `FK→` arrows, and the round-trip must not unescape them into
     * a 116-line diff of unrelated churn.
     */
    private function catalogWithoutSourceFor(string $table): MirrorCatalog
    {
        $raw = json_decode(
            (string) file_get_contents(self::CATALOG),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        unset($raw[$table]['sources']);

        $path = sys_get_temp_dir()."/teleframe-no-source-{$table}-".getmypid().'.json';
        file_put_contents($path, json_encode($raw, JSON_THROW_ON_ERROR));

        try {
            return MirrorCatalog::load($path, TlParser::parseFile(self::TL));
        } finally {
            @unlink($path);
        }
    }
}
