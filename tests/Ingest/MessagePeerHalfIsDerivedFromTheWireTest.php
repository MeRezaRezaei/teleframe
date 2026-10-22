<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Mirror\Models\TfMessage;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * THE PEER HALVES ARE DERIVED FROM THE WIRE, NOT SUPPLIED BY THE CALLER.
 *
 * ## Why this file exists — the gate next door cannot see this
 *
 * `tests/Store/MessagePeerScopedPrimaryKeyTest.php` proves the KEY SHAPE: it
 * writes rows carrying an EXPLICIT `peer_type`/`peer_id` and asserts two peers
 * holding the same `id` stay two rows. That is the right gate for a DDL, and
 * it stays the gate for a DDL.
 *
 * It says NOTHING about where those two values come from. A writer that filled
 * them with `0` would pass every assertion in that file, and every message row
 * in the account would then land with `peer_type = 0, peer_id = 0` — which is
 * the SAME corruption the widened key exists to end, moved one layer down: all
 * dialogs collapse back onto a single key prefix, and the 80.98% overwrite
 * returns with the widened schema still in place. The earlier fix of exactly
 * that on 2026-10-02 is why this arm exists rather than a comment.
 *
 * Task 1.2 made `unmappedClues` FATAL in the ingest gate, so the half now has to
 * be PROVEN to arrive, not assumed to.
 *
 * ## THE TL CITATION THAT SETTLES WHERE `peer_type` COMES FROM
 *
 * `message#7600b9d3` carries `id:int` AND `peer_id:Peer` in the SAME
 * constructor (`src/Schema/schema/sources/api_full.tl:121`, and `:122` for
 * `messageService#7a800e0a`). The field is typed `Peer`, NOT `long`:
 *
 * ```
 * peerUser#59511722    user_id:long    = Peer;   (api_full.tl:73)
 * peerChat#36c6019a    chat_id:long    = Peer;   (api_full.tl:74)
 * peerChannel#a2a5371e channel_id:long = Peer;   (api_full.tl:75)
 * ```
 *
 * So `peer_id` is a THREE-WAY UNION carrying its own discriminator, and
 * `peer_type` is not a value anyone has to invent: it IS the constructor name
 * Telegram sent. A Task 2.1 reviewer read this field as a bare `long`, and a
 * later relay repeated it; both were wrong, and the error mattered — a
 * `scoped_by` naming `peer_type` on a field with no type to read would be a
 * fabrication. It is not: `PeerShapeTool::normalize()` reads the `_`
 * constructor name and maps it, which is the same mapping the whole mirror
 * already uses for every other peer-typed field.
 *
 * ## What is asserted, and why VALUES rather than counts
 *
 * Each arm ingests a payload whose `peer_id` is a DIFFERENT constructor of that
 * union and asserts the exact `(peer_type, peer_id)` pair that landed. Three
 * constructors, three distinct pairs: so an implementation that hard-coded
 * `peer_type = 1` fails on the chat and channel arms, and one that hard-coded
 * `peer_type = 3` fails on the user arm. A count assertion would pass all
 * three.
 *
 * The ids are NEGATIVE for chats and channels (`inputPeerChat#35a95cb9
 * chat_id:long`, `inputPeerChannel#27bcbbfc channel_id:long` — Telegram's
 * convention that dialog ids are negated on the wire), so the literal asserts
 * the sign too. A writer that took an absolute value would still produce two
 * distinct rows per constructor and would pass a count.
 *
 * ## The falsifying arm: `0/0` is named, not merely un-asserted
 *
 * `test_a_message_with_no_peer_id_would_land_the_zero_placeholder` records the
 * consequence of the one payload shape the writer cannot place, so the number
 * `0` appearing in a message row is a DOCUMENTED outcome rather than a value a
 * reader has to guess about. It does not assert the row is good — it asserts
 * the row is IDENTIFIABLE, so a `0/0` sweep is detectable by a query rather
 * than invisible.
 *
 * Provenance: src/Teleframe/Ingest/UpdateIngestor.php::writeMessage (the
 * `PeerShapeTool::normalize(self::peerValue($payload['peer_id']))` derivation);
 * src/Schema/Eloquent/PeerShapeTool.php::normalize (the `_` → 1/2/3 mapping).
 */
final class MessagePeerHalfIsDerivedFromTheWireTest extends IngestTestCase
{
    private const ACCOUNT = 31;

    /** The id under test is the SAME in every arm — that is the point. */
    private const MESSAGE_ID = 5;

    private const USER_ID = 501558149;

    /** A group chat id, NEGATIVE on the wire. */
    private const CHAT_ID = -500123;

    /**
     * Tenant 31 (`ACCOUNT`) — the only one.
     *
     * Every arm ingests under `self::ACCOUNT` and then reads `tf_messages` back
     * scoped by it, so the tenant has to exist as a real parent: the account id
     * an ingest call carries is the caller's TELEGRAM user id —
     * `MirrorIngesterFactory.php:160` writes it straight into `tf_messages.account_id`
     * — and the FK there targets `telegram_accounts.user_id`, not the vault
     * row's own `id`. The file deliberately holds `MESSAGE_ID` constant across
     * arms and varies only the wire `peer_id`, so no second tenant is written.
     *
     * Production always plants the parent first:
     * `VaultAddAccountCommand.php:80` writes `user_id` at account-add time.
     *
     * @return list<int>
     */
    protected function ingestTenantIds(): array
    {
        return [31];
    }

    /** A supergroup id, NEGATIVE on the wire. */
    private const CHANNEL_ID = -1001234567;

    /**
     * The three constructors of the `Peer` union that `message.peer_id` can be,
     * each with the payload fragment and the EXACT pair it must produce.
     *
     * @return array<string, array{0: array<string, mixed>, 1: int, 2: int}>
     */
    public static function peerConstructors(): array
    {
        return [
            'peerUser' => [
                ['_' => 'peerUser', 'user_id' => self::USER_ID],
                1,
                self::USER_ID,
            ],
            'peerChat' => [
                ['_' => 'peerChat', 'chat_id' => self::CHAT_ID],
                2,
                self::CHAT_ID,
            ],
            'peerChannel' => [
                ['_' => 'peerChannel', 'channel_id' => self::CHANNEL_ID],
                3,
                self::CHANNEL_ID,
            ],
        ];
    }

    /**
     * A real `message` payload carrying the given `peer_id` union member.
     *
     * Shaped off `message#7600b9d3`: `id:int` and `peer_id:Peer` sit in the
     * same constructor, which is the whole reason the address is four columns
     * and not two.
     *
     * @param  array<string, mixed>  $peerId
     * @return array<string, mixed>
     */
    private static function messagePayload(array $peerId, int $id = self::MESSAGE_ID): array
    {
        return [
            '_' => 'message',
            'id' => $id,
            'peer_id' => $peerId,
            'from_id' => ['_' => 'peerUser', 'user_id' => self::USER_ID],
            'date' => 1726000000,
            'message' => 'a message whose peer half must be DERIVED',
        ];
    }

    /**
     * THE GATE. A real wire payload in, the exact peer pair out.
     *
     * Runs against `IngestTestCase`, which migrates the SHIPPED migration
     * directory through `UpdateIngestor::migrationPaths()`, so the columns read
     * back below are the shipped ones and not a fixture's own copy of them.
     *
     * @param  array<string, mixed>  $peerPayload
     */
    #[DataProvider('peerConstructors')]
    public function test_the_peer_halves_are_derived_from_the_wire_constructor(
        array $peerPayload,
        int $expectedType,
        int $expectedId,
    ): void {
        $root = (new UpdateIngestor)->ingest(self::messagePayload($peerPayload), self::ACCOUNT);

        self::assertInstanceOf(TfMessage::class, $root, 'a message payload must hydrate as TfMessage');

        // Read the row back from the shipped table rather than trusting the
        // hydrated model: the model is the writer's own return value, so
        // asserting on it alone would let a re-read that returned a DIFFERENT
        // dialog's row pass. This names the four key columns explicitly.
        $row = DB::connection()->table('tf_messages')
            ->where('account_id', self::ACCOUNT)
            ->where('id', self::MESSAGE_ID)
            ->first();

        self::assertNotNull($row, 'the message row did not land at all');

        self::assertSame(
            $expectedType,
            (int) $row->peer_type,
            'peer_type must be read off the `peer_id` union constructor Telegram sent. A hard-coded '
            .'value, or the zero placeholder, lands every dialog on one key prefix — the same 80.98% '
            .'overwrite this key exists to end.',
        );
        self::assertSame(
            $expectedId,
            (int) $row->peer_id,
            'peer_id must be the id INSIDE that union member, sign included. Telegram negates chat '
            .'and channel ids on the wire; taking an absolute value produces a row no real peer can claim.',
        );
    }

    /**
     * The pair is not merely present but UNIQUE — the collision the gate next
     * door asserts on DDL is reproduced here through the REAL writer, from
     * payloads rather than from an explicit key.
     *
     * Three constructors, one shared `id`, three rows. If any of the three
     * landed on the zero placeholder the other two would still be distinct, so
     * the count alone would pass — which is why the arms above assert the
     * VALUES and this arm asserts only the shape they add up to.
     */
    public function test_three_constructors_with_one_shared_id_derive_three_distinct_keys(): void
    {
        foreach (self::peerConstructors() as [$peerPayload]) {
            (new UpdateIngestor)->ingest(self::messagePayload($peerPayload), self::ACCOUNT);
        }

        $rows = DB::connection()->table('tf_messages')
            ->where('account_id', self::ACCOUNT)
            ->where('id', self::MESSAGE_ID)
            ->orderBy('peer_type')
            ->get();

        self::assertCount(
            3,
            $rows,
            'three peers each carrying message id 5 must be three rows. Fewer means two of the three '
            .'derived the SAME peer pair — the silent collapse, reproduced through the real writer.',
        );

        $actual = [];
        foreach ($rows as $row) {
            $actual[] = [(int) $row->peer_type, (int) $row->peer_id];
        }
        $expected = [];
        foreach (self::peerConstructors() as [, $type, $id]) {
            $expected[] = [$type, $id];
        }
        sort($actual);
        sort($expected);

        self::assertSame(
            $expected,
            $actual,
            'the derived pairs must be exactly the three constructors, each once',
        );
    }

    /**
     * `messageService` gets the same treatment, because it is a SEPARATE
     * constructor with its own table and its own writer branch
     * (`MESSAGE_TABLES['messageService'] = 'tf_messages_service'`).
     *
     * `messageService#7a800e0a` carries the same `peer_id:Peer` field, so a fix
     * applied only to the `message` branch would leave half the mirror unscoped.
     *
     * @param  array<string, mixed>  $peerPayload
     */
    #[DataProvider('peerConstructors')]
    public function test_a_service_message_derives_its_peer_halves_the_same_way(
        array $peerPayload,
        int $expectedType,
        int $expectedId,
    ): void {
        (new UpdateIngestor)->ingest([
            '_' => 'messageService',
            'id' => 77,
            'peer_id' => $peerPayload,
            'date' => 1726000000,
            'action' => [
                '_' => 'messagesReadContents',
                'flags' => 0,
                'peer' => ['_' => 'peerUser', 'user_id' => self::USER_ID],
            ],
        ], self::ACCOUNT);

        $row = DB::connection()->table('tf_messages_service')
            ->where('account_id', self::ACCOUNT)
            ->where('id', 77)
            ->first();

        self::assertNotNull($row, 'the messageService row did not land');
        self::assertSame(
            $expectedType,
            (int) $row->peer_type,
            'tf_messages_service shares the peer-scoped key with tf_messages, so it must derive the '
            .'same halves — a service row on the zero placeholder collides with every other one.',
        );
        self::assertSame(
            $expectedId,
            (int) $row->peer_id,
            'the service row must carry the id from inside the peer_id union member, sign included',
        );
    }

    /**
     * THE FALSIFYING ARM. A payload with no `peer_id` lands the zero
     * placeholder, and this makes that VISIBLE instead of silent.
     *
     * `message#7600b9d3` declares `peer_id` UNFLAGGED and MANDATORY, so a real
     * payload always carries it and this arm is unreachable over a real
     * connection. It is here for the two cases that are real: a hand-built
     * fixture, and a future decoder that drops the field.
     *
     * The assertion is deliberately NOT "this must not happen" — the writer
     * cannot refuse the row, and inventing a throw here would change the
     * ingest contract for a payload the TL says cannot exist. It asserts the
     * weaker, achievable, and genuinely useful thing: the row is still
     * RETRIEVABLE and its peer is IDENTIFIABLE as the placeholder. A `0/0`
     * sweep is then a query a maintenance run can count, instead of a state
     * nobody can name.
     */
    public function test_a_message_with_no_peer_id_lands_an_identifiable_placeholder(): void
    {
        $payload = self::messagePayload([]);
        unset($payload['peer_id']);

        $root = (new UpdateIngestor)->ingest($payload, self::ACCOUNT);
        self::assertInstanceOf(TfMessage::class, $root);

        $row = DB::connection()->table('tf_messages')
            ->where('account_id', self::ACCOUNT)
            ->where('id', self::MESSAGE_ID)
            ->first();

        self::assertNotNull($row, 'the row must still land — refusing it would change the ingest contract');
        self::assertSame(
            0,
            (int) $row->peer_type,
            'this arm documents the PLACEHOLDER value. If the writer ever stops falling back to 0 for an '
            .'absent peer_id, this goes red and the fallback has to be documented at its new home.',
        );
        self::assertSame(0, (int) $row->peer_id);

        // The consequence, stated as a count rather than a warning: a sweep of
        // unscoped rows is measurable, which is the property that makes the
        // placeholder survivable at all.
        $placeholders = DB::connection()->table('tf_messages')
            ->where('account_id', self::ACCOUNT)
            ->where('peer_type', 0)
            ->where('peer_id', 0)
            ->count();
        self::assertSame(
            1,
            (int) $placeholders,
            'the placeholder must be countable by peer_type/peer_id so an unscoped sweep is detectable',
        );
    }
}
