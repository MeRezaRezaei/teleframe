<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Backfill;

/**
 * In-memory backfill cursor ledger (Cycle 17): the offline/standalone half of
 * the CursorStoreContract pair, exactly as MemoryHotCache and MemoryStoreIndex
 * are the offline halves of their own contracts.
 *
 * This is what makes the durability testable at all — the two-invocation resume
 * test drives a real BackfillCommand twice against this store and asserts the
 * second run issues getHistory at the STORED offset, never 0. Against a real
 * database that test would be testing the database.
 *
 * Same semantics as the Eloquent half, deliberately: a first run and a lost
 * ledger both read as [], and forget() distinguishes a real reset from a no-op.
 *
 * Zero-regex: string/type functions only.
 */
final class MemoryCursorStore implements CursorStoreContract
{
    /** @var array<int, array<string, array<string, mixed>>> account => peer => row */
    private array $rows = [];

    /** @var callable(): int */
    private $now;

    public function __construct(?callable $now = null)
    {
        $this->now = $now ?? static fn (): int => time();
    }

    /**
     * The deepest progress known for a peer, as a DESC resume cursor.
     *
     * A stored offset of 0 means "nothing crawled yet" (the wire's start), so
     * it must never win over a real offset. Otherwise the min() would latch on
     * the untouched 0 and refuse to move — the same silent no-progress failure
     * as a rewind, in the other direction.
     */
    private function furthest(int $accountId, string $peerKey, int $incoming): int
    {
        $stored = (int) ($this->rows[$accountId][$peerKey]['offset_id'] ?? 0);

        if ($stored === 0) {
            return max(0, $incoming);
        }

        if ($incoming === 0) {
            return $stored;
        }

        return min($stored, $incoming);
    }

    public function upsertCursor(int $accountId, array $row): void
    {
        $peerKey = trim((string) ($row['peer_key'] ?? ''));
        if ($peerKey === '') {
            return; // a row with no identity is not a row; ignore rather than corrupt
        }


        $this->rows[$accountId][$peerKey] = [
            'account_id' => $accountId,
            'peer_key' => $peerKey,
            // Telegram history walks DESC: the resume cursor is the OLDEST
            // (smallest) id fetched, and it DECREASES as the crawl goes deeper.
            // So a rewind guard keeps the MINIMUM (deepest progress), not the
            // max — a max would pin the cursor to the newest page and re-crawl
            // the whole history from the top, which is exactly the bug the
            // ledger exists to prevent.
            'offset_id' => $this->furthest($accountId, $peerKey, (int) ($row['offset_id'] ?? 0)),
            'attempts' => (int) ($row['attempts'] ?? 0),
            'status' => (string) ($row['status'] ?? CursorStoreContract::STATUS_ACTIVE),
            'reason' => (string) ($row['reason'] ?? ''),
            'last_offset_at_unix' => (int) ($row['last_offset_at_unix'] ?? ($this->now)()),
        ];
    }

    public function cursorsFor(int $accountId): array
    {
        return $this->rows[$accountId] ?? [];
    }

    public function findCursor(int $accountId, string $peerKey): ?array
    {
        return $this->rows[$accountId][$peerKey] ?? null;
    }

    public function terminalPeers(int $accountId): array
    {
        $terminal = [];
        foreach ($this->rows[$accountId] ?? [] as $peerKey => $row) {
            $status = (string) ($row['status'] ?? '');
            if ($status === CursorStoreContract::STATUS_COMPLETE || $status === CursorStoreContract::STATUS_DEAD) {
                $terminal[$peerKey] = $status;
            }
        }

        return $terminal;
    }

    public function forget(int $accountId, string $peerKey): bool
    {
        if (! isset($this->rows[$accountId][$peerKey])) {
            return false;
        }

        unset($this->rows[$accountId][$peerKey]);

        return true;
    }
}
