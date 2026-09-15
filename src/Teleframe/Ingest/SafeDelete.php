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
 *
 * Curated dial: tf_messages is keyed (account_id, id) — the Telegram
 * message id IS the `id` column (NATURAL key, not a surrogate). The legacy
 * extracted `message_id` column is gone, so deletes match on `id`.
 */
final class SafeDelete
{
    public function __construct(
        private readonly ?Dispatcher $events = null,
    ) {}

    /**
     * Delete messages by their Telegram IDs.
     *
     * @param  list<int>  $messageIds
     * @return int Number of rows actually deleted
     */
    public function deleteMessages(int $accountId, array $messageIds, ?int $peerId = null): int
    {
        if ($messageIds === []) {
            return 0;
        }

        $deleted = DB::table('tf_messages')
            ->where('account_id', $accountId)
            ->whereIn('id', $messageIds)
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
