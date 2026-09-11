<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Ingest;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Ingest\Events\MessagesDeleted;

/**
 * TDLib-style explicit delete operations: remove messages from tf_messages
 * by their Telegram message IDs. Fires MessagesDeleted so consumers
 * (Centrifugo, Redis bus, handler pipeline) can react to deletions.
 */
final class SafeDelete
{
    public function __construct(
        private readonly ?Dispatcher $events = null,
    ) {}

    /**
     * Delete messages by their Telegram IDs.
     *
     * @param list<int> $messageIds
     * @return int Number of rows actually deleted
     */
    public function deleteMessages(int $accountId, array $messageIds, ?int $peerId = null): int
    {
        if ($messageIds === []) {
            return 0;
        }

        $deleted = DB::table('tf_messages')
            ->where('account_id', $accountId)
            ->whereIn('message_id', $messageIds)
            ->delete();

        if ($deleted > 0) {
            $this->events?->dispatch(new MessagesDeleted(
                $accountId,
                $messageIds,
                $peerId,
            ));
        }

        return $deleted;
    }
}
