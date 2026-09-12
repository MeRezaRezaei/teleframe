<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use MeRezaRezaei\Teleframe\Ingest\EntityAggregator;
use MeRezaRezaei\Teleframe\Ingest\Events\UpdateStored;
use MeRezaRezaei\Teleframe\Ingest\RouteIdempotency;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerIdTool;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUser;

/**
 * Plan Task 5: response routing on the public ingest path —
 * UpdateIngestor::ingestResponse() dedups method responses through the
 * route tables (seen? return the stored instance : ingest + mark), while
 * update-kind payloads ALWAYS become instances (branch taken before any
 * route logic). Methods without a generated route table stay routable-free
 * and ingest unconditionally.
 *
 * Domain truth: envelope ctors (messages.messages, users.users, ...) are
 * ephemeral — only their descendants land in tf_* rows, so the "root" of
 * a response ingest is the first persisted descendant, and routes whose
 * root is a surrogate-key row (updates, messages) are never marked.
 */
final class IngestResponseTest extends IngestTestCase
{
    private const ACCOUNT = 7;

    private const OTHER_ACCOUNT = 8;

    private const METHOD = 'messages.getHistory';

    private const USER_ID = 501558149;

    private const CHANNEL_ID = 1737473577;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', [
            '--force' => true,
            '--realpath' => true,
            '--path' => RouteIdempotency::migrationPaths(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private static function historyParams(int $channelId = self::CHANNEL_ID): array
    {
        return [
            'peer' => ['_' => 'inputPeerChannel', 'channel_id' => $channelId, 'access_hash' => -7779317524312221622],
            'offset_id' => 0,
            'offset_date' => 0,
            'add_offset' => 0,
            'limit' => 1,
            'max_id' => 0,
            'min_id' => 0,
            'hash' => 0,
        ];
    }

    /**
     * messages.getHistory response family member messages.messages#1d73e7ea
     * carrying the full message tree plus the chats/users sidecar vectors.
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
                    // out | entities | from_id | media
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
        $root = (new UpdateIngestor())->ingestResponse(
            self::METHOD,
            self::historyParams(),
            self::messagesMessagesResponse(),
            self::ACCOUNT,
        );

        self::assertNotNull($root, 'envelope resolves to its first persisted descendant');

        // The sidecar vectors landed: the user + channel objects are addressable.
        $user = (new EntityAggregator())->user(self::ACCOUNT, self::USER_ID);
        self::assertInstanceOf(TlUser::class, $user);
        self::assertSame('Reza', $user->first_name);

        $channel = (new EntityAggregator())->channel(self::ACCOUNT, self::CHANNEL_ID);
        self::assertInstanceOf(TlChannel::class, $channel);
        self::assertSame('Teleframe Café', $channel->title);

        // The message row landed with canonical peer longs.
        $message = TlMessage::forAccount(self::ACCOUNT)->sole();
        self::assertSame(1186, (int) $message->message_id);
        self::assertSame(PeerIdTool::channelLong(self::CHANNEL_ID), (int) $message->peer_id);
        self::assertSame('Check https://t.me/teleframe from @Reza', $message->message_text);
    }

    public function test_seen_route_reingests_idempotently(): void
    {
        Event::fake([UpdateStored::class]); // fake ONLY UpdateStored (ingest path must run)

        $ingestor = new UpdateIngestor(events: $this->app['events']);
        $first = $ingestor->ingestResponse(self::METHOD, self::historyParams(), self::messagesMessagesResponse(), self::ACCOUNT);

        $counts = [
            'route' => DB::table('tl_route_messages_get_history')->count(),
            'message' => TlMessage::acrossAccounts()->count(),
            'user' => TlUser::acrossAccounts()->count(),
        ];

        $second = $ingestor->ingestResponse(self::METHOD, self::historyParams(), self::messagesMessagesResponse(), self::ACCOUNT);

        self::assertNotNull($first);
        self::assertNotNull($second, 'duplicate response resolves to the stored rows');
        self::assertSame($counts['route'], DB::table('tl_route_messages_get_history')->count(), 'no second route row');
        self::assertSame($counts['message'], TlMessage::acrossAccounts()->count(), 'no second message row');
        self::assertSame($counts['user'], TlUser::acrossAccounts()->count(), 'no second user row');

        Event::assertDispatchedTimes(UpdateStored::class, 2, 'each response ingest fires once (upsert-stable)');
    }

    public function test_routes_are_tenant_scoped(): void
    {
        $ingestor = new UpdateIngestor();
        // Use different channel IDs and message IDs per account to avoid
        // unique-constraint collisions on the scope columns.
        $otherChannelId = self::CHANNEL_ID + 1;
        $otherUserId = self::USER_ID + 1;
        $paramsA = self::historyParams();
        $paramsB = self::historyParams($otherChannelId);
        $responseA = self::messagesMessagesResponse();

        $responseB = self::messagesMessagesResponse();
        $responseB['chats'][0]['id'] = $otherChannelId;
        $responseB['users'][0]['id'] = $otherUserId;
        foreach ($responseB['messages'] as &$msg) {
            $msg['id'] = 2186;
            $msg['peer_id']['channel_id'] = $otherChannelId;
            $msg['from_id']['user_id'] = $otherUserId;
        }
        unset($msg);

        $a = $ingestor->ingestResponse(self::METHOD, $paramsA, $responseA, self::ACCOUNT);
        $b = $ingestor->ingestResponse(self::METHOD, $paramsB, $responseB, self::OTHER_ACCOUNT);

        self::assertNotNull($a);
        self::assertNotNull($b);
        self::assertSame(1, TlMessage::forAccount(self::ACCOUNT)->count(), 'each tenant stores its own message');
        self::assertSame(1, TlMessage::forAccount(self::OTHER_ACCOUNT)->count());
        self::assertSame(2, TlMessage::acrossAccounts()->count());
    }

    public function test_update_kind_payloads_bypass_routes(): void
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

        // updates.getDifference HAS a route table — the update-kind branch
        // (checked BEFORE route logic) keeps it empty.
        self::assertSame(0, DB::table('tl_route_updates_get_difference')->count(), 'update-kind payloads never touch routes');

        self::assertNotNull($first);
        self::assertNotNull($second);
        Event::assertDispatchedTimes(UpdateStored::class, 2);
    }

    public function test_methods_without_a_route_table_ingest_unconditionally(): void
    {
        // users.getUsers returns Vector<User> — the generator skips route
        // tables for generic/vector returns, so this method is unroutable.
        $response = [
            '_' => 'users.users',
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

        $ingestor = new UpdateIngestor();
        $first = $ingestor->ingestResponse('users.getUsers', ['id' => [['_' => 'inputUserSelf']]], $response, self::ACCOUNT);
        $second = $ingestor->ingestResponse('users.getUsers', ['id' => [['_' => 'inputUserSelf']]], $response, self::ACCOUNT);

        self::assertNotNull($first);
        self::assertNotNull($second, 'unrouted methods never dedup-skip');
        self::assertSame(1, TlUser::acrossAccounts()->count(), 'content upsert keeps the user row stable anyway');
    }
}
