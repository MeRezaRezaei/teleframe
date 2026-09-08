<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Bus;

use Closure;
use InvalidArgumentException;
use JsonException;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Teleclient;

/**
 * The bus consumer (plan Task 2): reads one batch for the shared
 * consumer group from the updates stream and clears it:
 *
 *  - decode failure (poison payload) → re-posted verbatim to the
 *    dead-letter stream and acked — one bad entry never wedges the
 *    group;
 *  - RouteTable match → forwarded verbatim to the target stream and
 *    acked (counted as forwarded);
 *  - no route (the default path) → Teleclient::ingest into P2
 *    truth under the entry's own account_id, with the optional
 *    onStored hook fired after the row commits.
 *
 * Ingest-path failures get a bounded retry: an entry whose ingest
 * throws (unknown constructor against this build, transient DB
 * hiccup, throwing onStored hook, ...) stays un-acked and is retried
 * on the next consumeOnce() cycle; after self::INGEST_ATTEMPTS
 * consecutive throws it is dead-lettered with reason `ingest-failed`
 * and the error message, then acked — a persistently failing entry
 * can no longer wedge the group, while transient faults still get
 * their retries. The strike counter is per-instance (in-memory, see
 * self::ingestFailures): the long-lived loop/daemon reaches the cap
 * in-process; a fresh process starts the count over, which only
 * delays the dead-letter, never the group's progress.
 *
 * Every handled entry is acked before the batch ends, so a crash
 * mid-batch redelivers at most the un-acked tail.
 */
final class IngestConsumer
{
    private const BATCH = 10;

    /** Consecutive ingest-path throws per entry before dead-lettering. */
    private const INGEST_ATTEMPTS = 3;

    /** Hard ceiling on tracked strike counters (unbounded growth guard). */
    private const FAILURE_CAP = 1024;

    /** @var ?Closure(TlInstanceModel, int): void */
    private readonly ?Closure $onStored;

    /** @var ?Closure(array<string, mixed>, int): void */
    private readonly ?Closure $onRouted;

    /** @var array<string, int> stream entry id => consecutive ingest-path throws */
    private array $ingestFailures = [];

    public function __construct(
        private readonly RedisConnectionContract $redis,
        private readonly Teleclient $client,
        ?callable $onStored = null,
        ?callable $onRouted = null,
    ) {
        $this->onStored = $onStored === null ? null : $onStored(...);
        $this->onRouted = $onRouted === null ? null : $onRouted(...);
    }

    /**
     * Consume up to one batch of this consumer's own pending retries
     * (oldest work first) plus one batch of never-delivered entries,
     * and report counts. Entries whose ingest path threw stay pending
     * for the next cycle unless they hit the retry cap.
     *
     * @return array{processed: int, forwarded: int} entries fully
     *         handled (ingested, forwarded or dead-lettered) and the
     *         subset rerouted to a target stream
     */
    public function consumeOnce(): array
    {
        $batches = [
            // Own pending first: entries left un-acked by an earlier
            // cycle's ingest throw or a crash mid-batch must not be
            // starved by fresh traffic.
            $this->redis->xreadgroup(
                StreamSchema::GROUP,
                StreamSchema::CONSUMER,
                [StreamSchema::STREAM => self::BATCH],
                '0',
            )[StreamSchema::STREAM] ?? [],
            $this->redis->xreadgroup(
                StreamSchema::GROUP,
                StreamSchema::CONSUMER,
                [StreamSchema::STREAM => self::BATCH],
            )[StreamSchema::STREAM] ?? [],
        ];

        $processed = 0;
        $forwarded = 0;

        foreach ($batches as $batch) {
            foreach ($batch as $entryId => $fields) {
                if ($this->handleEntry($entryId, $fields, $forwarded)) {
                    $processed++;
                }
            }
        }

        return ['processed' => $processed, 'forwarded' => $forwarded];
    }

    /**
     * Run one entry to completion: true when it was fully handled and
     * acked (ingested, forwarded or dead-lettered); false when the
     * ingest path threw below the retry cap and the entry stays
     * pending for the next cycle.
     *
     * @param array<string, string> $fields
     */
    private function handleEntry(string $entryId, array $fields, int &$forwarded): bool
    {
        try {
            $entry = StreamSchema::decode($fields['update'] ?? '');
        } catch (JsonException | InvalidArgumentException) {
            // Poison: preserve the payload verbatim for forensics,
            // then ack so the group keeps moving.
            $this->deadLetter($fields);
            $this->ack($entryId);

            return true;
        }

        $target = (new RouteTable($this->redis))->match($entry['update']);

        if ($target !== null) {
            $this->redis->xadd($target, '*', $fields);
            if ($this->onRouted !== null) {
                // The matched entry fans into the handler pipeline too —
                // one payload shape, one pipeline (friction I.1).
                ($this->onRouted)($entry['update'], (int) $entry['account_id']);
            }
            $forwarded++;
            $this->ack($entryId);

            return true;
        }

        try {
            $root = $this->client->ingest($entry['update'], $entry['account_id']);

            if ($this->onStored !== null) {
                ($this->onStored)($root, $entry['account_id']);
            }
        } catch (\Throwable $e) {
            if (!$this->strike($entryId)) {
                return false; // retry on a later cycle; stays pending
            }

            // Cap reached: dead-letter with the error for forensics,
            // ack, keep the group moving.
            $this->deadLetter($fields, 'ingest-failed', $e->getMessage());
            $this->ack($entryId);

            return true;
        }

        unset($this->ingestFailures[$entryId]);
        $this->ack($entryId);

        return true;
    }

    /**
     * Count a consecutive ingest-path throw for the entry; true when
     * the retry cap is reached. The counter map is bounded (oldest
     * tracked entry evicted at FAILURE_CAP — it merely needs fresh
     * strikes) and cleared on the entry's first success.
     */
    private function strike(string $entryId): bool
    {
        if (count($this->ingestFailures) >= self::FAILURE_CAP
            && !isset($this->ingestFailures[$entryId])
        ) {
            unset($this->ingestFailures[(string) array_key_first($this->ingestFailures)]);
        }

        $attempts = ($this->ingestFailures[$entryId] ?? 0) + 1;
        $this->ingestFailures[$entryId] = $attempts;

        return $attempts >= self::INGEST_ATTEMPTS;
    }

    /**
     * Post an entry to the dead-letter stream: the original fields
     * verbatim, optionally tagged with a reason and error message.
     *
     * @param array<string, string> $fields
     */
    private function deadLetter(array $fields, ?string $reason = null, string $error = ''): void
    {
        $tag = $reason === null ? [] : array_filter([
            'reason' => $reason,
            'error' => $error,
        ], static fn (string $value): bool => $value !== '');

        $this->redis->xadd(StreamSchema::DL, '*', $fields + $tag);
    }

    private function ack(string $entryId): void
    {
        $this->redis->xack(StreamSchema::STREAM, StreamSchema::GROUP, [$entryId]);
    }
}
