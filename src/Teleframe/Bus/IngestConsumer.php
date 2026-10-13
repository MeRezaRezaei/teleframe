<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Bus;

use Closure;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use JsonException;
use LogicException;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Teleclient;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * The bus consumer (plan Task 2): reads one batch for the shared
 * consumer group from the updates stream and clears it:
 *
 *  - decode failure (poison payload) → re-posted verbatim to the
 *    dead-letter stream and acked — one bad entry never wedges the
 *    group;
 *  - RouteTable match → forwarded verbatim to the target stream and
 *    acked (counted as forwarded);
 *  - no route (the default path) → ingested into truth under the
 *    entry's own account_id, with the optional onStored hook fired
 *    after the row commits. The ingest half is a seam: without a
 *    mirrorIngester this is Teleclient::ingest into P2 JSONB truth
 *    (BC); with one, the entry goes through the NF5 mirror pipeline
 *    (observe → relational DB updated → Eloquent models — the
 *    verbatim's daily loop, cycles 10–12).
 *
 * Ingest-path failures get a bounded retry: an entry whose ingest
 * throws (unknown constructor against this build, transient DB
 * hiccup, throwing onStored hook, ...) stays un-acked and is retried
 * on the next consumeOnce() cycle; after self::INGEST_ATTEMPTS
 * consecutive throws it is dead-lettered with reason `ingest-failed`
 * and the error message, then acked — a persistently failing entry
 * can no longer wedge the group, while transient faults still get
 * their retries. The strike counter persists in Redis instead of
 * process memory: StreamSchema::STRIKES_KEY is a hash keyed by stream
 * entry id with a whole-key TTL (self::STRIKE_TTL), so a restarted
 * consumer keeps the tally and an entry that already crossed the cap
 * goes straight to the dead-letter on its next failure — no infinite
 * retry loop across restarts. The field is reset to "0" on the
 * entry's first success.
 *
 * Resilience seams delivered on top (bus-consumer audit):
 *
 *  - reclaimStalePending(): XPENDING reports the group's pending
 *    backlog, then XAUTOCLAIM (min-idle self::RECLAIM_IDLE_MS)
 *    transfers the entries a died consumer left pending — crash
 *    orphans — into this consumer with a valid claim, so they are
 *    re-processed by the same handler instead of staying PENDING
 *    forever. Where the Redis connection does not expose the claim
 *    primitives (LaravelRedisAdapter today), the consumer-group's own
 *    pending re-read (batch `0`) remains the fallback reclaim for
 *    same-name orphans.
 *  - replayDeadLetter(): operator-triggered, synchronous re-delivery
 *    of dead-lettered entries back onto the main stream (same
 *    payload, re-routed through the live route table at consume time).
 *
 * Every handled entry is acked before the batch ends, so a crash
 * mid-batch redelivers at most the un-acked tail.
 */
final class IngestConsumer
{
    private const BATCH = 10;

    /** Consecutive ingest-path throws per entry before dead-lettering. */
    private const INGEST_ATTEMPTS = 3;

    /** Idle time (ms) after which a pending entry counts as stale and is reclaimed. */
    private const RECLAIM_IDLE_MS = 60_000;

    /** TTL (s) on the whole strike-count hash — self-cleaning on inactivity. */
    private const STRIKE_TTL = 3600;

    /** Dead-letter replay window per xrange pass (entries deleted as replayed). */
    private const DL_REPLAY_BATCH = 100;

    /**
     * The bus entry version that predates the `v` discriminator.
     *
     * A v1 entry is `{account_id, update, ts}` and carries no version field at
     * all, so an absent `v` IS v1 — absence is the discriminator, not a missing
     * value to reject.
     */
    private const ENTRY_V1 = 1;

    /**
     * The first entry version that names a committed row instead of carrying a
     * payload, and therefore must never take the mirror seam.
     */
    private const ENTRY_V2 = 2;

    /**
     * Approximate bound on the dead-letter stream. A dead-letter is an
     * operator artifact, not a queue: it is replayed or inspected by hand.
     * Unbounded, it is a slow leak that is invisible until Redis is under
     * memory pressure — and the entries nobody looked at are exactly the
     * ones a postmortem needs.
     */
    private const DL_MAXLEN = 10_000;

    /** @var ?Closure(Model, int): void fired only for a stored root — same gate as UpdateStored. */
    private readonly ?Closure $onStored;

    /** @var ?Closure(array<string, mixed>, int): void
     *  Fires for a ROUTED entry (one that matched the bus route table) and
     *  not for the default ingest path. The 2026-09-26 shipped daemon used
     *  to pass null here, which silently dropped every routed update before
     *  the handler pipeline could see it: a declarative app could never
     *  answer a routed update even though the route table said the host
     *  wanted it. Host apps point this at a HandlerSink. */
    private readonly ?Closure $onRouted;

    /** @var ?Closure(array<string, mixed>, int): void */
    private readonly ?Closure $mirrorIngester;

    private readonly LoggerInterface $logger;

    /**
     * This process's identity inside the consumer group.
     *
     * It used to be the shared literal `ingest-1` for every process, and that
     * made more than one ingest worker silently unsafe. A consumer group's
     * pending list is tracked PER CONSUMER NAME, so with one shared name two
     * workers each see the other's un-acked in-flight entries as their OWN
     * pending: worker B re-reads and reprocesses what worker A is still
     * working on, so the same update is stored twice and `UpdateStored` fires
     * twice into listeners that are not idempotent (a Centrifugo publish, a
     * TStore write, a Passport link). Worse, reclaimStalePending() runs
     * XAUTOCLAIM over the whole group, so a worker that is merely SLOW — a
     * large batch, a slow DB — has its in-flight entries stolen by the other
     * worker once they pass the idle threshold, and the work is done twice.
     *
     * A per-process name fixes both: each worker owns its own pending list,
     * and reclaim is scoped to a name that is genuinely not making progress.
     * The single-worker case is unchanged in every way that matters — a unique
     * name still reads its own pending list and still reclaims orphans.
     *
     * NOT configurable via config on purpose: a shared, misconfigured name is
     * the exact failure being removed, and the host that genuinely wants a
     * stable identity (a container that must resume its own name) can pass
     * one to the constructor.
     */
    private readonly string $consumerName;

    /** Monotonic per-process instance counter; see defaultConsumerName(). */
    private static int $instanceCounter = 0;

    public function __construct(
        private readonly RedisConnectionContract $redis,
        private readonly Teleclient $client,
        ?callable $onStored = null,
        ?callable $onRouted = null,
        ?callable $mirrorIngester = null,
        ?LoggerInterface $logger = null,
        ?string $consumerName = null,
    ) {
        $this->onStored = $onStored === null ? null : $onStored(...);
        $this->onRouted = $onRouted === null ? null : $onRouted(...);
        $this->mirrorIngester = $mirrorIngester === null ? null : $mirrorIngester(...);
        $this->logger = $logger ?? new NullLogger;
        $this->consumerName = $consumerName ?? self::defaultConsumerName();
    }

    /**
     * A name unique to THIS process, built from the frozen base plus enough
     * entropy to separate workers on one host and pods on a fleet.
     *
     * Zero-regex, and never contains a space, so it is a legal Redis consumer
     * name without escaping.
     */
    public static function defaultConsumerName(): string
    {
        $host = gethostname();

        if ($host === false || $host === '') {
            $host = 'host';
        }

        // The instance counter is what makes the guarantee unconditional.
        // Host+pid separates PROCESSES, which is the common case, but a host
        // can legitimately build two consumers in one process (a worker doing
        // double duty, a test), and host+pid alone would collapse them into
        // one name — the exact defect being removed. The counter is a static,
        // so every instance in this process gets a distinct suffix.
        self::$instanceCounter++;

        return StreamSchema::CONSUMER.'-'.self::sanitiseName($host).'-'.getmypid().'-'.self::$instanceCounter;
    }

    /**
     * Keep only characters a Redis consumer name may contain.
     *
     * A character loop, not a regex, because src/Teleframe/** is zero-regex by
     * rule and phpstan enforces it. A hostname is normally already restricted
     * to [A-Za-z0-9.-] by DNS, but `hostname` is settable, and a stray space
     * or colon here would produce a name the operator cannot grep for.
     */
    private static function sanitiseName(string $value): string
    {
        $allowed = 'abcdefghijklmnopqrstuvwxyz0123456789_.-';
        $out = '';
        $length = strlen($value);

        for ($i = 0; $i < $length && strlen($out) < 24; $i++) {
            $char = strtolower($value[$i]);

            if (str_contains($allowed, $char)) {
                $out .= $char;
            }
        }

        return $out === '' ? 'host' : $out;
    }

    /**
     * Make sure the stream and its consumer group exist, tolerating a group
     * that is already there.
     *
     * XREADGROUP throws NOGROUP on a stream it has never seen, so a consumer
     * that starts before anything has published dies on its first batch. The
     * daemon created its own group inline; `teleframe:ingest` — the
     * separate-process deployment the docs recommend — did not, so the
     * documented setup failed instantly against a fresh Redis. The invariant
     * lives here so the two commands cannot drift again.
     *
     * MKSTREAM creates the stream too, so a first run needs no publisher.
     */
    public function ensureGroup(): bool
    {
        try {
            $this->redis->command('xgroup', [
                'CREATE',
                StreamSchema::STREAM,
                StreamSchema::GROUP,
                '0',
                'MKSTREAM',
            ]);

            return true;
        } catch (\Throwable $e) {
            // BUSYGROUP means the group is already there, which is the desired
            // end state, not a failure.
            if (str_contains($e->getMessage(), 'BUSYGROUP')) {
                return true;
            }

            $this->logger->error('consumer group setup failed', ['exception' => $e]);

            return false;
        }
    }

    /**
     * Consume up to one batch of this consumer's own pending retries
     * (oldest work first), one batch of stale pending entries reclaimed
     * from any consumer of the group, plus one batch of never-delivered
     * entries, and report counts. Entries whose ingest path threw stay
     * pending for the next cycle unless they hit the retry cap.
     *
     * @return array{processed: int, forwarded: int} entries fully
     *                                               handled (ingested, forwarded or dead-lettered) and the
     *                                               subset rerouted to a target stream
     */
    public function consumeOnce(): array
    {
        $claimed = $this->reclaimStalePending();

        $batches = [
            // Own pending first: entries left un-acked by an earlier
            // cycle's ingest throw or a crash mid-batch must not be
            // starved by fresh traffic. Entries this cycle's reclaim
            // already claimed (and processed below) are excluded so a
            // crash orphan is handled exactly once per cycle.
            array_diff_key(
                $this->redis->xreadgroup(
                    StreamSchema::GROUP,
                    $this->consumerName,
                    [StreamSchema::STREAM => self::BATCH],
                    '0',
                )[StreamSchema::STREAM] ?? [],
                $claimed,
            ),
            // Stale entries reclaimed from ANY consumer of the group
            // (XPENDING + XAUTOCLAIM, idle > RECLAIM_IDLE_MS) — the
            // oldest, most starved work — processed before fresh traffic.
            $claimed,
            $this->redis->xreadgroup(
                StreamSchema::GROUP,
                $this->consumerName,
                [StreamSchema::STREAM => self::BATCH],
            )[StreamSchema::STREAM] ?? [],
        ];

        $processed = 0;
        $forwarded = 0;

        $routes = null;

        foreach ($batches as $batch) {
            if ($batch === []) {
                continue;
            }

            // One HGETALL of the route table per pass, not one per entry:
            // the table is not mutated inside this loop, so every entry in
            // the pass matches against the same snapshot. Read lazily and
            // only when there is work, so an idle pass pays nothing.
            $routes ??= (new RouteTable($this->redis))->all();

            foreach ($batch as $entryId => $fields) {
                if ($this->handleEntrySafely((string) $entryId, $fields, $forwarded, $routes)) {
                    $processed++;
                }
            }
        }

        return ['processed' => $processed, 'forwarded' => $forwarded];
    }

    /**
     * Reclaim entries the group owns but nobody has finished: XPENDING
     * reports the group's pending backlog, then XAUTOCLAIM transfers the
     * entries idle for at least self::RECLAIM_IDLE_MS into this consumer
     * (a valid claim — ownership moves, so the ordinary ack path clears
     * them) and returns them for processing. Connections without the
     * claim primitives (LaravelRedisAdapter today) skip the claim and
     * return []: the own-pending batch (`0`) in consumeOnce() remains the
     * fallback reclaim for same-name orphans.
     *
     * @return array<string, array<string, string>> claimed entry id => fields
     */
    public function reclaimStalePending(): array
    {
        /** @var array<mixed> $summary */
        $summary = $this->redis->xpending(StreamSchema::STREAM, StreamSchema::GROUP);

        $pending = $this->pendingTotal($summary);
        if ($pending === 0) {
            return [];
        }

        $this->logger->debug('bus group has pending entries', [
            'group' => StreamSchema::GROUP,
            'pending' => $pending,
        ]);

        /** @var array<mixed> $raw */
        $raw = $this->redis->xautoclaim(
            StreamSchema::STREAM,
            StreamSchema::GROUP,
            $this->consumerName,
            self::RECLAIM_IDLE_MS,
            '0-0',
            self::BATCH,
        );

        $claimed = self::normalizeEntries($raw['entries'] ?? $raw[1] ?? []);

        if ($claimed !== []) {
            $this->logger->info('reclaimed stale pending bus entries', [
                'count' => count($claimed),
            ]);
        }

        return $claimed;
    }

    /**
     * Re-deliver dead-lettered entries back onto the main stream
     * (operator action, synchronous): each retryable DL entry's original
     * fields (reason/error tags stripped) are re-published under the same
     * account_id/update/ts, so the next consumeOnce() routes it through
     * the live route table (StreamSchema::ROUTES_KEY) — the same route it
     * would take today — or the default ingest path. True poison (payload
     * still does not decode) is left on the DL for forensics: replaying
     * it would only re-dead-letter. Replayed entries are removed from the
     * DL stream (at-least-once: a crash between the xadd and the xdel may
     * re-deliver an entry twice; the mirror ingest is idempotent because
     * MirrorFactWriter::write() converges on the table's real primary key via
     * updateOrInsert, and each entry's writes are wrapped in one transaction
     * at the route entry — see MirrorIngesterFactory::build()).
     *
     * "Idempotent" is scoped to that and is not a claim that a re-delivery is
     * inert: a re-delivered EDITED fact re-applies and CORRECTS the row, which
     * is the point. A fact whose value is genuinely wrong still raises, because
     * the writer deliberately rethrows every violation except the re-delivery
     * collision.
     *
     * @throws LogicException when the Redis connection cannot read or
     *                        delete the DL stream (needs xrange()/xdel();
     *                        LaravelRedisAdapter passthrough pending) —
     *                        fail loudly, never silently pretend.
     */
    public function replayDeadLetter(): int
    {
        $replayed = 0;

        do {
            /** @var array<mixed> $raw */
            $raw = $this->redis->xrange(StreamSchema::DL, '-', '+', self::DL_REPLAY_BATCH);

            $entries = self::normalizeEntries($raw);
            $removed = 0;

            foreach ($entries as $dlId => $fields) {
                if (! $this->retryable($fields)) {
                    continue; // poison stays on the DL for forensics
                }

                $payload = $fields;
                unset($payload['reason'], $payload['error']);

                // Back onto the main stream: the consumer group re-routes
                // the entry through the live route table (same route as the
                // original failure, or the default ingest path).
                $this->redis->xadd(StreamSchema::STREAM, '*', $payload, ['maxlen' => RedisStreamSink::DEFAULT_MAXLEN]);

                $this->redis->xdel(StreamSchema::DL, [$dlId]);
                $replayed++;
                $removed++;
            }

            // A full window with nothing removed means the remaining head
            // of the DL is non-retryable — nothing further can be replayed
            // in this pass.
        } while (count($entries) >= self::DL_REPLAY_BATCH && $removed > 0);

        $this->logger->info('dead-letter replay finished', ['replayed' => $replayed]);

        return $replayed;
    }

    /**
     * Per-entry containment: no single entry may abort its batch.
     *
     * handleEntry() bounds the work it can see — the ingest and routed
     * branches, with strike accounting and a dead-letter on the cap — but it
     * cannot see a throw from RouteTable::matchIn(), from the xadd that
     * publishes a routed entry, or from deadLetter() itself. Any of those
     * escaped handleEntry(), escaped the foreach ($batch) in consumeOnce(),
     * and left EVERY remaining entry of this batch — and both later batches —
     * unread, to be re-delivered and re-thrown on every cycle.
     *
     * This wrapper is the second half of the fix and it must stay even though
     * (a) moved the routed branch under the try: without it, an unguarded
     * throw above is not a loud crash loop but a SILENT one, with a pending
     * list that grows without bound.
     *
     * A contained entry stays pending: it was never acked, so the next cycle
     * re-reads it and tries again under the same cap.
     *
     * @param  array<string, string>  $fields
     * @param  array<string, string>  $routes
     */
    private function handleEntrySafely(string $entryId, array $fields, int &$forwarded, array $routes): bool
    {
        try {
            return $this->handleEntry($entryId, $fields, $forwarded, $routes);
        } catch (\Throwable $e) {
            $this->logger->error('bus entry failed outside the handler guard; batch continues', [
                'entry' => $entryId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Run one entry to completion: true when it was fully handled and
     * acked (ingested, forwarded or dead-lettered); false when the
     * ingest path threw below the retry cap and the entry stays
     * pending for the next cycle.
     *
     * The routed branch sits INSIDE the same try as the default branch, so a
     * failing xadd or a throwing onRouted hook gets the same strike
     * accounting, the same attempt cap and the same dead-letter as a failing
     * ingest. It used to sit outside any try, so its throw skipped both
     * $forwarded++ and ack() and escaped the batch entirely.
     *
     * @param  array<string, string>  $fields
     * @param  array<string, string>  $routes  Route-table snapshot read once by consumeOnce().
     */
    private function handleEntry(string $entryId, array $fields, int &$forwarded, array $routes): bool
    {
        try {
            $entry = StreamSchema::decode($fields['update'] ?? '');
        } catch (JsonException|InvalidArgumentException) {
            // Poison: preserve the payload verbatim for forensics,
            // then ack so the group keeps moving.
            $this->logger->warning('bus entry does not decode; dead-lettered verbatim', [
                'entry' => $entryId,
            ]);
            $this->deadLetter($fields);
            $this->ack($entryId);

            return true;
        }

        try {
            // Matched against the pass-wide snapshot — no Redis round trip here.
            $target = RouteTable::matchIn($routes, $entry['update']);

            if ($target !== null) {
                $this->redis->xadd($target, '*', $fields, ['maxlen' => RedisStreamSink::DEFAULT_MAXLEN]);

                if ($this->onRouted !== null) {
                    // The matched entry fans into the handler pipeline too —
                    // one payload shape, one pipeline (friction I.1). The
                    // hook returns a bool (HandlerSink::handle always true);
                    // it is honoured only as a diagnostic, never as the
                    // forward signal — the entry reached the target stream.
                    $handled = ($this->onRouted)($entry['update'], (int) $entry['account_id']);

                    if ($handled === false) {
                        $this->logger->warning('bus routed handler declined the entry after it was published', [
                            'entry' => $entryId,
                            'target' => $target,
                        ]);
                    }
                }

                $forwarded++;
                $this->ack($entryId);

                return true;
            }

            if ($this->mirrorIngester !== null && self::entryVersion($fields) < self::ENTRY_V2) {
                // The verbatim's daily loop: observe → relational DB
                // (NF5 mirror) → Eloquent models. The ingester classifies
                // and gates the UpdateStored event itself (cycles 10–12).
                //
                // V1 ENTRIES ONLY — see entryVersion(). An entry the migration
                // has already versioned carries a row, and both writers
                // converge on the same tables, so running the seam over it
                // would write the same update twice.
                ($this->mirrorIngester)($entry['update'], (int) $entry['account_id']);
            } else {
                // THE FRAME IDENTITY IS SCOPED HERE, and this is the only
                // reason `handleEntry()` threads `$entryId` all the way down.
                //
                // A seq-less update (a bare `update*` ctor) has no delivering
                // seq to key on, so `UpdateIngestor` derives one from the frame
                // identity. That derivation is what makes `xautoclaim` SAFE:
                // re-delivering an in-flight entry — which happens routinely,
                // because the shipped default runs two consumers on one group
                // and reclaims whatever a slow worker's PEL has been idle for —
                // re-derives the SAME key and upserts. An earlier allocation
                // scheme read `MAX(seq) + 1`, so the second delivery of one
                // entry read MAX after the first delivery had committed, took
                // the next value, and APPENDED a duplicate row.
                //
                // Scoped rather than passed because the seam below is the BC
                // client face `Teleclient::ingest($update, $accountId)` — a
                // published two-argument API. `withinFrame()` carries the
                // identity for the duration of this one call instead of
                // breaking that face, and restores the previous value in a
                // `finally` so a throw cannot leak one entry's frame into the
                // next entry's ingest.
                $root = UpdateIngestor::withinFrame(
                    $entryId,
                    fn (): ?Model => $this->client->ingest($entry['update'], $entry['account_id']),
                );

                // onStored mirrors UpdateStored: it fires only when the
                // ingest actually stored a root (null = nothing storeable).
                if ($this->onStored !== null && $root !== null) {
                    ($this->onStored)($root, $entry['account_id']);
                }
            }
        } catch (\Throwable $e) {
            if (! $this->strike($entryId)) {
                // Retry on a later cycle; stays pending. Logged so a
                // swallowed ingest error is never invisible.
                $this->logger->warning('bus entry failed; entry stays pending for retry', [
                    'entry' => $entryId,
                    'attempts' => (int) ($this->redis->hget(StreamSchema::STRIKES_KEY, $entryId) ?? '0'),
                    'error' => $e->getMessage(),
                ]);

                return false;
            }

            // Cap reached: dead-letter with the error for forensics,
            // ack, keep the group moving. Bounded failure over an unbounded
            // redelivery loop — and the routed path shares this cap with the
            // default path on purpose.
            $this->logger->error('bus entry failed past the retry cap; dead-lettering', [
                'entry' => $entryId,
                'error' => $e->getMessage(),
            ]);
            $this->deadLetter($fields, 'ingest-failed', $e->getMessage());
            $this->clearStrike($entryId);
            $this->ack($entryId);

            return true;
        }

        $this->clearStrike($entryId);
        $this->ack($entryId);

        return true;
    }

    /**
     * The bus entry version, read from the entry's own `v` field.
     *
     * ## WHY THE SEAM IS GATED RATHER THAN DELETED
     *
     * D-040 makes `UpdateIngestor` the daemon's truth writer and retires the
     * mirror seam from the bus path. It does not delete the branch, and the
     * reason is the entries already on the stream: a producer that ran before
     * this change published v1 entries that are sitting in
     * `tg:stream:updates` right now, and the seam is the writer whose
     * behaviour those entries were produced under. Deleting the branch would
     * re-home them on a writer they were never shaped for.
     *
     * Leaving it UNCONDITIONAL is the opposite failure and the more dangerous
     * one, because it is silent. The mirror's `MirrorFactWriter` and
     * `UpdateIngestor` converge on the same tables: run both over one entry
     * and the same update is written twice, in two different shapes, with no
     * error anywhere. So the branch is keyed on the entry's own version, and
     * a v2 entry — the first version that names a committed row rather than
     * carrying a payload — is refused the seam outright.
     *
     * ABSENCE IS THE DISCRIMINATOR. A v1 entry is `{account_id, update, ts}`
     * and carries no `v` field at all, so an unmarked entry is v1 — that is
     * what makes the entries a pre-migration producer already published
     * drainable rather than dead. An unrecognisable marker is read as v1 for
     * the same reason: an entry this consumer cannot positively identify as
     * post-migration must not be handed to a v1-only writer, and the
     * recoverable direction here is the one that commits a row.
     *
     * @param  array<string, string>  $fields  the raw stream entry field map
     */
    private static function entryVersion(array $fields): int
    {
        $raw = $fields['v'] ?? null;

        if ($raw === null || ! ctype_digit($raw)) {
            return self::ENTRY_V1;
        }

        return (int) $raw;
    }

    /**
     * Count a consecutive ingest-path throw for the entry; true when the
     * retry cap is reached. Counts persist in Redis —
     * StreamSchema::STRIKES_KEY, a hash keyed by stream entry id with a
     * whole-key TTL (self::STRIKE_TTL) — so a restarted consumer keeps
     * the tally and an entry that already crossed the cap goes straight
     * to the dead-letter on its next failure.
     */
    private function strike(string $entryId): bool
    {
        $attempts = ((int) ($this->redis->hget(StreamSchema::STRIKES_KEY, $entryId) ?? '0')) + 1;

        $this->redis->hset(StreamSchema::STRIKES_KEY, $entryId, (string) $attempts);
        $this->redis->expire(StreamSchema::STRIKES_KEY, self::STRIKE_TTL);

        return $attempts >= self::INGEST_ATTEMPTS;
    }

    /**
     * Forget a persisted strike tally — the entry was handled.
     *
     * This used to `hset(..., '0')` on EVERY success, which is what made
     * StreamSchema::STRIKES_KEY grow without bound: the key only ever got a
     * TTL on the FAILURE path (strike()), so a consumer that never threw
     * created one permanent hash field per update, forever — and
     * OpsReadModel HGETALLs the whole hash on every ops page load.
     *
     * A success must not create a field. Delete the field if one is there;
     * do not write a '0' placeholder.
     */
    private function clearStrike(string $entryId): void
    {
        $this->redis->hdel(StreamSchema::STRIKES_KEY, $entryId);
    }

    /**
     * A DL entry is retryable when its payload decodes to a canonical bus
     * entry. True poison (undecodable update) cannot ever be processed, so
     * replay leaves it on the dead-letter stream for forensics.
     *
     * @param  array<string, string>  $fields
     */
    private function retryable(array $fields): bool
    {
        try {
            StreamSchema::decode($fields['update'] ?? '');

            return true;
        } catch (JsonException|InvalidArgumentException) {
            return false;
        }
    }

    /**
     * Post an entry to the dead-letter stream: the original fields
     * verbatim, optionally tagged with a reason and error message.
     *
     * @param  array<string, string>  $fields
     */
    private function deadLetter(array $fields, ?string $reason = null, string $error = ''): void
    {
        $tag = $reason === null ? [] : array_filter([
            'reason' => $reason,
            'error' => $error,
        ], static fn (string $value): bool => $value !== '');

        $this->redis->xadd(StreamSchema::DL, '*', $fields + $tag, ['maxlen' => self::DL_MAXLEN]);
    }

    private function ack(string $entryId): void
    {
        $this->redis->xack(StreamSchema::STREAM, StreamSchema::GROUP, [$entryId]);
    }

    /**
     * Total pending count from an XPENDING summary, tolerant of BOTH real
     * driver shapes.
     *
     * The summary reply of `XPENDING <stream> <group>` is:
     *
     *     [count, minId, maxId, [[consumer, count], ...]]
     *
     * - predis (RESP2) answers exactly that, so the count is slot 0.
     * - phpredis answers a keyed array whose leading 'message' slot holds the
     *   idle-consumer note. LaravelRedisAdapter::xpending() unsets that key
     *   and re-indexes with array_values(), but the note is still there as a
     *   leading null when there is nothing to report, which shifts the count
     *   to slot 1: [null, count, minId, maxId, [[consumer, count], ...]].
     *
     * The extended form (a range was asked for) replies with a bare list of
     * [id, consumer, idleMs, deliveryCount] rows and has no count slot; its
     * count is the number of rows.
     *
     * This used to read `array_key_last($summary)`, which on a real reply is
     * the CONSUMERS MAP — so the count read 0 on both drivers,
     * reclaimStalePending() bailed before issuing XAUTOCLAIM, and crash
     * orphans stayed PENDING forever. It only ever "worked" because the
     * in-memory test double fabricated an ['name' => 'total', 'pending' => N]
     * row that no real driver produces.
     *
     * @param  array<mixed>  $summary
     */
    private function pendingTotal(array $summary): int
    {
        // predis RESP2: the count leads.
        $leading = self::countSlot($summary[0] ?? null);

        if ($leading !== null) {
            return $leading;
        }

        // phpredis through the adapter: a leading message slot (null when
        // there is nothing to report) pushes the count one position later.
        $next = self::countSlot($summary[1] ?? null);

        if ($next !== null) {
            return $next;
        }

        // The extended form has no count at all — count the rows. Only rows
        // (arrays) qualify, so a malformed summary still reads 0.
        if ($summary !== [] && is_array($summary[0])) {
            foreach ($summary as $row) {
                if (! is_array($row)) {
                    return 0;
                }
            }

            return count($summary);
        }

        return 0;
    }

    /**
     * Read one summary slot as a pending count, or null when the slot holds
     * anything else (a null message note, a min/max id like '1-0', the
     * consumers map, an entry row). `is_numeric` is safe here precisely
     * BECAUSE a stream id is 'ms-seq' and therefore never numeric.
     */
    private static function countSlot(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && $value !== '' && is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }

    /**
     * Normalize a stream-read reply (xautoclaim claims, xrange window) to
     * [id => fields]. phpredis returns the associative [id => [field =>
     * value]] form; predis (RESP2) returns [id, [f, v, ...]] pairs with
     * flat field lists. Tolerant to both shapes.
     *
     * @return array<string, array<string, string>>
     */
    private static function normalizeEntries(mixed $entries): array
    {
        if (! is_array($entries)) {
            return [];
        }

        $normalized = [];

        foreach ($entries as $key => $value) {
            if (is_string($key)) {
                $normalized[$key] = self::asFieldMap($value);
            } elseif (
                is_array($value)
                && isset($value[0], $value[1])
                && is_array($value[1])
            ) {
                $normalized[(string) $value[0]] = self::asFieldMap($value[1]);
            }
        }

        return $normalized;
    }

    /**
     * @return array<string, string>
     */
    private static function asFieldMap(mixed $fields): array
    {
        $map = [];

        if (is_array($fields)) {
            foreach ($fields as $key => $value) {
                $map[(string) $key] = (string) $value;
            }
        }

        return $map;
    }
}
