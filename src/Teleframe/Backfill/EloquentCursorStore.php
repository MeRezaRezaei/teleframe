<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Backfill;

use Illuminate\Support\Facades\DB;

/**
 * Eloquent CursorStoreContract (Cycle 17): the tf_backfill_cursors truth table
 * (migration 2026_09_25_000300).
 *
 * The table carries a composite key (account_id + peer_key), which Eloquent's
 * Model save()/updateOrCreate() cannot target without a scalar PK, so writes go
 * through the query builder's natural-key upsert (updateOrInsert) exactly as
 * EloquentStoreIndex does for tf_store_buckets. Rows cross the boundary as
 * arrays keyed by the DB column names.
 *
 * Peer keys are dialog keys, not chat ids: a key that no longer resolves into
 * an inputPeer still keeps its progress instead of silently restarting at 0.
 *
 * Zero-regex: string/type functions only.
 */
final class EloquentCursorStore implements CursorStoreContract
{
    public function upsertCursor(int $accountId, array $row): void
    {
        $peerKey = trim((string) ($row['peer_key'] ?? ''));
        if ($peerKey === '') {
            return; // a row with no identity is not a row
        }

        $existing = $this->findCursor($accountId, $peerKey);
        $stored = (int) ($existing['offset_id'] ?? 0);
        $incoming = (int) ($row['offset_id'] ?? 0);

        // Telegram history walks DESC: the resume cursor is the OLDEST
        // (smallest) id fetched, and it DECREASES as the crawl goes deeper. A
        // rewind guard therefore keeps the MINIMUM (deepest progress). A max
        // would pin the cursor to the newest page and re-crawl the whole
        // history from the top. A 0 offset means "nothing crawled yet" and must
        // never win over a real offset in either direction.
        $offset = match (true) {
            $stored === 0 => max(0, $incoming),
            $incoming === 0 => $stored,
            default => min($stored, $incoming),
        };

        DB::table('tf_backfill_cursors')->updateOrInsert(
            ['account_id' => $accountId, 'peer_key' => $peerKey],
            [
                'offset_id' => $offset,
                'attempts' => (int) ($row['attempts'] ?? 0),
                'status' => (string) ($row['status'] ?? self::STATUS_ACTIVE),
                'reason' => (string) ($row['reason'] ?? ''),
                'last_offset_at_unix' => (int) ($row['last_offset_at_unix'] ?? time()),
            ],
        );
    }

    public function cursorsFor(int $accountId): array
    {
        $rows = DB::table('tf_backfill_cursors')
            ->where('account_id', $accountId)
            ->orderBy('peer_key')
            ->get();

        $cursors = [];
        foreach ($rows as $row) {
            $row = (array) $row;
            $cursors[(string) $row['peer_key']] = $row;
        }

        return $cursors;
    }

    public function findCursor(int $accountId, string $peerKey): ?array
    {
        $row = DB::table('tf_backfill_cursors')
            ->where('account_id', $accountId)
            ->where('peer_key', $peerKey)
            ->first();

        return $row === null ? null : (array) $row;
    }

    public function terminalPeers(int $accountId): array
    {
        $rows = DB::table('tf_backfill_cursors')
            ->where('account_id', $accountId)
            ->whereIn('status', [self::STATUS_COMPLETE, self::STATUS_DEAD])
            ->orderBy('peer_key')
            ->get();

        $terminal = [];
        foreach ($rows as $row) {
            $row = (array) $row;
            $terminal[(string) $row['peer_key']] = (string) $row['status'];
        }

        return $terminal;
    }

    public function forget(int $accountId, string $peerKey): bool
    {
        return DB::table('tf_backfill_cursors')
            ->where('account_id', $accountId)
            ->where('peer_key', $peerKey)
            ->delete() > 0;
    }
}
