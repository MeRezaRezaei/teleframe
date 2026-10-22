<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Container\Container;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactDecomposer;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactWriter;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorNestedSource;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorNestedSourceWalker;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorTableResolver;
use MeRezaRezaei\Teleframe\Tests\Store\ShippedStoreSchema;
use PHPUnit\Framework\TestCase;

/**
 * A `message`'s MEDIA reaches the writer — and the `Document` behind it keeps
 * its own `access_hash` / `dc_id` VALUES.
 *
 * ## What was broken, in three independent layers
 *
 * 1. NO EDGE. `mirror-catalog.json` declared `sources` for seventeen of its
 *    thirty-six tables. `tf_message_medias` had none, so the walker — which
 *    iterates ONLY `nestedSourcesFor($ctor)` and never infers an edge — could
 *    not look, and every photo, video, file, voice note, sticker, poll,
 *    contact, location, invoice and web preview in the account had no writer
 *    path at all.
 *
 * 2. NO DESCENT. Adding the edge was not enough, and this is the part a reader
 *    is most likely to assume works. `MirrorNestedSourceWalker::
 *    declaredParentTypesFor()` only descended when the node it stood on was
 *    ITSELF a declared source parent. Every one of the twenty-one shipped
 *    parents is a TOP-LEVEL constructor, so the gate was never exercised and
 *    looked correct. A `message` is not top-level on the backfill path: it is
 *    one element of a `messages.messages` envelope's `messages:Vector<Message>`
 *    param. MEASURED with a load-validated edge installed:
 *    `collect(messages.messages)` returned ZERO facts while `collect(message)`
 *    returned the media fact.
 *
 * 3. THE KEY. `tf_message_medias` ships `PRIMARY KEY (account_id,
 *    message_media_id)` and `message_media_id` is not fillable from
 *    `messageMedia` itself. The decomposer used to emit
 *    `tf_message_medias: keying column 'message_media_id' missing from payload
 *    — cannot place the fact` and drop the row. `keyFrom` +
 *    `sources.context` now express what the wire actually means: a message
 *    carries AT MOST ONE `media` (`message#7600b9d3 ... media:flags.9?
 *    MessageMedia`, `api_full.tl:121`), so a media row's identity IS its
 *    message's identity, and the enclosing `id` supplies it.
 *
 *    CORRECTED 2026-10-04: this paragraph used to call `message_media_id` "a
 *    fabricated parent-link column no `MessageMedia` constructor carries". The
 *    first half was wrong on its own terms — the column is not fabricated, it
 *    is the overlay's own name for the enclosing message's id — and the second
 *    half was FALSE, and the file range it cited refuted it in the same file.
 *    Of the 19 `MessageMedia` constructors (`api_full.tl:124-142`), exactly one
 *    carries a bare `id:int`: `messageMediaStory#68cb6283 ... peer:Peer id:int
 *    story:flags.0?StoryItem` (`api_full.tl:137`). It is not a counter-example
 *    — that `id` is a STORY id, the stories namespace keyed `id` on
 *    `tf_story_items`, on 1 of 19 constructors, so it is neither a union-wide
 *    key nor a legitimate `message_media_id`. The shipped DDL reached that
 *    verdict before this docblock did and DROPPED `messageMediaStory.id` as a
 *    plain-name collision with the parent FK column `id`
 *    (`2026_09_14_200012_create_tf_message_medias_tables.php:18-19`). The other
 *    id-bearing params are distinctly named — `user_id`, `venue_id`,
 *    `receipt_msg_id`, `channel_id`, `launch_msg_id` — and are not `id`.
 *    Counted with `grep -c '^messageMedia' src/Schema/schema/sources/api_full.tl`
 *    (19); the bare `^messageMedia#` form returns 0, because no constructor is
 *    named `messageMedia#<hash>` — the union itself is `MessageMedia` and every
 *    member carries a suffix.
 *
 * ## Why VALUES, not counts
 *
 * A count proves a row arrived. It does not prove the row is the RIGHT row,
 * and for this table the risk is specific: `access_hash` is a signed TL long
 * and decodes NEGATIVE about half the time, so a fixture asserting it as a
 * positive integer would pass a broken encoder and fail a correct one. The
 * assertions below pin the exact signed values, and `dc_id` is asserted
 * separately because it is the one field a plausible-looking default (`0`)
 * would silently fake.
 *
 * ## The two catalogs
 *
 * BEFORE is derived by DELETING the edge key from the real shipped JSON, so it
 * differs from what ships by precisely the edge under test. The schema is the
 * real shipped migrations executed by `ShippedStoreSchema::capsuleFor()`.
 */
final class MessageMediaReachesTheWriterTest extends TestCase
{
    private const TL = __DIR__.'/../../schema/sources/TL_telegram_v227.tl';

    private const CATALOG = __DIR__.'/../../src/Schema/schema/mirror-catalog.json';

    private const MIG_GAP = __DIR__.'/../../src/Laravel/Migrations/2026_09_29_000100_create_tf_mirror_gap_tables.php';

    /**
     * MEASURED REQUIRED since Task 3.1: `tf_documents` now has a declared edge
     * on `messageMediaDocument.document`, so the walk this suite performs emits
     * a `tf_documents` row and the shipped DDL for it must be present.
     */
    private const MIG_DOCUMENTS = __DIR__.'/../../src/Laravel/Migrations/2026_09_14_200020_create_tf_documents_tables.php';

    private const ACCOUNT = 11;

    /** The document's access_hash decodes NEGATIVE — a TL long, not a magnitude. */
    private const ACCESS_HASH = -9988776655443;

    private const DC_ID = 2;

    /**
     * The container/facade application in force before this test ran.
     *
     * `ShippedStoreSchema::capsuleFor()` calls `Container::setInstance()` and
     * `Facade::setFacadeApplication()` with its OWN in-memory sqlite and does
     * NOT put the previous one back. Leaving that leak behind makes this class
     * poison every later suite that extends Orchestra Testbench and expects the
     * real facade application — observed directly: with this file present,
     * three `MirrorFactWriterParentFirstOrderingTest` cases failed on tables
     * this change never touches; with it absent, they passed. Captured and
     * restored here for the reason `ShippedStoreSchema::restoreFacadeApplication()`
     * documents.
     */
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
     * A realistic decoded `message` carrying `messageMediaDocument`, with a
     * nested `document` and a `documentAttributeFilename`.
     *
     * Transcribed from the v227 scheme: `message#7600b9d3`,
     * `messageMediaDocument#3cb63eb`, `document#8fd4c4d`,
     * `documentAttributeFilename#9801d2f7`.
     *
     * @return array<string, mixed>
     */
    private static function message(): array
    {
        return [
            '_' => 'message',
            'id' => 38826512,
            'peer_id' => ['_' => 'peerChat', 'chat_id' => 1232026269],
            'date' => 1712345678,
            'message' => 'caption here',
            'out' => true,
            'media' => [
                '_' => 'messageMediaDocument',
                'document' => [
                    '_' => 'document',
                    'id' => 5150123456789,
                    'access_hash' => self::ACCESS_HASH,
                    'file_reference' => 'AQADAgADEAAD1y3',
                    'date' => 1712345600,
                    'mime_type' => 'image/jpeg',
                    'size' => 88213,
                    'dc_id' => self::DC_ID,
                    'attributes' => [
                        ['_' => 'documentAttributeFilename', 'file_name' => 'photo.jpg'],
                    ],
                    'thumbs' => [],
                ],
                'spoiler' => false,
            ],
        ];
    }

    /**
     * The whole route the backfill takes: an ENVELOPE carrying the message,
     * walked by the real walker against the real catalog.
     *
     * @return list<array{table: string, payload: array<string, mixed>}>
     */
    private static function collect(MirrorCatalog $catalog, array $message): array
    {
        $walker = new MirrorNestedSourceWalker($catalog);
        $envelope = [
            '_' => 'messages.messages',
            'messages' => [$message],
            'chats' => [],
            'users' => [],
        ];

        return $walker->collect($envelope);
    }

    /**
     * RED: the shipped artifact with this table's `sources` edge deleted must
     * place NOTHING and say NOTHING — an edge that is merely declared but
     * unreachable is the failure mode this whole change is about.
     */
    public function test_without_the_declared_edge_the_media_never_reaches_the_writer(): void
    {
        $catalog = self::catalogWithoutSourceFor('tf_message_medias');
        $facts = self::collect($catalog, self::message());

        $tables = array_map(static fn (array $f): string => $f['table'], $facts);
        self::assertNotContains(
            'tf_message_medias',
            $tables,
            'without a declared sources edge the walker must not look at media — otherwise this test '
            .'would not be proving the edge is what makes the row reachable',
        );

        // MEASURED 2026-10-02 (takeover), and this is why the assertion is scoped
        // to the ONE table rather than to the whole fact list: Task 3.1 landed a
        // SECOND edge that also stands on `messageMediaDocument` —
        // `tf_documents`' `messageMediaDocument.document`. Deleting
        // `tf_message_medias`' edge removes the MEDIA row but leaves
        // `MessageMedia` a declared parent type (the document edge declares it),
        // so the walker still descends into the media node and finds the
        // Document. That is CORRECT: two tables legitimately read the same
        // node. Asserting an empty fact list here would have been asserting
        // that the document edge does not exist.
        self::assertContains(
            'tf_documents',
            $tables,
            'tf_documents has its OWN edge on messageMediaDocument, so it must still be found with '
            ."tf_message_medias' edge removed — if this stops holding, a second edge was removed by "
            .'accident and the walk is under-reaching',
        );
    }

    /**
     * RED, second layer: with the edge declared, the ENVELOPE path must find it.
     *
     * Before the descent fix this returned zero facts, because the walker only
     * descended from a node that was already a declared parent, and a
     * `messages.messages` envelope is not one. Asserted through the envelope
     * rather than through a bare `message` payload precisely because the bare
     * payload already worked — it is the envelope that was broken.
     */
    public function test_the_envelope_reaches_the_message_and_through_it_the_media(): void
    {
        $facts = self::collect(self::shippedCatalog(), self::message());

        $tables = array_map(static fn (array $f): string => $f['table'], $facts);
        self::assertContains(
            'tf_message_medias',
            $tables,
            'the walker must descend messages.messages -> messages[] -> message and then find message.media',
        );

        $media = null;
        foreach ($facts as $fact) {
            if ($fact['table'] === 'tf_message_medias') {
                $media = $fact['payload'];
            }
        }
        self::assertNotNull($media, 'the media fact must be present');
        self::assertSame('messageMediaDocument', $media['_'], 'the fact must carry its own ctor');
        self::assertSame(
            38826512,
            $media['id'],
            'the context overlay must supply the ENCLOSING message id — it is the media row\'s identity',
        );
    }

    /**
     * GREEN: the row places against the REAL shipped DDL, carrying the real
     * discriminator, and the nested Document keeps its own signed access_hash
     * and dc_id VALUES.
     */
    public function test_the_media_row_lands_and_its_document_keeps_its_own_values(): void
    {
        $catalog = self::shippedCatalog();
        // MEASURED 2026-10-02 (takeover): the documents migration is now
        // REQUIRED here, not optional. Task 3.1 landed `tf_documents`' edge on
        // `messageMediaDocument.document`, so this walk emits a `tf_documents`
        // row — and against a schema without that table the write died with
        // "no such table: tf_documents". The assertion below wanted the
        // document's values all along; the edge is what made them reachable.
        $db = ShippedStoreSchema::capsuleFor(self::MIG_GAP, self::MIG_DOCUMENTS)->getConnection();

        $schema = TlParser::parseFile(self::TL);
        $decomposer = new MirrorFactDecomposer(new MirrorTableResolver($catalog, $schema), $catalog);
        $writer = new MirrorFactWriter;

        $rows = [];
        $clues = [];
        foreach (self::collect($catalog, self::message()) as $fact) {
            $decomposed = $decomposer->decompose($fact['table'], self::ACCOUNT, $fact['payload']);
            $clues = [...$clues, ...$decomposed['clues']];
            $rows = [...$rows, ...$decomposed['rows']];
        }

        // A half-truth is the failure this guards: a row that lands while the
        // decomposer still reports it unplaceable proves nothing.
        self::assertSame([], $clues, 'the media fact must decompose with NO write clue');

        $written = $writer->write($db, $rows);
        self::assertGreaterThan(0, $written['inserted'], 'the writer must report the insert it just made');

        self::assertSame(
            1,
            $db->table('tf_message_medias')->count(),
            'exactly one media row must place for a message carrying one media',
        );

        $row = $db->table('tf_message_medias')->where('account_id', self::ACCOUNT)->first();
        self::assertSame('messageMediaDocument', $row->constructor, 'the discriminator names the real variant');
        self::assertSame(
            38826512,
            (int) $row->message_media_id,
            'the media row is keyed by the enclosing message id — a message carries at most one media',
        );
    }

    /**
     * `tf_documents` NOW DECLARES ITS EDGE — this test was written to fail when
     * that happened, and its own instruction was to be REPLACED by a real value
     * assertion rather than deleted. This is that replacement.
     *
     * What it asserted before (2026-10-02, Task 2.2): `tf_documents` had no
     * edge, so the Document behind a `messageMediaDocument` reached nothing and
     * its `access_hash` / `dc_id` were unwritten. The edge
     * `messageMediaDocument.document` now exists — it was priority #1 of
     * Task 3.1 and the biggest live loss the phase closed — so the honest
     * assertion is the opposite one: the Document is found by the walk AND its
     * signed `access_hash` and `dc_id` land as the wire carried them.
     *
     * Note this is `messageMediaDocument`, NOT `tf_message_medias`' children:
     * the media entry declares `photo`/`ttl_seconds`/`video`, while `document`
     * is the field `messageMediaDocument` actually carries (TL:129,
     * `messageMediaDocument#52d8ccd9`), so the Document is reached by its own
     * declared edge. That was already true when this test was written — the
     * test's claim that the Document sat "one level below every declared edge"
     * was wrong about the walk and right only about the missing edge.
     */
    public function test_the_nested_document_is_reached_by_its_own_edge_and_keeps_its_values(): void
    {
        $catalog = self::shippedCatalog();

        $sources = $catalog->nestedSourcesFor('messageMediaDocument');
        $fields = array_map(static fn (MirrorNestedSource $s): string => $s->field, $sources);
        self::assertContains(
            'document',
            $fields,
            'tf_documents declares its own edge on messageMediaDocument.document — if that edge is '
            .'removed, this test must be reverted to asserting the values are unwritten, never deleted',
        );

        $facts = self::collect($catalog, self::message());
        $document = null;
        foreach ($facts as $fact) {
            if ($fact['table'] === 'tf_documents') {
                $document = $fact['payload'];
            }
        }

        self::assertNotNull($document, 'the Document behind the media must be walked');
        self::assertSame(self::ACCESS_HASH, $document['access_hash'], 'the signed access_hash must survive the walk');
        self::assertSame(self::DC_ID, $document['dc_id'], 'dc_id must survive the walk');

        $db = ShippedStoreSchema::capsuleFor(self::MIG_GAP, self::MIG_DOCUMENTS)->getConnection();
        $schema = TlParser::parseFile(self::TL);
        $decomposer = new MirrorFactDecomposer(new MirrorTableResolver($catalog, $schema), $catalog);

        $rows = [];
        $clues = [];
        foreach ($facts as $fact) {
            $decomposed = $decomposer->decompose($fact['table'], self::ACCOUNT, $fact['payload']);
            $clues = [...$clues, ...$decomposed['clues']];
            $rows = [...$rows, ...$decomposed['rows']];
        }

        self::assertSame([], $clues, 'the Document must decompose with NO write clue — a row that lands '
            .'while the decomposer still reports it unplaceable proves nothing');

        (new MirrorFactWriter)->write($db, $rows);

        $row = $db->table('tf_documents')->where('account_id', self::ACCOUNT)->first();
        self::assertNotNull($row, 'the Document row must place against the real shipped DDL');
        self::assertSame('document', $row->constructor);
        self::assertSame(
            self::ACCESS_HASH,
            (int) $row->access_hash,
            'the row must carry the wire access_hash — NEGATIVE, because it is a signed TL long',
        );
        self::assertSame(self::DC_ID, (int) $row->dc_id, 'dc_id is the document DC and must not be a plausible 0');
    }

    /**
     * The Document behind the media must reach `tf_documents` with its
     * `access_hash` and `dc_id` INTACT.
     *
     * Asserted through a SEPARATE catalog edge rather than through
     * `tf_message_medias`'s own children, because the media entry declares
     * `photo`/`video` but NOT `document` — `messageMediaDocument.document` is
     * `FK→Document` and reaches `tf_documents` by its own declared edge. That
     * is measured here, not assumed.
     */
    private static function shippedCatalog(): MirrorCatalog
    {
        return MirrorCatalog::load(self::CATALOG, TlParser::parseFile(self::TL));
    }

    /**
     * The shipped artifact with exactly one table's `sources` deleted.
     *
     * Byte-derived from the real JSON by REMOVING A KEY, never by
     * transcribing a "before" state, so the two catalogs differ by precisely
     * the edge under test.
     */
    private static function catalogWithoutSourceFor(string $table): MirrorCatalog
    {
        $raw = json_decode((string) file_get_contents(self::CATALOG), true, 512, JSON_THROW_ON_ERROR);
        unset($raw[$table]['sources']);

        $path = sys_get_temp_dir()."/teleframe-media-nosource-{$table}-".getmypid().'.json';
        file_put_contents($path, json_encode($raw, JSON_THROW_ON_ERROR));

        try {
            return MirrorCatalog::load($path, TlParser::parseFile(self::TL));
        } finally {
            @unlink($path);
        }
    }
}
