<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Mirror;

use Illuminate\Container\Container;
use Illuminate\Database\Connection;
use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlScheme;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactDecomposer;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorNestedSourceWalker;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorTableResolver;
use MeRezaRezaei\Teleframe\Tests\Store\ShippedStoreSchema;
use PHPUnit\Framework\TestCase;

/**
 * `tf_message_medias` must key on an identity, not on a number that repeats.
 *
 * ## WHAT THE LIVE RUN SHOWED (MEASURED 2026-10-04, operator's account)
 *
 * 7 of the 10 discarded `messages.getDialogs` pages died on
 * `Duplicate entry '1724372757-958882' for key 'tf_message_medias.PRIMARY'`,
 * and the shipped table was keyed `(account_id, message_media_id)` over
 * `(account_id, message_media_id, constructor, spoiler, live_photo)`.
 *
 * ## WHAT `message_media_id` ACTUALLY HOLDS — measured, not assumed
 *
 * `messageMedia` is a PURE UNION and carries no union-wide identity of its own.
 * `grep -c '^messageMedia' src/Schema/schema/sources/api_full.tl` finds 19
 * constructors (api_full.tl:124-142 — `messageMediaEmpty`, `messageMediaPhoto`,
 * … `messageMediaVideoStream`). Use that exact form: the bare `^messageMedia#`
 * returns 0, because no constructor is named `messageMedia#<hash>` — the union
 * itself is `MessageMedia`, and every member carries a suffix. There is no
 * `messageMedia#4cf4d72d`.
 *
 * CORRECTED 2026-10-04, and the earlier text here was FALSE: it claimed those 19
 * carry "NOT ONE `id` parameter between them" and that there is "no per-media
 * `id` in any TL file in this repo". Exactly one DOES carry a bare `id` —
 * `messageMediaStory#68cb6283 … peer:Peer id:int story:flags.0?StoryItem`
 * (api_full.tl:137; identically in `api.tl:140` and `TL_telegram_v227.tl:137`).
 *
 * It changes nothing about this test, and the reason is the substance: that `id`
 * is a STORY id — the stories namespace, keyed `id` on `tf_story_items` — and it
 * is carried by 1 of 19 constructors, so it is neither a union-wide key nor a
 * legitimate `message_media_id`. The shipped DDL reached that verdict before this
 * docblock did and DROPPED `messageMediaStory.id` as a plain-name collision with
 * the parent FK column `id`
 * (`src/Laravel/Migrations/2026_09_14_200012_create_tf_message_medias_tables.php:18-19`).
 *
 * So the column's value is the ENCLOSING MESSAGE's id, overlaid by
 * `sources.context` (`MirrorNestedSourceWalker::walk()`), which the
 * decomposer turns into `message_media_id` — measured with the real walker and
 * the real decomposer on the payload in `message()` below, BEFORE this change:
 * `{"account_id":11,"constructor":"messageMediaPhoto","message_media_id":38826512,…}`.
 *
 * CORRECTED 2026-10-04 (verifier): this line used to read
 * `sources.context: ["id"]`, which understated the overlay. The shipped
 * `schema/mirror-catalog.json` declares `context: ["id", "peer_id"]` — `id`
 * supplies `message_media_id` and `peer_id` supplies the peer half of the
 * scope, which is the whole point of the widening. Asserting the key is
 * therefore not the same as asserting the overlay carries only `id`.
 * The column is therefore FILLABLE, and this test is NOT about an unfillable
 * column.
 *
 * ## WHAT IS ACTUALLY WRONG WITH THE KEY
 *
 * `message#7600b9d3` (api_full.tl:121) numbers `id:int` PER CHAT and carries
 * `peer_id:Peer`, so a bare message id is not an identity across a real
 * account's dialogs — two chats both hold a message numbered 1 and they
 * collide on the account's own primary key. The same rule is already applied
 * to the sibling tables: 36 shipped tables key
 * `(account_id, peer_type, peer_id, id)` —
 * `grep -ho "primary(\['account_id', 'peer_type', 'peer_id', 'id'\])"
 * src/Laravel/Migrations/*.php | wc -l` = 36 — of which `tf_messages` is one;
 * `tf_messages` itself has 29 measured child tables
 * (`docs/schema/mirror-relation-graph.md`, the generated one: "`tf_messages`
 * (message — tf_messages plus 29 measured child tables)"). MEASURED by
 * RESIDUE-RECONCILE 2026-10-05 — `grep -n 'child tables'
 * docs/schema/mirror-relation-graph.md` line 78 reads 29, and an independent
 * count of the `tf_messages` erDiagram entity blocks (30 entities, minus
 * `tf_messages` itself) also reads 29. An earlier draft of this docblock
 * quoted 31; that was WRONG and misquoted the very source it cites.
 * The 36 and the 29 count DIFFERENT things and are deliberately not reconciled
 * into one number.
 * `tf_messages_media` also carries
 * `message_peer_type`/`message_peer_id` for exactly this reason
 * (`src/Laravel/Migrations/2026_09_14_200012_create_tf_message_medias_tables.php:28-35`).
 * `tf_message_medias` was the one media table left keyed inside a scope it
 * never declared.
 *
 * The identity the payload DOES carry is therefore
 * `(peer_type, peer_id, message_media_id)` — a message carries AT MOST ONE
 * `media` (`message#7600b9d3 … media:flags.9?MessageMedia`), so its enclosing
 * coordinate identifies the media row uniquely. The three assertions below
 * pin that identity where it can rot: the executed DDL, the resolver's
 * derivation, and the REAL decomposer's emitted row.
 *
 * ## WHY THE PLACEMENT IS ASSERTED, NOT JUST THE SCHEMA
 *
 * A key column the decomposer never emits costs every row of the table, and a
 * key column that is `NULL`/`0` corrupts it silently. So the third assertion
 * runs the shipped walker and the shipped decomposer over a real `message`
 * payload and requires every column of the widened key to arrive with its real
 * value and ZERO clues.
 */
final class FabricatedMediaKeyTest extends TestCase
{
    private const PACKAGE_ROOT = __DIR__.'/../../..';

    private const TL = self::PACKAGE_ROOT.'/schema/sources/TL_telegram_v227.tl';

    private const CATALOG = self::PACKAGE_ROOT.'/src/Schema/schema/mirror-catalog.json';

    /** The migration that actually declares `tf_message_medias` — MEASURED, see `declaringMigration()`. */
    private const MIG_GAP = self::PACKAGE_ROOT.'/src/Laravel/Migrations/2026_09_29_000100_create_tf_mirror_gap_tables.php';

    private const ACCOUNT = 11;

    /** A chat peer: PeerShapeTool's canonical type for `peerChat` is 2. */
    private const PEER_TYPE = 2;

    private const PEER_ID = 1232026269;

    private const MESSAGE_ID = 38826512;

    /**
     * The full shipped identity: account scope + the enclosing message's peer
     * scope + the message id that supplies `message_media_id`.
     *
     * @var list<string>
     */
    private const EXPECTED_KEY = ['account_id', 'peer_type', 'peer_id', 'message_media_id'];

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
     * The EXECUTED DDL is the artifact under test, not a string in a file: the
     * plan's own first draft asserted on the contents of
     * `2026_09_14_200010_create_tf_messages_tables.php`, which does not declare
     * this table at all (it is declared by the gap-tables migration — measured
     * with `grep -rn "Schema::create('tf_message_medias'" src/Laravel/Migrations`),
     * so that assertion would have passed while guarding nothing.
     */
    public function test_the_executed_media_primary_key_is_the_enclosing_message_coordinate(): void
    {
        $db = ShippedStoreSchema::capsuleFor(self::MIG_GAP)->getConnection();

        self::assertSame(
            self::EXPECTED_KEY,
            $this->primaryKeyColumns($db, 'tf_message_medias'),
            'tf_message_medias is keyed (account_id, message_media_id) and `message#7600b9d3` '
            .'numbers `id:int` PER CHAT, so two dialogs holding the same message number collide on '
            ."the account's own primary key. The peer scope is the identity, and the payload carries it.",
        );

        self::assertSame(
            self::EXPECTED_KEY,
            $this->primaryKeyColumns($db, 'tf_message_medias_ttl_seconds'),
            'the one child table `makeChildPkCols()` copies the parent key onto must widen with it, '
            .'in the same migration — otherwise the child PK names columns the parent row no longer keys on',
        );
    }

    /**
     * The resolver's derived key and the shipped `primary()` must be ONE
     * identity. `MirrorResolverKeyMatchesShippedDdlTest` compares them for every
     * CATALOGUE table; this asserts the value itself, so a future regeneration
     * that drops the scope fails here with the number rather than in a list.
     */
    public function test_the_resolver_derives_exactly_that_key(): void
    {
        $table = $this->resolver()->resolveAll(['tf_message_medias'])[0];

        self::assertSame(
            ['peer_type', 'peer_id', 'message_media_id'],
            $table->keyColumns,
            'the declared `scoped_by` must PREFIX the expressed `key`, exactly as it prefixes a '
            .'derived natural key: the key says which id, the scope says which chat it is numbered in',
        );
    }

    /**
     * Every column of the widened key, filled with its REAL value, from a REAL
     * payload, by the shipped decomposer — the assertion that makes a key
     * column nobody emits impossible to declare.
     */
    public function test_every_column_of_the_widened_key_is_emitted_with_its_real_value(): void
    {
        $scheme = TlParser::parseFile(self::TL);
        $catalog = MirrorCatalog::load(self::CATALOG, $scheme);
        $resolver = new MirrorTableResolver($catalog, $scheme);
        $decomposer = new MirrorFactDecomposer($resolver, $catalog);

        $facts = (new MirrorNestedSourceWalker($catalog))->collect([
            '_' => 'messages.messages',
            'messages' => [$this->message()],
            'chats' => [],
            'users' => [],
        ]);

        $media = null;
        foreach ($facts as $fact) {
            if ($fact['table'] === 'tf_message_medias') {
                $media = $fact['payload'];
            }
        }
        self::assertNotNull($media, 'a message carrying media must still reach tf_message_medias');

        $out = $decomposer->decompose('tf_message_medias', self::ACCOUNT, $media);
        self::assertSame([], $out['clues'], 'a media row with a real peer and a real message id must place with no clue at all');

        $rows = [];
        foreach ($out['rows'] as $row) {
            $rows[$row['table']] = $row['row'];
        }

        self::assertSame(
            [
                'account_id' => self::ACCOUNT,
                'peer_type' => self::PEER_TYPE,
                'peer_id' => self::PEER_ID,
                'message_media_id' => self::MESSAGE_ID,
            ],
            array_intersect_key($rows['tf_message_medias'], array_flip(self::EXPECTED_KEY)),
            'the media row must carry the whole identity, each half filled from the wire: peer_type/'
            ."peer_id from `message#7600b9d3`'s `peer_id:Peer`, message_media_id from its `id:int`",
        );

        self::assertSame(
            [
                'account_id' => self::ACCOUNT,
                'peer_type' => self::PEER_TYPE,
                'peer_id' => self::PEER_ID,
                'message_media_id' => self::MESSAGE_ID,
            ],
            array_intersect_key($rows['tf_message_medias_ttl_seconds'], array_flip(self::EXPECTED_KEY)),
            'the child row inherits the parent identity unchanged — a child keyed on less than its '
            .'parent is a second, independent duplicate-key surface',
        );
    }

    /**
     * A realistic decoded `message` carrying `messageMediaPhoto`.
     *
     * Transcribed from the shipped scheme: `message#7600b9d3`
     * (`id:int peer_id:Peer … media:flags.9?MessageMedia`) and
     * `messageMediaPhoto#e216eb63`
     * (`flags:# spoiler:flags.3?true live_photo:flags.4?true photo:flags.0?Photo ttl_seconds:flags.2?int`).
     *
     * @return array<string, mixed>
     */
    private function message(): array
    {
        return [
            '_' => 'message',
            'id' => self::MESSAGE_ID,
            'peer_id' => ['_' => 'peerChat', 'chat_id' => self::PEER_ID],
            'date' => 1712345678,
            'message' => 'caption here',
            'out' => true,
            'media' => [
                '_' => 'messageMediaPhoto',
                'photo' => [
                    '_' => 'photo',
                    'id' => 5150123456789,
                    'access_hash' => -9988776655443,
                    'file_reference' => 'AQADAgADEAAD1y3',
                    'date' => 1712345600,
                    'dc_id' => 2,
                ],
                'ttl_seconds' => 12,
                'spoiler' => false,
            ],
        ];
    }

    private function scheme(): TlScheme
    {
        return TlParser::parseFile(self::TL);
    }

    private function resolver(): MirrorTableResolver
    {
        return new MirrorTableResolver(MirrorCatalog::load(self::CATALOG, $this->scheme()), $this->scheme());
    }

    /**
     * The PRIMARY index columns of a table in the EXECUTED shipped schema.
     *
     * @return list<string>
     */
    private function primaryKeyColumns(Connection $db, string $table): array
    {
        foreach ($db->getSchemaBuilder()->getIndexes($table) as $index) {
            if (($index['primary'] ?? false) !== true) {
                continue;
            }

            return array_map('strval', $index['columns']);
        }

        self::fail("{$table} declares no PRIMARY index in the shipped schema");
    }
}
