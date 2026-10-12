<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Ingest\Events;

/**
 * Fired after messages are explicitly deleted from tf_messages
 * (TDLib updateDeleteMessages / updateDeleteChannelMessages path).
 */
final class MessagesDeleted
{
    /**
     * @param list<int> $messageIds Telegram message IDs that were deleted
     */
    public function __construct(
        public readonly int $accountId,
        public readonly array $messageIds,
        public readonly ?int $peerId = null,
    ) {}
}
