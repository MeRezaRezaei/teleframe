<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Backfill;

use Closure;
use MeRezaRezaei\Teleframe\Core\Exceptions\Rpc\FloodWaitException;
use Throwable;

/**
 * Quota-sliced history backfill loop ported from the owner's fork
 * (Sync/BackfillWorker + Sync/FetchQueue quota math) onto plain sync PHP:
 * no amphp, no event loop, no redis. Callables inject the transport, so
 * the loop is fully offline-testable.
 *
 * Headroom rule (mirrored from the fork's FetchQueue::quotaSlice): "Max
 * fetches runnable now while reserving >= 50% of remaining quota headroom."
 * A run may start at most intdiv(requestBudget, 2) requests — the other
 * half of the per-run request budget is always reserved for live traffic,
 * so a budget that cannot be halved into a whole fetch (e.g. 1) issues no
 * requests at all.
 */
final class BackfillWorker
{
    private const PAGE_SIZE = 100;

    /** Consecutive failed fetches (flood or generic) before a run goes dead (mirrors FetchQueue::MAX_ATTEMPTS). */
    public const MAX_ATTEMPTS = 5;

    private readonly int $requestBudget;

    private readonly int $floodCapSeconds;

    private readonly Closure $sleep;

    public function __construct(int $requestBudget, int $floodCapSeconds = 3600, ?callable $sleep = null)
    {
        $this->requestBudget = $requestBudget;
        $this->floodCapSeconds = $floodCapSeconds;
        $this->sleep = $sleep !== null
            ? Closure::fromCallable($sleep)
            : Closure::fromCallable('sleep'); // production default: real blocking sleep
    }

    /**
     * Backfill one peer's history with an offset_id cursor loop
     * (messages.getHistory order: pages arrive newest -> oldest; the next
     * offset is the oldest id of the batch just fetched).
     *
     * Budget is counted in requests (per-message cost 0): EVERY $fetch
     * invocation — success, FLOOD_WAIT or generic failure — consumes one
     * request, and page size is min(remaining request budget, 100).
     *
     * FLOOD_WAIT from $fetch sleeps min(seconds, floodCapSeconds) through
     * the injected sleeper, then retries the SAME offset; any other
     * Throwable retries immediately. Five CONSECUTIVE failed fetches end
     * the run as 'dead' (no sleep on the killing attempt); one successful
     * fetch resets the streak. An empty page ends the run as 'done'. Dead-
     * letter persistence (attempt counting across runs) lives in
     * FetchQueue::fail, driven by the command layer that owns the queue —
     * this method stays pure and just reports why it stopped.
     *
     * @param callable(int $offsetId, int $limit): list<array<string, mixed>> $fetch
     *        one getHistory page (DESC); rows carry an int 'id'; empty page = history exhausted
     * @param callable(list<array<string, mixed>> $messages): void $onBatch receives each non-empty page once, oldest-side cursor not yet advanced
     *
     * @return array{messages: int, requests: int, offset_id: int, status: 'done'|'budget'|'dead', reason: string|null}
     *         offset_id is the resume cursor (oldest id fetched, or the caller's start on no progress)
     */
    public function process(string $peer, callable $fetch, callable $onBatch, int $offsetId = 0): array
    {
        $maxRequests = FetchQueue::quotaSlice($this->requestBudget, 1); // cost per fetch = 1 request
        $requests = 0;
        $messages = 0;
        $failStreak = 0;

        while ($requests < $maxRequests) {
            $limit = min($maxRequests - $requests, self::PAGE_SIZE);

            try {
                /** @var list<array<string, mixed>> $page */
                $page = $fetch($offsetId, $limit);
            } catch (FloodWaitException $e) {
                $requests++;
                $failStreak++;

                if ($failStreak >= self::MAX_ATTEMPTS) {
                    return $this->result($messages, $requests, $offsetId, 'dead', 'flood_wait');
                }

                ($this->sleep)(min($e->seconds, $this->floodCapSeconds));

                continue; // retry the same offset after the wait
            } catch (Throwable) {
                $requests++;
                $failStreak++;

                if ($failStreak >= self::MAX_ATTEMPTS) {
                    return $this->result($messages, $requests, $offsetId, 'dead', 'fetch_failed');
                }

                continue; // gradual: retry, budget still ticks
            }

            $requests++;

            if ($page === []) {
                return $this->result($messages, $requests, $offsetId, 'done', null);
            }

            $failStreak = 0;
            $onBatch($page);
            $messages += count($page);
            /** @var int $lastId */
            $lastId = $page[count($page) - 1]['id'];
            $offsetId = $lastId;
        }

        return $this->result($messages, $requests, $offsetId, 'budget', 'request_budget_exhausted');
    }

    /**
     * @param 'done'|'budget'|'dead' $status
     *
     * @return array{messages: int, requests: int, offset_id: int, status: 'done'|'budget'|'dead', reason: string|null}
     */
    private function result(int $messages, int $requests, int $offsetId, string $status, ?string $reason): array
    {
        return [
            'messages' => $messages,
            'requests' => $requests,
            'offset_id' => $offsetId,
            'status' => $status,
            'reason' => $reason,
        ];
    }
}
