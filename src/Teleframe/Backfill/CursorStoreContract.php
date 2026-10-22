<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Backfill;

/**
 * Durable backfill cursor + attempt ledger (Cycle 17, verbatim 2026-09-16
 * "i need the full acount hsitory in my database to let me work with it").
 *
 * The crawl shipped — dialog discovery + whole-update ingest — but its progress
 * did not survive the process. `$cursors` was a local array of one invocation
 * and FetchQueue's attempt counts died with the object, so a throttled walk
 * (FLOOD_WAIT_27 mid-enumeration is routine, per BackfillCommand's own
 * comment) restarted every peer at offset 0 and could never reach message id
 * 1. docs/bus.md said as much: "the offset cursor and queue state are NOT
 * persisted".
 *
 * This is the seam that makes the crawl completable. One contract, two
 * implementations — Eloquent (the shipped truth table) and memory (offline
 * tests, standalone smoke) — mirroring the shipped BackupRunIndexContract
 * pair, and for the same reason: src/Teleframe/ must stay plain-PHP
 * constructible, so nothing here touches a container or a query builder.
 *
 * Rows cross the boundary as arrays keyed by the column names, and the
 * orchestrator owns the call order, never the container.
 *
 * Peer keys are the caller-visible dialog keys (the same strings `$keys` holds),
 * NOT chat ids, so a key that cannot be resolved into an inputPeer still keeps
 * its progress instead of silently resetting.
 *
 * Zero-regex: string/type functions only.
 */
interface CursorStoreContract
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_COMPLETE = 'complete';

    public const STATUS_DEAD = 'dead';

    /**
     * Insert or replace the cursor row for {account_id, peer_key}. Idempotent
     * by identity, so re-running a peer resumes rather than duplicates.
     *
     * @param  array<string, mixed>  $row  peer_key, offset_id, attempts,
     *                                     status (active|complete|dead),
     *                                     reason, last_offset_at_unix
     */
    public function upsertCursor(int $accountId, array $row): void;

    /**
     * Every cursor row for one account, keyed by peer_key. Rows keep the
     * column names as keys. An account with no rows yields [] — never null, so
     * a first run and a lost ledger are told apart by the caller.
     *
     * @return array<string, array<string, mixed>>
     */
    public function cursorsFor(int $accountId): array;

    /**
     * One peer's row, or null when the peer has no durable progress.
     *
     * @return array<string, mixed>|null
     */
    public function findCursor(int $accountId, string $peerKey): ?array;

    /**
     * Peers whose ledger says they are finished (complete) or exhausted (dead).
     *
     * @return array<string, string> peer_key => status
     */
    public function terminalPeers(int $accountId): array;

    /**
     * Forget a peer's progress (operator escape hatch: re-crawl one dialog
     * from scratch). Returns true when a row was actually removed, so a
     * no-op is distinguishable from a real reset.
     */
    public function forget(int $accountId, string $peerKey): bool;
}
