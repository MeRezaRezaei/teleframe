<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Realtime;

/**
 * Centrifugo channel naming contract (Phase 4 plan): per-account updates
 * feed and per-chat message feeds. Everything a browser subscribes to is
 * derived from these two shapes.
 *
 *   account:{account_id}:updates          — every ingested update
 *   account:{account_id}:messages:{chat_id} — ingested messages for one chat
 *
 * Callers pass already-resolved values (int cast) — no user input ever
 * reaches a channel name raw.
 */
final class ChannelNames
{
    /** account:{accountId}:updates */
    public static function accountUpdates(int $accountId): string
    {
        return sprintf('account:%d:updates', $accountId);
    }

    /** account:{accountId}:messages:{chatId} */
    public static function accountMessages(int $accountId, int $chatId): string
    {
        return sprintf('account:%d:messages:%d', $accountId, $chatId);
    }
}