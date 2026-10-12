<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Backfill;

/**
 * In-memory peer fetch queue ported from the owner's fork
 * (Sync/FetchQueue, SQL-backed) onto plain PHP arrays — no SQL, no redis.
 *
 * Owns peer ordering, attempt accounting and the dead-letter list. The
 * backfill COMMAND layer drives it (push -> pop -> BackfillWorker::process
 * -> complete|fail) while the worker itself stays pure; per the fork a job
 * that fails is re-queued (attempts++), and after MAX_ATTEMPTS failures it
 * is dead-lettered with its reason instead of re-queued.
 */
final class FetchQueue
{
    /** Attempts before a peer is dead-lettered (mirrors the fork). */
    public const MAX_ATTEMPTS = 5;

    /** @var list<string> */
    private array $pending = [];

    /** @var array<string, int> peer => attempts so far */
    private array $attempts = [];

    /** @var array<string, string> peer => dead-letter reason */
    private array $dead = [];

    /**
     * Max fetches runnable now while reserving >= 50% of remaining quota headroom.
     * Verbatim port of the fork's slicing rule: half of the remaining quota is
     * always reserved for live traffic, and each fetch costs $costPerFetch —
     * slice = intdiv(intdiv(remaining, 2), costPerFetch). A quota that cannot
     * spare a whole cost unit issues nothing.
     */
    public static function quotaSlice(int $remaining, int $costPerFetch): int
    {
        if ($costPerFetch <= 0 || $remaining <= 0) {
            return 0;
        }

        return intdiv(intdiv($remaining, 2), $costPerFetch);
    }

    /**
     * Enqueue a peer for backfill. Idempotent: an already-queued or
     * dead-lettered peer is ignored (no zombie revival).
     */
    public function push(string $peer): void
    {
        if (isset($this->attempts[$peer])) {
            return;
        }

        $this->attempts[$peer] = 0;
        $this->pending[] = $peer;
    }

    /**
     * Restore a peer's CROSS-RUN attempt count (Cycle 17, verbatim
     * 2026-09-16: "i need the full acount hsitory in my database").
     *
     * Before this, a peer's attempts lived only in this object, so a new
     * command invocation started every peer at 0 and docs/bus.md's "dead-letter
     * after 5 attempts across runs" was not what the code did. A peer restored
     * at or past MAX_ATTEMPTS is dead-lettered IMMEDIATELY rather than granted
     * a fresh budget of lives — otherwise restoring the ledger would still lose
     * the attempt history it exists to keep.
     *
     * Unknown peers are ignored, matching push(): seeding must never invent a
     * job the caller did not ask for.
     */
    public function seed(string $peer, int $attempts, string $deadReason = ''): void
    {
        if (! isset($this->attempts[$peer]) || $attempts <= 0) {
            return;
        }

        if ($attempts >= self::MAX_ATTEMPTS) {
            $this->attempts[$peer] = $attempts;
            $this->dead[$peer] = $deadReason !== '' ? $deadReason : 'restored-exhausted';
            $this->pending = array_values(array_filter(
                $this->pending,
                static fn (string $queued): bool => $queued !== $peer,
            ));

            return;
        }

        $this->attempts[$peer] = $attempts;
    }

    /** Claim the next pending peer (FIFO), or null when the queue is empty. */
    public function pop(): ?string
    {
        /** @var string|null $peer */
        $peer = array_shift($this->pending);

        return $peer;
    }

    /**
     * Re-queue a claimed peer WITHOUT attempt accounting: the job was not
     * failed, merely deferred (e.g. the backfill command's budget stop —
     * the peer keeps its front position for the next run's resume).
     */
    public function requeue(string $peer): void
    {
        if (! isset($this->attempts[$peer])) {
            $this->attempts[$peer] = 0;
        }

        if (! in_array($peer, $this->pending, true)) {
            array_unshift($this->pending, $peer);
        }
    }

    /**
     * Re-queue a claimed peer at the BACK without attempt accounting: the
     * full-history loop uses this so one deep peer (many pages) that hits the
     * per-pass budget does not starve every other peer, while still keeping
     * its pending slot for the next pass. The resume cursor lives with the
     * caller (BackfillCommand), not the queue.
     */
    public function requeueBack(string $peer): void
    {
        if (! isset($this->attempts[$peer])) {
            $this->attempts[$peer] = 0;
        }

        if (! in_array($peer, $this->pending, true)) {
            $this->pending[] = $peer;
        }
    }

    /**
     * Record a failed run for a claimed peer: attempts++, then re-queue —
     * unless this was the MAX_ATTEMPTS-th failure, which dead-letters the
     * peer with $reason instead (mirrors the fork's fail()).
     */
    public function fail(string $peer, string $reason): void
    {
        if (! isset($this->attempts[$peer])) {
            return; // unknown job: nothing to account, like the fork's missing row
        }

        $attempts = $this->attempts[$peer] + 1;
        $this->attempts[$peer] = $attempts;

        if ($attempts >= self::MAX_ATTEMPTS) {
            $this->dead[$peer] = $reason;
            $this->pending = array_values(array_filter(
                $this->pending,
                static fn (string $queued): bool => $queued !== $peer, // a dead peer never sits in pending
            ));

            return;
        }

        if (! in_array($peer, $this->pending, true)) {
            $this->pending[] = $peer; // back to pending, behind peers already queued
        }
    }

    /** Drop a successfully finished peer from the queue (mirrors the fork's complete()). */
    public function complete(string $peer): void
    {
        unset($this->attempts[$peer]);
    }

    /** Attempts recorded for a peer (stays readable after dead-lettering). */
    public function attemptsFor(string $peer): int
    {
        return $this->attempts[$peer] ?? 0;
    }

    /** @return list<string> dead-lettered peers, in death order */
    public function deadLetters(): array
    {
        return array_keys($this->dead);
    }

    /** @return array<string, string> peer => reason for every dead letter */
    public function deadReasons(): array
    {
        return $this->dead;
    }

    public function deadLetterCount(): int
    {
        return count($this->dead);
    }

    /** @return array{pending: int, dead: int} */
    public function counts(): array
    {
        return ['pending' => count($this->pending), 'dead' => count($this->dead)];
    }
}
