<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Backfill;

use Closure;
use MeRezaRezaei\Teleframe\Core\Exceptions\Rpc\FloodWaitException;
use MeRezaRezaei\Teleframe\Core\Exceptions\Rpc\RpcErrorException;
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

    /** Backoff slice: flood-wait sleep is chunked so a stop flag lands within ~1s. */
    private const SLEEP_CHUNK_SECONDS = 1;

    /** Consecutive failed fetches (flood or generic) before a run goes dead (mirrors FetchQueue::MAX_ATTEMPTS). */
    public const MAX_ATTEMPTS = 5;

    /**
     * Ceiling on the detail folded into a failure reason, so
     * `fetch_failed: <detail>` stays inside the `varchar(255)` of
     * `tf_backfill_cursors.reason` even with the 13-char prefix.
     */
    private const REASON_DETAIL_LIMIT = 180;

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
     * Throwable retries immediately. The flood backoff is chunked into 1s
     * slices, so a $shouldStop flag flipping mid-wait is honored within
     * ~1s (the run returns with status 'budget' / reason 'stopped') while
     * the total slept stays identical when no stop lands. Five CONSECUTIVE
     * failed fetches end the run as 'dead' (no sleep on the killing
     * attempt); one successful fetch resets the streak. An empty page ends
     * the run as 'done'. Dead-letter persistence (attempt counting across
     * runs) lives in FetchQueue::fail, driven by the command layer that owns
     * the queue — this method stays pure and just reports why it stopped.
     *
     * @param  callable(int $offsetId, int $limit): list<array<string, mixed>>  $fetch
     *                                                                                  one getHistory page (DESC); rows carry an int 'id'; empty page = history exhausted
     * @param  callable(list<array<string, mixed>> $messages): void  $onBatch  receives each non-empty page once, oldest-side cursor not yet advanced
     * @param  (callable(): bool)|null  $shouldStop  optional stop flag checked at the loop
     *                                               top and between flood-wait sleep slices; when it flips the run halts
     *                                               with status 'budget' / reason 'stopped' and the peer stays resumable
     * @return array{messages: int, requests: int, offset_id: int, status: 'done'|'budget'|'dead', reason: string|null}
     *                                                                                                                  offset_id is the resume cursor (oldest id fetched, or the caller's start on no progress)
     */
    public function process(
        string $peer,
        callable $fetch,
        callable $onBatch,
        int $offsetId = 0,
        ?callable $shouldStop = null,
    ): array {
        $maxRequests = FetchQueue::quotaSlice($this->requestBudget, 1); // cost per fetch = 1 request
        $requests = 0;
        $messages = 0;
        $failStreak = 0;
        $stop = $shouldStop !== null ? Closure::fromCallable($shouldStop) : static fn (): bool => false;

        while ($requests < $maxRequests) {
            if ($stop()) {
                return $this->result($messages, $requests, $offsetId, 'budget', 'stopped');
            }

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

                $this->interruptibleSleep(min($e->seconds, $this->floodCapSeconds), $stop);

                if ($stop()) {
                    return $this->result($messages, $requests, $offsetId, 'budget', 'stopped');
                }

                continue; // retry the same offset after the wait
            } catch (Throwable $e) {
                $requests++;
                $failStreak++;

                if ($failStreak >= self::MAX_ATTEMPTS) {
                    return $this->result($messages, $requests, $offsetId, 'dead', self::failureReason($e));
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
     * A reason an operator can ACT on, not a category they can only guess at.
     *
     * The bare constant this replaces ('fetch_failed') is why the live
     * hashless-peer run stayed undiagnosable for as long as it did: the wire
     * error name that distinguishes CHANNEL_INVALID (a peer Telegram cannot
     * resolve, i.e. no access_hash) from CHAT_WRITE_FORBIDDEN (a peer it can)
     * lived on the exception, one line above a `return` that discarded it.
     * Every failure then reported identically, so "the peer had no hash" and
     * "the account may not read that chat" became the same report.
     *
     * An `RpcErrorException` is reduced to its TYPED WIRE NAME
     * (`rpcErrorMessage`, populated by RpcExceptionResolver) because that is
     * the only stable, greppable identity the failure has — its `getMessage()`
     * also carries a doc hint and a method name that vary per call site. Any
     * other throwable keeps its class and message, which is still strictly
     * more than the constant.
     *
     * BOUNDED ON PURPOSE. This string is persisted verbatim into
     * `tf_backfill_cursors.reason`, a `varchar(255)`, by the command layer; an
     * unbounded exception message would overflow the ledger it exists to
     * explain. No regex is used (src/Teleframe/ is zero-regex): instanceof and
     * mb_substr are enough.
     */
    private static function failureReason(Throwable $e): string
    {
        $detail = $e instanceof RpcErrorException
            ? mb_substr($e->rpcErrorMessage, 0, self::REASON_DETAIL_LIMIT)
            : mb_substr($e::class.': '.$e->getMessage(), 0, self::REASON_DETAIL_LIMIT);

        return $detail === '' ? 'fetch_failed' : 'fetch_failed: '.$detail;
    }

    /**
     * Sleep in stop-responsive 1s chunks so a stop flag is honored within
     * ~1s; the production sleeper is real sleep(), tests inject a recorder.
     * With no stop the slices sum to the exact original duration.
     *
     * @param  Closure(): bool  $shouldStop
     */
    private function interruptibleSleep(int $seconds, Closure $shouldStop): void
    {
        for ($slept = 0; $slept < $seconds && ! $shouldStop(); $slept += self::SLEEP_CHUNK_SECONDS) {
            ($this->sleep)(self::SLEEP_CHUNK_SECONDS);
        }
    }

    /**
     * @param  'done'|'budget'|'dead'  $status
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
