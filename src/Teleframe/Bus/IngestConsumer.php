<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Bus;

use Closure;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use JsonException;
use LogicException;
use MeRezaRezaei\Teleframe\Mirror\Models\TfUpdate;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * The bus consumer (plan Task 2): reads one batch for the shared
 * consumer group from the updates stream and clears it.
 *
 * ## THE ENTRY NAMES A COMMITTED ROW, SO THE CONSUMER READS A ROW
 *
 * A v2 stream entry is `{v, account_id, seq, position, constructor, ts}` as
 * flat fields — it carries NO payload (D-039). The producer committed the row
 * BEFORE publishing it, so:
 *
 *  - a pre-v2 (or otherwise unreadable) entry is REFUSED and dead-lettered
 *    without a payload, never mis-parsed into an empty update — a
 *    half-migrated fleet fails loudly instead of dropping updates;
 *  - a RouteTable match (matched on the published `constructor`) → forwarded
 *    RE-SPECIFIED to the target stream, and fanned into the handler pipeline
 *    as an identity row; and
 *  - no route (the default path) → the named `(account_id, seq, position)`
 *    is HYDRATED from `tf_updates` and handed to the onStored hook. The two
 *    ingest sites that used to live here are gone: one wrote a second,
 *    differently-shaped copy of an update the producer had already stored, and
 *    the other was the fallback for a seam the bus path no longer carries.
 *
 * A `-1/-1` sentinel entry (D-042: a constructor that stored no row) names no
 * row, which is a real inconsistency and RAISES — so it walks the ordinary
 * strike/dead-letter path instead of being acknowledged as though it had
 * been found.
 *
 * Ingest-path failures get a bounded retry: an entry whose path throws
 * (an unresolvable key, a throwing onStored hook, ...) stays un-acked and is
 * retried on the next consumeOnce() cycle; after self::INGEST_ATTEMPTS
 * consecutive throws it is dead-lettered with reason `ingest-failed <key>`
 * and the error message, then acked — a persistently failing entry
 * can no longer wedge the group, while transient faults still get
 * their retries. The strike counter persists in Redis instead of
 * process memory: StreamSchema::STRIKES_KEY is a hash keyed by stream
 * entry id with a whole-key TTL (self::STRIKE_TTL), so a restarted
 * consumer keeps the tally and an entry that already crossed the cap
 * goes straight to the dead-letter on its next failure — no infinite
 * retry loop across restarts. The field is deleted on the
 * entry's first success.
 *
 * THE DEAD LETTER RETAINS NO PAYLOAD (D-043). The row is already committed,
 * so keeping its bytes on Redis would put the system of record back on the
 * bus. What a DL row carries instead is the composite key in `reason`, so an
 * operator can trace it back to its `tf_updates` row by query. The reduction
 * in forensic power is real and deliberate: a bad update's bytes are no
 * longer reconstructible from the dead-letter.
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
 *    of dead-lettered entries back onto the main stream (re-notification
 *    of a committed row, re-routed through the live route table).
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
     * Approximate bound on the dead-letter stream. A dead-letter is an
     * operator artifact, not a queue: it is replayed or inspected by hand.
     * Unbounded, it is a slow leak that is invisible until Redis is under
     * memory pressure — and the entries nobody looked at are exactly the
     * ones a postmortem needs.
     */
    private const DL_MAXLEN = 10_000;

    /** @var ?Closure(Model, int): void fired only for a stored root — same gate as UpdateStored. */
    private readonly ?Closure $onStored;

    /** @var ?Closure(array<string, mixed>, int, ?int): void
     *  Fires for a ROUTED entry (one that matched the bus route table) and
     *  not for the default ingest path. The 2026-09-26 shipped daemon used
     *  to pass null here, which silently dropped every routed update before
     *  the handler pipeline could see it: a declarative app could never
     *  answer a routed update even though the route table said the host
     *  wanted it. Host apps point this at a HandlerSink. */
    private readonly ?Closure $onRouted;

    /** @var ?Closure(array<string, mixed>, int, ?int): void */
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

    /**
     * @param  RedisConnectionContract  $redis  the updates stream's connection
     *
     * The five hooks below bind POSITIONALLY at every call site, so the dead
     * `Teleclient $client` parameter that used to sit in slot two was dropped
     * together with every call site, not before them. It had no remaining
     * read: the bus-inversion refactor deleted the last one — the
     * `UpdateIngestor::withinFrame(fn => $client->ingest(...))` second write of
     * an update the producer had already committed, which the deletion of the
     * payload made impossible to restore. Shifting the hooks without touching
     * the call sites would have wired `onStored` onto the slot `onRouted` was
     * given, and so on, so the two had to move as one change.
     */
    public function __construct(
        private readonly RedisConnectionContract $redis,
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
                    continue; // nothing replayable; stays for forensics
                }

                // Re-published RE-SPECIFIED, never verbatim (step 6). Under v1
                // this copied whatever shape arrived, which is how the dead
                // letter and routed-target streams became mixed-version by
                // construction; a v1 codec reading a v2 entry mis-parses it,
                // and a v2 codec reading a v1 entry refuses it.
                //
                // identityOf() also MIGRATES a pre-migration row — see there —
                // and drops the meta tags either way: they described the
                // failure that put the entry HERE, not the entry.
                $this->redis->xadd(
                    StreamSchema::STREAM,
                    '*',
                    StreamSchema::fields($this->identityOf($fields)),
                    ['maxlen' => RedisStreamSink::DEFAULT_MAXLEN],
                );

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
            // THE ENTRY IS THE FIELD MAP. A v2 stream entry carries
            // {v, account_id, seq, position, constructor, ts} as flat fields
            // (D-039) — there is no inner `update` blob to unpack, because the
            // payload is not on the bus and the row is the system of record.
            $entry = StreamSchema::fromFields($fields);
        } catch (InvalidArgumentException) {
            // NOT a v2 entry. Step 3, and the whole reason the version
            // discriminator exists: a rolling upgrade hands a pre-migration
            // payload-carrying entry to a consumer that cannot read one, and
            // the choices are mis-parse it (drop updates invisibly) or refuse
            // it (loudly, recoverably).
            //
            // ONE last chance before the refusal, and only for a host that has
            // explicitly bound the mirror seam: an entry a producer published
            // BEFORE the migration is drained through the writer it was
            // produced under, because draining is the recoverable direction
            // and refusing is the lossy one (D-045.2). The SHIPPED daemon binds
            // nothing here, so the stock path refuses — and the refusal is
            // itself an entry, re-specified and payload-free, so the
            // dead-letter stream is never mixed-version either.
            if ($this->drainLegacy($fields)) {
                $this->ack($entryId);

                return true;
            }

            $this->logger->warning('bus entry is not a v'.StreamSchema::VERSION.' entry; dead-lettered without a payload', [
                'entry' => $entryId,
                'version' => StreamSchema::versionOf($fields),
            ]);

            $this->deadLetter($fields, 'refused-v'.StreamSchema::versionOf($fields));
            $this->ack($entryId);

            return true;
        }

        try {
            // Matched against the pass-wide snapshot — no Redis round trip
            // here, and against the CONSTRUCTOR the entry publishes, which is
            // the one thing matching has always read.
            $target = RouteTable::matchConstructorIn($routes, $entry['constructor']);

            if ($target !== null) {
                // Re-specified, not forwarded verbatim: the target stream is a
                // bus stream, and a writer that copied whatever shape it was
                // handed is what made the dead-letter and routed streams
                // mixed-version by construction (step 6).
                $this->redis->xadd(
                    $target,
                    '*',
                    StreamSchema::fields($entry),
                    ['maxlen' => RedisStreamSink::DEFAULT_MAXLEN],
                );

                if ($this->onRouted !== null) {
                    // The matched entry fans into the handler pipeline too —
                    // one shape, one pipeline (friction I.1). The hook returns
                    // a bool (HandlerSink::handle always true); it is honoured
                    // only as a diagnostic, never as the forward signal — the
                    // entry reached the target stream.
                    //
                    // THE HOOK RECEIVES THE IDENTITY, NOT THE PAYLOAD. This
                    // is the same minimal identity row the event path has
                    // always handed the pipeline (`Update::fromMirror()`:
                    // `['_'] => ctor` and nothing else), so the two intake
                    // rows stay convergent — and a handler that needs payload
                    // fields reads the committed row, which is what S2's
                    // horizontal relations exist to make cheap.
                    // NEW-1 (2026-10-17): "minimal" was minimal TOO FAR. With
                    // only `_` => ctor and no ts, `Update::updateId()` is
                    // sha1(accountId . "\0" . '' . "\0" . '{"_":"updateNew..."}')
                    // — ONE CONSTANT PER CONSTRUCTOR. Measured before this fix:
                    // 500 distinct bus entries produced exactly 1 distinct
                    // updateId(). Both downstream guards (ReplayDedup's seen-set,
                    // forwardKey()'s 'u:'.updateId() branch) then agree on the
                    // same wrong answer and silently discard every message after
                    // the first: a bus-path handler ran ONCE per process
                    // lifetime. It stayed hidden behind a green suite because
                    // ReplayDedupTest and FakeDispatcher feed a FULL payload plus
                    // a ts — a shape production never emits.
                    //
                    // So the identity row must be DISTINCT PER ENTRY, not merely
                    // small. All four fields are already on the entry — none of
                    // this is new wire data:
                    //
                    //   entry        the Redis stream entry id (e.g. "1758...-0").
                    //                Unique per ENTRY and STABLE ACROSS
                    //                REDELIVERY — XREADGROUP hands back the same
                    //                id for an unacked entry, which is exactly the
                    //                property a dedup key needs: a retry is the
                    //                same entry, a second publish is a different
                    //                one.
                    //   seq/position the committed row key (D-042). -1/-1
                    //                sentinels when nothing was stored, in which
                    //                case `entry` alone still discriminates.
                    //   ts           was DROPPED entirely here — HandlerSink never
                    //                passed it, so it was always null on this path.
                    //
                    // The committed row key stays the semantic identity (it is
                    // what "the same update" means); the stream entry id is the
                    // transport-level discriminator that stops the frameless
                    // -1/-1 case from collapsing. seq/position ride as dedicated
                    // fields rather than being folded into the hash, so a
                    // consumer can still read them.
                    $row = [
                        '_' => $entry['constructor'],
                        'entry' => $entryId,
                        'seq' => $entry['seq'],
                        'position' => $entry['position'],
                    ];

                    $handled = ($this->onRouted)($row, $entry['account_id'], $entry['ts']);

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

            // THE DEFAULT PATH IS HYDRATION (step 4). The row was committed by
            // the producer BEFORE this entry was published — that ordering is
            // the whole invariant (D-039/D-040) — so the consumer's job is to
            // resolve the key it names and hand the stored row on. Both ingest
            // sites that used to live here are gone: one wrote a second,
            // differently-shaped copy of an update the producer had already
            // stored, the other was the fallback for a seam that no longer
            // carries the bus path at all.
            $root = $this->hydrate($entry);

            // onStored mirrors UpdateStored: it fires only when the entry
            // names a stored row. A key that resolves to nothing raises
            // inside hydrate() rather than handing on a null root, so there
            // is no "row is gone" branch to silently pass here.
            if ($this->onStored !== null) {
                ($this->onStored)($root, $entry['account_id']);
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
            $this->deadLetter($fields, 'ingest-failed '.$this->keyLabelOf($fields), $e->getMessage());
            $this->clearStrike($entryId);
            $this->ack($entryId);

            return true;
        }

        $this->clearStrike($entryId);
        $this->ack($entryId);

        return true;
    }

    /**
     * Resolve the committed row an entry names, or raise.
     *
     * The composite key is `(account_id, seq, position)` — the real primary
     * key of `tf_updates`, which has no surrogate id (D-039). A key that
     * names no row is a genuine inconsistency and RAISES, so it walks the
     * ordinary strike/dead-letter path: a `-1/-1` sentinel entry (a
     * constructor that stored nothing, D-042) reaches the cap and is
     * dead-lettered with its sentinel named, instead of being acknowledged as
     * though a row had been found.
     *
     * Never null: the miss above is raised, not returned, so the caller has
     * no null branch to forget.
     *
     * @param  array{v: int, account_id: int, seq: int, position: int, constructor: string, ts: int}  $entry
     */
    private function hydrate(array $entry): Model
    {
        $stored = TfUpdate::forAccount($entry['account_id'])
            ->where('seq', $entry['seq'])
            ->where('position', $entry['position'])
            ->first();

        if ($stored === null) {
            throw new RuntimeException(sprintf(
                'bus entry names no committed tf_updates row (%s) for constructor "%s"',
                StreamSchema::keyLabel($entry),
                $entry['constructor'],
            ));
        }

        return $stored;
    }

    /**
     * The composite key of a raw field map, for the dead-letter `reason`.
     *
     * Falls back to the raw fields when the map is not a v2 entry at all —
     * a refused v1 entry still has to be traceable, and its numbers are read
     * as best they can be rather than invented.
     *
     * @param  array<string, string>  $fields
     */
    private function keyLabelOf(array $fields): string
    {
        return StreamSchema::keyLabel($fields);
    }

    /**
     * Drain ONE pre-migration entry through the mirror seam, if a host bound
     * one. False whenever there is no seam to drain with.
     *
     * Scoped deliberately narrow, because it is the last thing standing
     * between a half-migrated fleet and a silent loss:
     *
     *  - it runs ONLY for an entry the v2 codec refused, never for one the
     *    codec read, so it can never double-write a v2 entry's row; and
     *  - it runs ONLY when a host bound the seam, which the shipped daemon
     *    does not — so on the stock path this is dead code that costs one
     *    null check, and the entry is refused loudly instead.
     *
     * A throw from the seam is swallowed HERE and reported as "not drained",
     * so the caller takes the refusal path. It is never allowed to abort the
     * batch.
     *
     * @param  array<string, string>  $fields
     */
    private function drainLegacy(array $fields): bool
    {
        if ($this->mirrorIngester === null) {
            return false;
        }

        try {
            $legacy = StreamSchema::decodeLegacy($fields['update'] ?? '');

            ($this->mirrorIngester)($legacy['update'], (int) $legacy['account_id']);

            return true;
        } catch (\Throwable) {
            // Every failure lands here, decode failures included: there is no
            // payload to hand the seam, so there is nothing to drain and the
            // entry is refused. Listing JsonException/InvalidArgumentException
            // alongside \Throwable named two branches the union already
            // covers, so they could never be reached on their own.
            $this->logger->warning('a pre-migration bus entry could not be drained through the mirror seam', [
                'version' => StreamSchema::versionOf($fields),
            ]);

            return false;
        }
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
     * A DL entry is replayable when there is SOMETHING to replay: a v2 entry
     * this build reads, or a legacy pre-migration entry that still carries the
     * payload the old consumer preserved.
     *
     * Under D-043 a v2 DL entry retains NO payload, so `retryable()` is a
     * SHAPE check and nothing more: it used to decode a payload to decide
     * whether the original update could be re-ingested, and the bytes it
     * tested are deliberately no longer kept. What replay can now re-do is the
     * notification — re-route the entry and re-fan it into the handler
     * pipeline — and the row it names is already committed, which is the
     * entire point of the inversion.
     *
     * An entry with NEITHER a readable identity NOR a decodable legacy
     * payload stays on the DL: there is nothing to notify anyone with.
     *
     * @param  array<string, string>  $fields
     */
    private function retryable(array $fields): bool
    {
        if (StreamSchema::versionOf($fields) === StreamSchema::VERSION) {
            try {
                StreamSchema::fromFields($fields);

                return true;
            } catch (InvalidArgumentException) {
                return false;
            }
        }

        // A pre-migration DL row: the old consumer preserved the payload for
        // forensics, so it carries one and it can still be read.
        try {
            StreamSchema::decodeLegacy($fields['update'] ?? '');

            return true;
        } catch (JsonException|InvalidArgumentException) {
            return false;
        }
    }

    /**
     * Post an entry to the dead-letter stream, RE-SPECIFIED as a v2 entry and
     * carrying NO payload (D-043).
     *
     * This used to copy the original fields verbatim, which under v1 preserved
     * the update bytes for forensics. Under v2 there is nothing to preserve:
     * the row is committed, so keeping its bytes on Redis would put the
     * system of record back on the bus — the exact inversion this slice
     * removes.
     *
     * THE COST, STATED PLAINLY: an operator investigating a bad update can no
     * longer reconstruct its bytes from the dead-letter. What they get instead
     * is `reason` naming the composite key, so the DL row is traceable to its
     * `tf_updates` row by query.
     *
     * A refused pre-v2 entry carries no identity either, so it is published
     * as the bare v2 shell naming what it could read — never as a payload.
     *
     * @param  array<string, string>  $fields
     */
    private function deadLetter(array $fields, ?string $reason = null, string $error = ''): void
    {
        $tag = $reason === null ? [] : array_filter([
            'reason' => $reason,
            'error' => $error,
        ], static fn (string $value): bool => $value !== '');

        $this->redis->xadd(
            StreamSchema::DL,
            '*',
            StreamSchema::fields($this->identityOf($fields)) + $tag,
            ['maxlen' => self::DL_MAXLEN],
        );
    }

    /**
     * The identity half of a raw field map, as a canonical v2 entry.
     *
     * THE ONE PLACE a malformed or legacy entry becomes a well-formed one, so
     * no writer on the bus can emit a shape the codec refuses.
     *
     * A v2 entry passes straight through — minus any extra keys, which is how
     * a dead-letter's `reason`/`error` tags are dropped on the way back.
     *
     * A PRE-MIGRATION entry is MIGRATED, not discarded: the old consumer
     * preserved the payload on the dead-letter stream, and that row is a real
     * operator artifact. Its `account_id`, `ts` and constructor are read out of
     * the payload it still carries, and published as a v2 identity carrying the
     * `-1/-1` sentinels. The constructor is extracted rather than dropped
     * because routing reads it: migrating a legacy dead-letter row while
     * discarding its constructor would turn a routable update into an
     * unroutable one — the silent loss D-042 exists to prevent, arriving
     * through the migration instead of through the publish.
     *
     * The payload itself is NOT carried across, under any path.
     *
     * @param  array<string, string>  $fields
     * @return array<string, mixed>
     */
    private function identityOf(array $fields): array
    {
        try {
            return StreamSchema::fromFields($fields);
        } catch (InvalidArgumentException) {
            // Not a v2 entry.
        }

        $accountId = (int) ($fields['account_id'] ?? 0);
        $ts = (int) ($fields['ts'] ?? 0);
        $constructor = '';

        try {
            $legacy = StreamSchema::decodeLegacy($fields['update'] ?? '');
            $accountId = $legacy['account_id'];
            $ts = $legacy['ts'];
            $constructor = (string) ($legacy['update']['_'] ?? '');
        } catch (JsonException|InvalidArgumentException) {
            // No readable legacy payload: the sentinels and an empty
            // constructor are the honest answer, and a `*` catch-all route
            // still matches it.
        }

        return [
            'v' => StreamSchema::VERSION,
            'account_id' => $accountId,
            'seq' => StreamSchema::NO_ROW_SEQ,
            'position' => StreamSchema::NO_ROW_POSITION,
            'constructor' => $constructor,
            'ts' => $ts,
        ];
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
