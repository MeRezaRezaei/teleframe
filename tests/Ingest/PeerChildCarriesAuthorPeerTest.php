<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use MeRezaRezaei\Teleframe\Laravel\SchemaArtifacts;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactDecomposer;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactWriter;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorTableResolver;
use MeRezaRezaei\Teleframe\Tests\Store\ShippedStoreSchema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * A decomposed message's author must be MIRRORED, not stored as 0/0.
 *
 * `mirror-catalog.json` declares `from_id` on `tf_messages` as a peer child
 * (`["from_id","Peer"]`), and `MirrorTableResolver` materialises that as the
 * table `tf_messages_from_id` — the peer pair `from_id_type` / `from_id_id`.
 * The peer VALUE is a field of the PARENT wire object (`message.from_id:Peer`,
 * schema/sources/TL_telegram_v227.tl:121); the child row is built from that
 * value, so the field name is never a key of the child's own array.
 *
 * `MirrorFactDecomposer::childRow()` therefore probed the child's own payload
 * for `from_id`, never found it, emitted a `peer column 'from_id' missing`
 * clue, and left both halves of the pair unwritten — so the shipped DDL
 * defaults (`->default(0)`, src/Laravel/Migrations/2026_09_14_200010:129-130)
 * filled the row with 0/0 and the AUTHOR of every backfilled message was lost
 * with a key-only row. The PARENT call site passed the right payload all
 * along, which is the asymmetry that proves it.
 *
 * The DDL is EXECUTED, never transcribed: `ShippedStoreSchema::capsuleFor()`
 * runs the real shipped migration file, so the columns asserted here are the
 * columns the package actually ships. The row asserted here is the row the
 * REAL decomposer produced, read back out of the real table.
 *
 * Provenance: src/Schema/schema/mirror-catalog.json (`tf_messages` children →
 * `["from_id","Peer"]`); src/Schema/Mirror/MirrorFactDecomposer.php:214
 * (`childRow()`), :231 (the peer half of a child row), :292
 * (`fillPeerHalf()`), :71 (the parent call site that already passed
 * `$payload`); src/Laravel/Migrations/2026_09_14_200010_create_tf_messages_tables.php:126-131
 * (`tf_messages_from_id`, both halves `->default(0)`);
 * schema/sources/TL_telegram_v227.tl:121 (`from_id:flags.8?Peer`).
 */
final class PeerChildCarriesAuthorPeerTest extends TestCase
{
    private const PACKAGE_ROOT = __DIR__.'/../..';

    /** The one migration that creates the message subject table and every one of its children. */
    private const MIGRATION = '2026_09_14_200010_create_tf_messages_tables.php';

    private const ACCOUNT = 7;

    /** The author's peer id on the wire, and the canonical enum PeerShapeTool assigns it. */
    private const AUTHOR_ID = 777;

    private const AUTHOR_TYPE = 1; // user

    /**
     * A `message` exactly as the wire decodes it, carrying the peer-declared
     * children whose value lives on the PARENT object.
     *
     * @var array<string, mixed>
     */
    private const PAYLOAD = [
        '_' => 'message',
        'id' => 4242,
        'from_id' => ['_' => 'peerUser', 'user_id' => self::AUTHOR_ID],
        'peer_id' => ['_' => 'peerUser', 'user_id' => 77],
        'date' => 1735689600,
        'message' => 'the author of this message is the row the mirror must not lose',
        'saved_peer_id' => ['_' => 'peerChannel', 'channel_id' => 991],
        'guestchat_via_from' => ['_' => 'peerUser', 'user_id' => 12],
        'fwd_from' => [
            '_' => 'messageFwdHeader',
            'from_id' => ['_' => 'peerUser', 'user_id' => 555],
            'from_name' => 'origin channel',
            'date' => 1,
            'saved_from_peer' => ['_' => 'peerChat', 'chat_id' => -42],
            'saved_from_id' => ['_' => 'peerChannel', 'channel_id' => 881],
        ],
        'reply_to' => [
            '_' => 'messageReplyHeader',
            'reply_to_msg_id' => 9,
            'reply_to_peer_id' => ['_' => 'peerUser', 'user_id' => 31],
        ],
    ];

    public function test_the_decomposed_author_row_carries_the_authors_peer_id(): void
    {
        $capsule = ShippedStoreSchema::capsuleFor(self::PACKAGE_ROOT.'/src/Laravel/Migrations/'.self::MIGRATION);
        $db = $capsule->getConnection();

        $result = (new MirrorFactDecomposer(...$this->pipeline()))->decompose('tf_messages', self::ACCOUNT, self::PAYLOAD);
        $written = (new MirrorFactWriter)->write($db, $result['rows']);

        self::assertSame(
            count($result['rows']),
            $written['inserted'],
            'a decomposed row did not land: '.implode(', ', array_column($result['rows'], 'table'))
            .' | fkClues: '.json_encode($written['fkClues']),
        );
        self::assertSame([], $written['unmappedClues'], 'a column the shipped DDL does not have is a modelling gap, not a green write');

        $row = $db->table('tf_messages_from_id')->where('account_id', self::ACCOUNT)->where('id', 4242)->first();
        self::assertNotNull($row, 'tf_messages_from_id holds no row for the mirrored message');

        // The ROW, not the absence of an error: the author's id is 777 on the
        // wire. Both halves are asserted because the pair is written by two
        // independent columns and either can be lost on its own.
        self::assertSame(self::AUTHOR_ID, (int) $row->from_id_id, 'the author peer id was lost — the row is a 0/0 key-only row');
        self::assertSame(self::AUTHOR_TYPE, (int) $row->from_id_type, 'the author peer type was lost — the row is a 0/0 key-only row');
    }

    /**
     * Every peer-declared child of a message, read back from the shipped DDL.
     *
     * The defect was not confined to the author: `saved_peer_id`,
     * `guestchat_via_from` and `reply_to.peer` are the same shape — a peer
     * field of the parent, a child table with a {field}_type/{field}_id pair.
     * `fwd_from` is the control that PROVES the child's own payload is still
     * read: its peers are fields of the nested `MessageFwdHeader`, not of the
     * message, so a fix that replaced the child's payload with the parent's
     * would lose them.
     *
     * @return array<string, array{0: string, 1: string, 2: int, 3: int}>
     */
    public static function peerChildProvider(): array
    {
        return [
            'author' => ['tf_messages_from_id', 'from_id', 777, 1],
            'saved peer' => ['tf_messages_saved_peer_id', 'saved_peer_id', 991, 3],
            'guest inviter' => ['tf_messages_guestchat_via_from', 'guestchat_via_from', 12, 1],
            'reply peer (parent field)' => ['tf_messages_reply_to', 'peer', 77, 1],
            'fwd author (child field)' => ['tf_messages_fwd_from', 'from_id', 555, 1],
            'fwd saved_from_peer (child field)' => ['tf_messages_fwd_from', 'saved_from_peer', -42, 2],
            'fwd saved_from_id (child field)' => ['tf_messages_fwd_from', 'saved_from_id', 881, 3],
        ];
    }

    /**
     * @param  string  $table  shipped child table
     * @param  string  $stem  column stem: {stem}_type / {stem}_id
     * @param  int  $id  the canonical peer id the wire payload carries
     * @param  int  $type  the canonical peer type (1=user 2=chat 3=channel)
     */
    #[DataProvider('peerChildProvider')]
    public function test_every_peer_child_row_carries_its_peer_pair(string $table, string $stem, int $id, int $type): void
    {
        $capsule = ShippedStoreSchema::capsuleFor(self::PACKAGE_ROOT.'/src/Laravel/Migrations/'.self::MIGRATION);
        $db = $capsule->getConnection();

        $result = (new MirrorFactDecomposer(...$this->pipeline()))->decompose('tf_messages', self::ACCOUNT, self::PAYLOAD);
        (new MirrorFactWriter)->write($db, $result['rows']);

        $row = $db->table($table)->where('account_id', self::ACCOUNT)->where('id', 4242)->first();
        self::assertNotNull($row, "{$table} holds no row for the mirrored message");
        self::assertSame($id, (int) $row->{$stem.'_id'}, "{$table}.{$stem}_id was not mirrored");
        self::assertSame($type, (int) $row->{$stem.'_type'}, "{$table}.{$stem}_type was not mirrored");
    }

    /**
     * The defect as it presented, and the proof that the fix is what changed.
     *
     * The same decomposer call, over the same shipped schema, built the child
     * row with NO peer keys at all — so the DDL defaults 0/0 filled it. This
     * asserts that property of the produced row, not of a log message: a
     * decomposer that emitted `from_id_id => 0` would still satisfy it, and a
     * test that only counted clues would have passed on a fix that merely
     * moved the failure.
     *
     * @param  list<array{table: string, row: array<string, int|string|null>}>  $rows
     */
    public function test_the_author_row_is_produced_by_the_decomposer_with_its_peer_keys(): void
    {
        $result = (new MirrorFactDecomposer(...$this->pipeline()))->decompose('tf_messages', self::ACCOUNT, self::PAYLOAD);

        $from = null;
        foreach ($result['rows'] as $row) {
            if ($row['table'] === 'tf_messages_from_id') {
                $from = $row['row'];
            }
        }

        self::assertNotNull($from, 'the decomposer no longer emits a tf_messages_from_id row — this test is stale');
        self::assertArrayHasKey('from_id_id', $from, 'the author row carries no peer keys: the DDL defaults 0/0 and the author is lost');
        self::assertSame(self::AUTHOR_ID, $from['from_id_id']);
        self::assertSame(self::AUTHOR_TYPE, $from['from_id_type']);
    }

    /**
     * @return array{0: MirrorTableResolver, 1: MirrorCatalog}
     */
    private function pipeline(): array
    {
        $scheme = TlParser::parseFile(SchemaArtifacts::tl());
        $catalog = MirrorCatalog::load(SchemaArtifacts::catalog(), $scheme);

        return [new MirrorTableResolver($catalog, $scheme), $catalog];
    }
}
