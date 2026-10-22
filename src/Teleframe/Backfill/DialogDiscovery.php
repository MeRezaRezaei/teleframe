<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Backfill;

use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerShapeTool;

/**
 * Full-dialog discovery for history backfill (the missing half of the
 * TDLib/Telethon sync flow): paginated `messages.getDialogs` walk yielding
 * one entry per peer, so the backfill queue can be seeded with EVERY chat
 * the account has — not just the ones named on the command line.
 *
 * Why this exists: `updates.getDifference` can never replay an account's
 * whole history (Telegram bounds the update window and answers
 * `updates.differenceTooLong` once you fall behind it). The only complete
 * path is discover dialogs here, then walk each peer with
 * `messages.getHistory` down to message id 1 (that is `BackfillWorker`).
 *
 * Pagination is the standard getDialogs cursor: from the LAST dialog of a
 * page take (date, top_message, peer) and pass them as
 * `offset_date` / `offset_id` / `offset_peer` on the next call. Each
 * response carries companion `users` / `chats` arrays supplying the
 * access_hash needed to build a usable InputPeer; a peer whose hash is
 * missing is skipped (it cannot be fetched).
 *
 * Zero transport coupling: `$fetch` is injected, so the walker is fully
 * offline-testable. Zero regex per the repo rule.
 */
final class DialogDiscovery
{
    /** Wire page size (getDialogs accepts up to 100). */
    public const PAGE_LIMIT = 100;

    /** Safety cap: never loop forever on a misbehaving server. */
    public const MAX_PAGES = 10_000;

    /** @param int $pageLimit page size to request (tests lower it to exercise multi-page) */
    public function __construct(private readonly int $pageLimit = self::PAGE_LIMIT) {}

    public function pageLimit(): int
    {
        return $this->pageLimit;
    }

    /**
     * Walk every dialog the account can see.
     *
     * @param  callable(int $offsetDate, int $offsetId, array<string, mixed> $offsetPeer, int $limit): array<string, mixed>  $fetch
     *                                                                                                                               one messages.getDialogs page
     * @param  callable(array<string, mixed> $entry): void|null  $onPeer
     *                                                                    optional progress hook, invoked once per discovered peer
     * @return list<array{key: string, peer: array<string, mixed>, peer_type: int, peer_id: int, top_message: int, date: int}>
     */
    public function discover(callable $fetch, ?callable $onPeer = null): array
    {
        $entries = [];
        $seen = [];

        $offsetDate = 0;
        $offsetId = 0;
        $offsetPeer = ['_' => 'inputPeerEmpty'];

        for ($page = 0; $page < self::MAX_PAGES; $page++) {
            $response = $fetch($offsetDate, $offsetId, $offsetPeer, $this->pageLimit);

            if ((string) ($response['_'] ?? '') === 'messages.dialogsNotModified') {
                break; // server says nothing changed since `hash` — nothing to page
            }

            /** @var list<array<string, mixed>> $dialogs */
            $dialogs = is_array($response['dialogs'] ?? null) ? array_values(array_filter(
                $response['dialogs'],
                static fn ($d): bool => is_array($d),
            )) : [];

            if ($dialogs === []) {
                break;
            }

            [$users, $chats] = $this->accessHashIndex($response);

            $lastEntry = null;
            foreach ($dialogs as $dialog) {
                $entry = $this->entry($dialog, $users, $chats);
                if ($entry === null) {
                    continue; // dialogFolder / missing access_hash: not fetchable
                }
                $lastEntry = $entry;

                if (isset($seen[$entry['key']])) {
                    continue; // defensive: a cursor that does not advance must not duplicate peers
                }
                $seen[$entry['key']] = true;
                $entries[] = $entry;

                if ($onPeer !== null) {
                    $onPeer($entry);
                }
            }

            if ($lastEntry === null) {
                break; // page carried no usable dialog: cannot advance the cursor safely
            }

            $offsetDate = $lastEntry['date'];
            $offsetId = $lastEntry['top_message'];
            $offsetPeer = $lastEntry['peer'];

            if (count($dialogs) < $this->pageLimit) {
                break; // short page = final page
            }
        }

        return $entries;
    }

    /**
     * Build the peer-id → access_hash indexes from a dialogs response's
     * companion arrays.
     *
     * @param  array<string, mixed>  $response
     * @return array{0: array<int, string>, 1: array<int, string>} [users, chats]
     */
    private function accessHashIndex(array $response): array
    {
        $users = [];
        foreach ((array) ($response['users'] ?? []) as $user) {
            if (is_array($user) && isset($user['id'])) {
                $users[(int) $user['id']] = (string) ($user['access_hash'] ?? '0');
            }
        }

        $chats = [];
        foreach ((array) ($response['chats'] ?? []) as $chat) {
            if (is_array($chat) && isset($chat['id'])) {
                $chats[(int) $chat['id']] = (string) ($chat['access_hash'] ?? '0');
            }
        }

        return [$users, $chats];
    }

    /**
     * One dialog → a fetchable peer entry, or null when unfetchable.
     *
     * @param  array<string, mixed>  $dialog
     * @param  array<int, string>  $users
     * @param  array<int, string>  $chats
     * @return array{key: string, peer: array<string, mixed>, peer_type: int, peer_id: int, top_message: int, date: int}|null
     */
    private function entry(array $dialog, array $users, array $chats): ?array
    {
        $raw = $dialog['peer'] ?? null;
        if (! is_array($raw)) {
            return null;
        }

        [$type, $id] = PeerShapeTool::normalize($raw);
        if ($id === 0) {
            return null;
        }

        $peer = match ($type) {
            PeerShapeTool::PEER_USER => isset($users[$id])
                ? ['_' => 'inputPeerUser', 'user_id' => $id, 'access_hash' => $users[$id]]
                : null,
            PeerShapeTool::PEER_CHAT => ['_' => 'inputPeerChat', 'chat_id' => $id],
            PeerShapeTool::PEER_CHANNEL => isset($chats[$id])
                ? ['_' => 'inputPeerChannel', 'channel_id' => $id, 'access_hash' => $chats[$id]]
                : null,
            default => null,
        };

        if ($peer === null) {
            return null; // no access_hash: getHistory would be rejected
        }

        return [
            'key' => $this->peerKey($peer),
            'peer' => $peer,
            'peer_type' => $type,
            'peer_id' => $id,
            'top_message' => (int) ($dialog['top_message'] ?? 0),
            'date' => (int) ($dialog['date'] ?? 0),
        ];
    }

    /** @param array<string, mixed> $peer */
    private function peerKey(array $peer): string
    {
        return match ((string) ($peer['_'] ?? '')) {
            'inputPeerUser' => 'user:'.(int) ($peer['user_id'] ?? 0),
            'inputPeerChat' => 'chat:'.(int) ($peer['chat_id'] ?? 0),
            'inputPeerChannel' => 'channel:'.(int) ($peer['channel_id'] ?? 0),
            'inputPeerSelf' => 'self',
            default => 'peer:0',
        };
    }
}
