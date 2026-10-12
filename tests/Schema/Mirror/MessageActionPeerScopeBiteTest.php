<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Mirror;

use Illuminate\Container\Container;
use Illuminate\Database\Connection;
use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlScheme;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactDecomposer;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactWriter;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorNestedSourceWalker;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorTableResolver;
use MeRezaRezaei\Teleframe\Tests\Store\ShippedStoreSchema;
use PHPUnit\Framework\TestCase;

/**
 * The bite test for `tf_message_actions`' peer scope.
 *
 * ## THE DEFECT THIS BITES, MEASURED 2026-10-04
 *
 * `messageService#7a800e0a` (`src/Schema/schema/sources/api_full.tl:122`)
 * carries `id:int` AND `peer_id:Peer`, and Telegram numbers `id` PER CHAT. The
 * shipped key was `primary(['account_id','message_action_id'])`, so the same
 * message number in two dialogs was the same key.
 *
 * ## WHY THE OLD SHAPE WAS THE WORST FAILURE MODE, NOT A NOISY ONE
 *
 * `MirrorFactWriter::placeRow()` converges a primary-key repeat with ONE
 * atomic `upsert($row, $primary)` — that is deliberate, because the bus is
 * at-least-once and the same fact arriving twice is ordinary. So the narrow
 * key did not raise, did not decline, and did not report.
 *
 * MEASURED 2026-10-04 on the shipped path with the fix reverted, key measured
 * as `["account_id","message_action_id"]`, two service messages sharing
 * `id=5000111` under `peerChat` 1232026269 and 987654321:
 *
 *  - as TWO `write()` calls (the live shape — two pages, two batches): each
 *    returned `inserted=1`, `fkClues=[]`, `unmappedClues=[]`, and the table
 *    held ONE row whose title was `"second dialog title"`. The first dialog's
 *    title was gone.
 *  - as ONE `write()` call (both rows in one batch): `inserted=2`, the same
 *    three empty clue buckets, and the same single surviving row. So `inserted`
 *    counts STATEMENTS PLACED, not rows held, and it counts the overwrite as a
 *    success.
 *
 * Either way: data gone, no clue, no error, nothing to alert on and nothing to
 * retry.
 *
 * That is why the first test below asserts on the STORED ROWS and not on the
 * write counters alone: `inserted=1` with `fkClues=[]` is exactly what the
 * broken code returned, so a counter assertion passes on the defect.
 *
 * ## WHY `context` MUST CARRY `peer_id` AND NOT JUST THE DDL BE WIDENED
 *
 * `context` is the only mechanism that puts the peer's VALUE into the fact
 * payload. Measured: widening the primary key WITHOUT adding `peer_id` to
 * `context` yields `inserted=0` and
 * `tf_message_actions: row cannot cover its primary key (account_id,
 * peer_type, peer_id, message_action_id) — uncovered key columns: peer_type,
 * peer_id` — a LOUDER total loss, every row dropped. So the DDL and the
 * overlay move together, and the second test proves the half that makes the
 * second write an update rather than a second row.
 *
 * ## WHAT "THE LATER VALUES WIN" MEANS HERE
 *
 * Re-delivery is ordinary (a crash mid-batch replays the un-acked tail,
 * `XAUTOCLAIM` steals a slow worker's entries, the dead letter is replayed on
 * purpose), so a repeat of the SAME fact must still be ONE row. The widening
 * must not turn that into a duplicate, and the title is the value the writer
 * actually overwrites on convergence, which makes it the honest witness.
 */
final class MessageActionPeerScopeBiteTest extends TestCase
{
    private const PACKAGE_ROOT = __DIR__.'/../../..';

    private const TL = self::PACKAGE_ROOT.'/schema/sources/TL_telegram_v227.tl';

    private const CATALOG = self::PACKAGE_ROOT.'/src/Schema/schema/mirror-catalog.json';

    /** The migration that actually declares the table — measured, see the class docblock. */
    private const MIG_GAP = self::PACKAGE_ROOT.'/src/Laravel/Migrations/2026_09_29_000100_create_tf_mirror_gap_tables.php';

    private const ACCOUNT = 7;

    /** The SAME message number in two different dialogs — the whole point. */
    private const MESSAGE_ID = 5000111;

    private const PEER_A = 1232026269;

    private const PEER_B = 987654321;

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
     * The same message id under TWO peers must be TWO rows, each keeping its
     * own value.
     *
     * This is the case the fix exists for, and it is the one that fails on the
     * reverted code with NO signal: the table holds ONE row instead of two,
     * every clue bucket is empty, and the first title is gone. The row COUNT
     * is asserted rather than `inserted`, because `inserted` counts statements
     * placed — on the reverted code it reports 2 for this very batch and would
     * sail past a counter assertion while the data is already lost.
     */
    public function test_the_same_message_id_under_two_peers_is_two_rows_and_neither_value_is_lost(): void
    {
        $db = $this->shippedSchema();
        [$catalog, $resolver] = $this->pipeline();

        $write = (new MirrorFactWriter)->write($db, $this->decompose($catalog, $resolver, [
            $this->serviceMessage(self::PEER_A, 'first dialog title'),
            $this->serviceMessage(self::PEER_B, 'second dialog title'),
        ]));

        self::assertSame([], $write['fkClues'], 'both action rows must place with no clue at all');
        self::assertSame([], $write['unmappedClues'], 'every decomposed column must be one the shipped DDL has');
        self::assertSame([], $write['valueClues'], 'no value may fail to fit its column');
        self::assertSame(2, $write['inserted'], 'a per-chat message number under two peers is TWO facts');

        $stored = $db->table('tf_message_actions')
            ->where('account_id', self::ACCOUNT)
            ->orderBy('peer_id')
            ->get();

        self::assertCount(2, $stored, 'the shipped DDL must hold one row per PEER, not one row per '
            .'message number: `messageService#7a800e0a` numbers `id:int` PER CHAT');

        $byPeer = [];
        foreach ($stored as $row) {
            $byPeer[(int) $row->peer_id] = $row;
        }

        self::assertArrayHasKey(self::PEER_A, $byPeer, 'the first dialog\'s action row must survive');
        self::assertArrayHasKey(self::PEER_B, $byPeer, 'the second dialog\'s action row must survive');

        // The data-loss assertion: each row keeps ITS OWN title. Under the
        // reverted key both rows collapse onto one identity and the second
        // title overwrites the first — so this is the line that bites.
        self::assertSame('first dialog title', $byPeer[self::PEER_A]->title,
            'the first dialog\'s title must not be overwritten by the second dialog\'s action for the '
            .'same message number');
        self::assertSame('second dialog title', $byPeer[self::PEER_B]->title,
            'the second dialog\'s title must be stored against its own peer');

        self::assertSame(self::MESSAGE_ID, (int) $byPeer[self::PEER_A]->message_action_id,
            'both rows carry the same message number — that is why the peer half is the identity');
        self::assertSame(self::MESSAGE_ID, (int) $byPeer[self::PEER_B]->message_action_id);
        self::assertSame('messageActionChatEditTitle', $byPeer[self::PEER_A]->constructor);
    }

    /**
     * The SAME fact twice must stay ONE row with the later value — the
     * widening must not turn ordinary re-delivery into a duplicate.
     *
     * `MirrorFactWriter` converges on `upsert()`, so `inserted` is 2 here (two
     * statements placed) while the table holds one row. Asserting `inserted`
     * would therefore assert nothing about convergence; the row count and the
     * surviving title are what actually pin it.
     */
    public function test_the_same_id_under_the_same_peer_twice_is_one_row_and_the_later_value_wins(): void
    {
        $db = $this->shippedSchema();
        [$catalog, $resolver] = $this->pipeline();

        $write = (new MirrorFactWriter)->write($db, $this->decompose($catalog, $resolver, [
            $this->serviceMessage(self::PEER_A, 'stale title'),
            $this->serviceMessage(self::PEER_A, 'fresh title'),
        ]));

        self::assertSame([], $write['fkClues'], 'a repeat of one fact must place with no clue at all');
        self::assertSame([], $write['unmappedClues']);
        self::assertSame([], $write['valueClues']);

        $stored = $db->table('tf_message_actions')->where('account_id', self::ACCOUNT)->get();

        self::assertCount(1, $stored, 're-delivery is ordinary — the bus is at-least-once — so one fact '
            .'must converge on one row, never two');

        $row = $stored->first();
        self::assertSame('fresh title', $row->title,
            'convergence is an upsert on the table\'s real primary key, so the LATER value wins; this is '
            .'the behaviour the widening had to preserve rather than break');
        self::assertSame(self::MESSAGE_ID, (int) $row->message_action_id);
        self::assertSame(self::PEER_A, (int) $row->peer_id);
    }

    /**
     * A peer half that cannot be filled must DROP the row loudly.
     *
     * `peer_type`/`peer_id` are declared `NOT NULL` with NO default, on
     * purpose — the `tf_dialogs` `->default(0)` hazard the writer's own
     * docblock records is exactly the silent corruption this table must not
     * inherit. With `peer_id` in `context` this cannot be reached from the
     * shipped path, so it is asserted as the property that would fail if the
     * overlay were ever narrowed back to `["id"]` while the DDL stayed wide.
     */
    public function test_a_row_without_the_peer_half_is_declined_rather_than_written_under_a_fabricated_key(): void
    {
        $db = $this->shippedSchema();

        $write = (new MirrorFactWriter)->write($db, [[
            'table' => 'tf_message_actions',
            'row' => [
                'account_id' => self::ACCOUNT,
                'message_action_id' => self::MESSAGE_ID,
                'constructor' => 'messageActionChatEditTitle',
                'title' => 'no peer at all',
            ],
        ]]);

        self::assertSame(0, $write['inserted'], 'a row that cannot cover its live primary key is not stored');
        self::assertCount(1, $write['fkClues'], 'the decline must be REPORTED, which is the difference '
            .'between a loud drop and the silent overwrite this fix replaced');
        self::assertStringContainsString('uncovered key columns: peer_type, peer_id', $write['fkClues'][0]);
        self::assertSame(0, $db->table('tf_message_actions')->count(),
            'nothing may land at a fabricated (account_id, 0, 0)');
    }

    /**
     * A realistic decoded `messageService` carrying `messageActionChatEditTitle`.
     *
     * Transcribed from the shipped scheme: `messageService#7a800e0a`
     * (`id:int … peer_id:Peer … action:MessageAction`, api_full.tl:122) and
     * `messageActionChatEditTitle#b5a1ce5a title:string` (api_full.tl:146) —
     * a variant with no vector child, so the test measures the key and nothing
     * else.
     *
     * @return array<string, mixed>
     */
    private function serviceMessage(int $peerId, string $title): array
    {
        return [
            '_' => 'messageService',
            'id' => self::MESSAGE_ID,
            'peer_id' => ['_' => 'peerChat', 'chat_id' => $peerId],
            'date' => 1712345700,
            'action' => ['_' => 'messageActionChatEditTitle', 'title' => $title],
        ];
    }

    /**
     * Run the SHIPPED walker and decomposer over the given service messages —
     * nothing hand-built, so a change to the catalogue or the walker is felt.
     *
     * @param  list<array<string, mixed>>  $messages
     * @return list<array{table: string, row: array<string, int|string|null>}>
     */
    private function decompose(MirrorCatalog $catalog, MirrorTableResolver $resolver, array $messages): array
    {
        $decomposer = new MirrorFactDecomposer($resolver, $catalog);
        $facts = (new MirrorNestedSourceWalker($catalog))->collect([
            '_' => 'messages.messages',
            'messages' => $messages,
            'chats' => [],
            'users' => [],
        ]);

        $rows = [];
        $clues = [];
        foreach ($facts as $fact) {
            $out = $decomposer->decompose($fact['table'], self::ACCOUNT, $fact['payload']);
            $clues = [...$clues, ...$out['clues']];
            $rows = [...$rows, ...$out['rows']];
        }

        self::assertSame([], $clues, 'the shipped path must decompose with NO clue — a clue here means '
            .'the row would degrade instead of placing, which is a different defect this test is not '
            .'measuring and would otherwise mask');

        return $rows;
    }

    /**
     * @return array{0: MirrorCatalog, 1: MirrorTableResolver, 2?: TlScheme}
     */
    private function pipeline(bool $withScheme = false): array
    {
        $scheme = TlParser::parseFile(self::TL);
        $catalog = MirrorCatalog::load(self::CATALOG, $scheme);
        $resolver = new MirrorTableResolver($catalog, $scheme);

        return $withScheme ? [$catalog, $resolver, $scheme] : [$catalog, $resolver];
    }

    /**
     * A real in-memory sqlite schema from the real shipped migration, with the
     * container/facade state put back so this does not leak into its
     * neighbours.
     */
    private function shippedSchema(): Connection
    {
        $capsule = ShippedStoreSchema::capsuleFor(self::MIG_GAP);

        return $capsule->getConnection();
    }
}
