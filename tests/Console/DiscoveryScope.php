<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Console;

use MeRezaRezaei\Teleframe\Tests\Support\FakeUserScope;

/**
 * A scope that answers `messages.getDialogs` with ONE realistic page and
 * records that it was dialled.
 *
 * The page is deliberately FULL, not minimal: it carries the `users` and
 * `chats` companion vectors, because those are the vectors the access_hashes
 * live in. A fixture that omitted them would let the suite pass while proving
 * nothing — which is exactly the trap the defect sat in, where discovery
 * "worked" on the wire and persisted nothing.
 *
 * `messages.getHistory` answers an empty slice so a `--peer` run terminates
 * immediately instead of stalling on an un-scripted page.
 */
final class DiscoveryScope extends FakeUserScope
{
    /** How many dialogs pages the walker fetched, so a second-page walk is visible if it happens. */
    public int $dialogsPages = 0;

    public const CHANNEL_ID = 1915503741;

    public const USER_ID = 8329480050;

    public const CHAT_ID = 444_000_555;

    public const CHANNEL_HASH = '9172635481029';

    public const USER_HASH = '5512378901644';

    /** @param array<string, mixed> $params */
    public function call(string $method, array $params = []): array
    {
        // Recorded here too, because the parent's own recording is bypassed on
        // the branches below and a suite that asserts "no getHistory was
        // dialled" needs a log that cannot miss one.
        $this->calls[] = ['method' => $method, 'params' => $params];

        if ($method !== 'messages.getDialogs') {
            return match ($method) {
                'messages.getHistory' => ['_' => 'messages.messagesSlice', 'count' => 0, 'messages' => []],
                default => parent::call($method, $params),
            };
        }

        $this->dialogsPages++;

        return $this->page();
    }

    /**
     * One `messages.dialogs` page (api.tl:286), carrying one channel dialog,
     * one user dialog and one basic-group dialog plus their companion vectors.
     *
     * @return array<string, mixed>
     */
    public function page(): array
    {
        return [
            '_' => 'messages.dialogs',
            'dialogs' => [
                [
                    '_' => 'dialog',
                    'peer' => ['_' => 'peerChannel', 'channel_id' => self::CHANNEL_ID],
                    'top_message' => 12424,
                    'date' => 1_712_345_600,
                    'unread_count' => 2,
                    'pinned' => true,
                ],
                [
                    '_' => 'dialog',
                    'peer' => ['_' => 'peerUser', 'user_id' => self::USER_ID],
                    'top_message' => 103356,
                    'date' => 1_712_345_500,
                    'unread_count' => 0,
                ],
                [
                    '_' => 'dialog',
                    'peer' => ['_' => 'peerChat', 'chat_id' => self::CHAT_ID],
                    'top_message' => 7,
                    'date' => 1_712_345_400,
                    'unread_count' => 0,
                ],
            ],
            'messages' => [],
            'chats' => [
                [
                    '_' => 'channel',
                    'id' => self::CHANNEL_ID,
                    'access_hash' => self::CHANNEL_HASH,
                    'title' => 'a channel',
                    'participants_count' => 12,
                    'date' => 1_600_000_000,
                ],
                // A BASIC GROUP: ctor `chat`, which by design carries no
                // access_hash and IS addressable as inputPeerChat. It is here
                // so the constructor gate on `tf_chats` can be pinned in BOTH
                // directions — `chat` resolves, `channel` must not.
                [
                    '_' => 'chat',
                    'id' => self::CHAT_ID,
                    'title' => 'a basic group',
                    'participants_count' => 5,
                    'date' => 1_500_000_000,
                ],
            ],
            'users' => [
                [
                    '_' => 'user',
                    'id' => self::USER_ID,
                    'access_hash' => self::USER_HASH,
                    'first_name' => 'Someone',
                    'last_name' => 'Readable',
                ],
            ],
        ];
    }
}
