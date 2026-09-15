<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use MeRezaRezaei\Teleframe\Ingest\Events\UpdateStored;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Mirror\Models\TfMessage;
use MeRezaRezaei\Teleframe\Mirror\Models\TfUpdate;

/**
 * Phase C re-baseline: response ingest on the public
 * UpdateIngestor::ingestResponse() path. The legacy route-table dedup
 * (RouteIdempotency + tl_route_* surfaces) is GONE — a response is a
 * payload like any other, and the message/update-kind branches below are
 * the whole routing story:
 *
 *   - message envelopes (messages.messages, ...) decompose into the nested
 *     messages mirror; the FIRST stored message is the returned root;
 *   - update-kind payloads ALWAYS become tf_updates root instances;
 *   - the users/chats sidecar vectors are difference-stream-only — they
 *     have NO curated ingest write surface (identity mirrors are seeded),
 *     so they are walked and dropped, never persisted.
 *
 * Dedup therefore has exactly one source: content upserts on the curated
 * composite keys ((account_id, id) for messages, (account_id, seq,
 * position) for updates). No request-token table exists to mark responses.
 */
final class IngestResponseTest extends IngestTestCase
{
    private const ACCOUNT = 7;

    private const OTHER_ACCOUNT = 8;

    private const METHOD = 'messages.getHistory';

    private const USER_ID = 501558149;

    private const CHANNEL_ID = 1737473577;

    /**
     * messages.getHistory response member messages.messages carrying one
     * full message tree plus the chats/users sidecar vectors — the exact
     * wire shape the legacy test family used.
     *
     * @return array<string, mixed>
     */
    private static function messagesMessagesResponse(): array
    {
        return [
            '_' => 'messages.messages',
            'messages' => [
                [
                    '_' => 'message',
                    'flags' => (1 << 1) | (1 << 7) | (1 << 8) | (1 << 9),
                    'out' => true,
                    'id' => 1186,
                    'from_id' => ['_' => 'peerUser', 'user_id' => self::USER_ID],
                    'peer_id' => ['_' => 'peerChannel', 'channel_id' => self::CHANNEL_ID],
                    'date' => 1724852400,
                    'message' => 'Check https://t.me/teleframe from @Reza',
                    'media' => ['_' => 'messageMediaEmpty'],
                    'entities' => [
                        ['_' => 'messageEntityBold', 'offset' => 0, 'length' => 5],
                        ['_' => 'messageEntityUrl', 'offset' => 6, 'length' => 21],
                    ],
                    'flags2' => 0,
                ],
            ],
            'chats' => [
                [
                    '_' => 'channel',
                    'flags' => (1 << 7) | (1 << 8) | (1 << 13),
                    'verified' => true,
                    'megagroup' => true,
                    'id' => self::CHANNEL_ID,
                    'access_hash' => -7779317524312221622,
                    'title' => 'Teleframe Café',
                    'photo' => ['_' => 'chatPhotoEmpty'],
                    'date' => 1712345678,
                ],
            ],
            'users' => [
                [
                    '_' => 'user',
                    'flags' => (1 << 0) | (1 << 1) | (1 << 2) | (1 << 3),
                    'id' => self::USER_ID,
                    'access_hash' => -5988024083302710253,
                    'first_name' => 'Reza',
                    'last_name' => 'Rezaei',
                    'username' => 'RezaRezaei',
                ],
            ],
        ];
    }

    public function test_response_ingests_descendants(): void
    {
        $root = (new UpdateIngestor)->ingestResponse(
            self::METHOD,
            ['peer' => ['_' => 'inputPeerChannel', 'channel_id' => self::CHANNEL_ID]],
            self::messagesMessagesResponse(),
            self::ACCOUNT,
        );

        self::assertInstanceOf(TfMessage::class, $root, 'envelope resolves to its first persisted descendant');

        // The message row landed with canonical inline peer halves and no
        // tl_data / extracted message_text legacy surface.
        $message = TfMessage::forAccount(self::ACCOUNT)->sole();
        self::assertSame(1186, (int) $message->getAttribute('id'));
        self::assertSame(3, (int) $message->getAttribute('peer_type'), 'peerChannel normalizes to peer_type 3');
        self::assertSame(self::CHANNEL_ID, (int) $message->getAttribute('peer_id'));
        self::assertSame('Check https://t.me/teleframe from @Reza', $message->getAttribute('message'));

        // The user/channel SIDECARS are ephemeral in the curated dial: they
        // ride the wire only and satisfy nothing on the identity mirrors.
        self::assertSame(0, DB::table('tf_users')->where('account_id', self::ACCOUNT)->count());
        self::assertSame(0, DB::table('tf_channels')->where('account_id', self::ACCOUNT)->count());
    }

    public function test_duplicate_response_reingests_upsert_stable(): void
    {
        Event::fake([UpdateStored::class]);

        $ingestor = new UpdateIngestor(events: $this->app['events']);
        $first = $ingestor->ingestResponse(self::METHOD, [], self::messagesMessagesResponse(), self::ACCOUNT);
        $second = $ingestor->ingestResponse(self::METHOD, [], self::messagesMessagesResponse(), self::ACCOUNT);

        self::assertInstanceOf(TfMessage::class, $first);
        self::assertInstanceOf(TfMessage::class, $second, 'duplicate response re-ingests through the same branch');
        self::assertSame(1, TfMessage::acrossAccounts()->count(), 'content upsert keeps the message row stable on (account_id, id)');

        Event::assertDispatchedTimes(UpdateStored::class, 2, 'each response ingest fires once (no route-table dedup)');
    }

    public function test_response_rows_are_tenant_scoped(): void
    {
        // Same response under a second tenant: distinct (account_id, id)
        // rows — no cross-account dedup, no unique-constraint collision.
        $ingestor = new UpdateIngestor;
        $a = $ingestor->ingestResponse(self::METHOD, [], self::messagesMessagesResponse(), self::ACCOUNT);
        $b = $ingestor->ingestResponse(self::METHOD, [], self::messagesMessagesResponse(), self::OTHER_ACCOUNT);

        self::assertNotNull($a);
        self::assertNotNull($b);
        self::assertSame(1, TfMessage::forAccount(self::ACCOUNT)->count(), 'each tenant stores its own message');
        self::assertSame(1, TfMessage::forAccount(self::OTHER_ACCOUNT)->count());
        self::assertSame(2, TfMessage::acrossAccounts()->count());
    }

    public function test_update_kind_payloads_always_become_instances(): void
    {
        Event::fake([UpdateStored::class]);

        $payload = [
            '_' => 'updateNewMessage',
            'message' => [
                '_' => 'message',
                'flags' => (1 << 1) | (1 << 9),
                'out' => true,
                'id' => 1187,
                'peer_id' => ['_' => 'peerChannel', 'channel_id' => self::CHANNEL_ID],
                'date' => 1724852401,
                'message' => 'sideband update',
                'media' => ['_' => 'messageMediaEmpty'],
                'flags2' => 0,
            ],
            'pts' => 1350,
            'pts_count' => 1,
        ];

        $ingestor = new UpdateIngestor(events: $this->app['events']);
        $first = $ingestor->ingestResponse('updates.getDifference', ['pts_total' => 1], $payload, self::ACCOUNT);
        $second = $ingestor->ingestResponse('updates.getDifference', ['pts_total' => 1], $payload, self::ACCOUNT);

        self::assertInstanceOf(TfUpdate::class, $first);
        self::assertInstanceOf(TfUpdate::class, $second, 'update-kind payloads always become instances (no dedup skip)');
        self::assertSame(1, TfUpdate::acrossAccounts()->count(), 'update row upsert-stable on (account_id, seq, position)');

        Event::assertDispatchedTimes(UpdateStored::class, 2);
    }

    public function test_no_route_tables_exist_in_the_curated_dial(): void
    {
        // The legacy tl_route_* / generated surface is purged from the dial;
        // ingestResponse routes through the message/update-kind branches only.
        self::assertFalse(Schema::hasTable('tl_route_messages_get_history'));
        self::assertFalse(Schema::hasTable('tl_route_updates_get_difference'));
        self::assertFalse(Schema::hasTable('tf_routes'));
    }
}
