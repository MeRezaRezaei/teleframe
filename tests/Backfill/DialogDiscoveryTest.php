<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Backfill;

use MeRezaRezaei\Teleframe\Backfill\DialogDiscovery;
use PHPUnit\Framework\TestCase;

/**
 * DialogDiscovery: the paginated messages.getDialogs walk that enumerates
 * EVERY peer for full-history backfill. Offline via an injected page
 * callable — no transport.
 */
final class DialogDiscoveryTest extends TestCase
{
    public function test_walks_pages_via_the_last_dialog_cursor(): void
    {
        $pages = [
            [
                '_' => 'messages.dialogsSlice',
                'count' => 3,
                'dialogs' => [
                    ['_' => 'dialog', 'peer' => ['_' => 'peerChannel', 'channel_id' => 100], 'top_message' => 10, 'date' => 50],
                    ['_' => 'dialog', 'peer' => ['_' => 'peerUser', 'user_id' => 7], 'top_message' => 20, 'date' => 40],
                ],
                'chats' => [['_' => 'channel', 'id' => 100, 'access_hash' => '555']],
                'users' => [['_' => 'user', 'id' => 7, 'access_hash' => '777']],
            ],
            [
                '_' => 'messages.dialogsSlice',
                'count' => 3,
                'dialogs' => [
                    ['_' => 'dialog', 'peer' => ['_' => 'peerChat', 'chat_id' => 9], 'top_message' => 30, 'date' => 30],
                ],
                'chats' => [['_' => 'chat', 'id' => 9]],
                'users' => [],
            ],
        ];

        $calls = [];
        $fetch = function (int $offsetDate, int $offsetId, array $offsetPeer, int $limit) use (&$calls, &$pages): array {
            $calls[] = [$offsetDate, $offsetId, $offsetPeer['_'] ?? '', $limit];

            return array_shift($pages) ?? ['_' => 'messages.dialogs', 'dialogs' => [], 'users' => [], 'chats' => []];
        };

        $entries = (new DialogDiscovery(2))->discover($fetch);

        self::assertCount(3, $entries);
        self::assertSame(['channel:100', 'user:7', 'chat:9'], array_column($entries, 'key'));
        self::assertSame([3, 1, 2], array_column($entries, 'peer_type'));

        // First call: empty cursor. Second call resumes from the LAST dialog
        // of page one (date 40, top_message 20, that dialog's peer).
        self::assertSame([0, 0, 'inputPeerEmpty', 2], $calls[0]);
        self::assertSame(40, $calls[1][0]);
        self::assertSame(20, $calls[1][1]);
        self::assertSame('inputPeerUser', $calls[1][2]);
    }

    public function test_skips_dialogs_without_access_hash(): void
    {
        $entries = (new DialogDiscovery)->discover(fn (): array => [
            '_' => 'messages.dialogsSlice',
            'count' => 2,
            'dialogs' => [
                ['_' => 'dialog', 'peer' => ['_' => 'peerChannel', 'channel_id' => 100], 'top_message' => 1, 'date' => 1],
                ['_' => 'dialog', 'peer' => ['_' => 'peerUser', 'user_id' => 7], 'top_message' => 2, 'date' => 2],
            ],
            'chats' => [], // channel 100 hash missing → unfetchable
            'users' => [['_' => 'user', 'id' => 7, 'access_hash' => '777']],
        ]);

        self::assertCount(1, $entries);
        self::assertSame('user:7', $entries[0]['key']);
    }

    public function test_stops_on_dialogs_not_modified(): void
    {
        $calls = 0;
        $entries = (new DialogDiscovery)->discover(function () use (&$calls): array {
            $calls++;

            return ['_' => 'messages.dialogsNotModified'];
        });

        self::assertSame([], $entries);
        self::assertSame(1, $calls);
    }

    public function test_short_page_ends_the_walk(): void
    {
        $calls = 0;
        $entries = (new DialogDiscovery)->discover(function () use (&$calls): array {
            $calls++;

            return [
                '_' => 'messages.dialogsSlice',
                'count' => 1,
                'dialogs' => [
                    ['_' => 'dialog', 'peer' => ['_' => 'peerUser', 'user_id' => 7], 'top_message' => 5, 'date' => 5],
                ],
                'users' => [['_' => 'user', 'id' => 7, 'access_hash' => '777']],
                'chats' => [],
            ];
        });

        self::assertSame(1, $calls, 'a sub-limit page is the final page');
        self::assertCount(1, $entries);
    }

    public function test_duplicate_peers_are_deduplicated(): void
    {
        $page = [
            '_' => 'messages.dialogsSlice',
            'count' => 2,
            'dialogs' => [
                ['_' => 'dialog', 'peer' => ['_' => 'peerUser', 'user_id' => 7], 'top_message' => 5, 'date' => 5],
                ['_' => 'dialog', 'peer' => ['_' => 'peerUser', 'user_id' => 7], 'top_message' => 5, 'date' => 5],
            ],
            'users' => [['_' => 'user', 'id' => 7, 'access_hash' => '777']],
            'chats' => [],
        ];

        $calls = 0;
        $entries = (new DialogDiscovery)->discover(function () use (&$calls, $page): array {
            $calls++;

            return $calls === 1 ? $page : ['_' => 'messages.dialogs', 'dialogs' => [], 'users' => [], 'chats' => []];
        });

        self::assertCount(1, $entries);
    }
}
