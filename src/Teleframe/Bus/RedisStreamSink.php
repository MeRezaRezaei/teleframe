<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Bus;

use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Laravel\Services\UpdatePollerService;
use MeRezaRezaei\Teleframe\Mirror\Models\TfUpdate;

/**
 * Teleframe sink that mirrors every update into the shared Redis
 * stream (StreamSchema::STREAM) so any number of accounts can feed one
 * consumer group.
 *
 * ## THE ROW IS THE SYSTEM OF RECORD, AND THIS SINK PROVES IT BY ORDER
 *
 * The stream entry names the row: `{v, account_id, seq, position,
 * constructor, ts}`. It does NOT inline the update (D-039) — that inversion
 * is what this class exists to make true, and it is only true if the row is
 * written FIRST:
 *
 *     handleCommitted()  ingest → COMMIT → xadd
 *
 * A crash between the commit and the append costs the PUBLISH, never the
 * update: the row is durable, the next producer cycle re-derives it from the
 * same cursor, and nothing about the update is lost. The pre-inversion order
 * was the opposite — `xadd` first, no database touched on this path at all —
 * so a Redis outage at that moment lost the update outright, with no trace.
 *
 * The ordering is not asserted by comment. It is enforced by the ORDER of the
 * two statements below, and `ProducerPublishesOnlyAfterRowCommitsTest` proves
 * it from a second, independent database connection at the instant of the
 * append.
 *
 * ## WHAT IS PUBLISHED WHEN NOTHING WAS STORED
 *
 * Some constructors store no row at all — `RPC_RESULT_CTORS`, and ctors with
 * no curated surface. Routing reads `constructor`, so those updates are still
 * published, with `seq`/`position` as the `-1/-1` SENTINELS (D-042). An update
 * that cannot be stored is a routable update, not a lost one.
 *
 * ## A FAILED INGEST THROWS AND PUBLISHES NOTHING
 *
 * The other half of the invariant. A database write that fails is not
 * downgraded to a sentinel entry: the exception propagates, `xadd()` is never
 * reached, and the caller learns the update was not stored. That is the
 * inverse assertion `ProducerPublishesOnlyAfterRowCommitsTest` records as owed
 * — a successful ingest publishes exactly once, a failed one publishes not at
 * all.
 *
 * Sink semantics: always true — an appended entry is consumed as far as
 * this sink is concerned. Returning false ("not now") is reserved for a
 * future backpressure path; per UpdateSinkInterface, soft refusals must
 * not throw.
 */
final class RedisStreamSink implements CommittedRowSink
{
    /**
     * Approximate upper bound on the update stream.
     *
     * An XACK only clears an entry from the group's pending list; it never
     * shortens the stream. With no MAXLEN the stream therefore holds every
     * update the tenant has ever received, forever, and a first real
     * deployment OOMs Redis within days. `~` keeps the trim O(1) and may
     * overshoot the bound by a little, which is the correct trade here.
     */
    public const DEFAULT_MAXLEN = 50_000;

    /**
     * The poller whose minted producer frame is in scope, or null.
     *
     * A FRAME READ, not an ownership link: the poller owns the frame and this
     * sink only reads `getProducerFrame()` for the duration of one
     * `handleCommitted()` call (D-041). It is injected rather than reached for
     * because the composition root that holds both is `AccountWorker::run()`,
     * which builds the poller around a sink it was handed.
     */
    private ?UpdatePollerService $poller = null;

    public function __construct(
        private readonly RedisConnectionContract $redis,
        private readonly int $accountId,
        private readonly int $maxlen = self::DEFAULT_MAXLEN,
    ) {}

    /**
     * Adopt the poller this sink reads its producer frame from.
     *
     * Fluent, like every other setter in the package. Optional: with no
     * poller the frame is null and the row's key is derived from the payload's
     * own content instead (`UpdateIngestor::contentIdentity()`), which is
     * still stable across a re-ingest of the same bytes.
     */
    public function withProducerFrameSource(UpdatePollerService $poller): static
    {
        $this->poller = $poller;

        return $this;
    }

    /**
     * Publish one update, committing its row first.
     *
     * The CommittedRowSink path with no committer of its own: a direct caller
     * (a host writing to the bus by hand, a test) publishes an identity-only
     * entry. The PRODUCTION path is `UpdatePollerService::processUpdate()`,
     * which calls handleCommitted() with the resolved writer and the minted
     * frame.
     *
     * @param  array<string, mixed>  $update
     */
    public function handle(array $update, ?string $source = null, ?int $ts = null): bool
    {
        return $this->handleCommitted($update, $source, null, $this->poller?->getProducerFrame());
    }

    /**
     * @param  array<string, mixed>  $update
     */
    public function handleCommitted(
        array $update,
        ?string $source,
        ?UpdateIngestor $committer,
        ?string $frame,
    ): bool {
        $constructor = (string) ($update['_'] ?? '');

        $key = $constructor === ''
            ? null
            : $this->commit($update, $committer, $frame);

        $ts = time();

        $this->redis->xadd(StreamSchema::STREAM, '*', StreamSchema::fields([
            'v' => StreamSchema::VERSION,
            'account_id' => $this->accountId,
            'seq' => $key['seq'] ?? StreamSchema::NO_ROW_SEQ,
            'position' => $key['position'] ?? StreamSchema::NO_ROW_POSITION,
            'constructor' => $constructor,
            'ts' => $ts,
        ]), $this->maxlen > 0 ? ['maxlen' => $this->maxlen] : []);

        return true;
    }

    /**
     * Commit the row and return the committed `(seq, position)`, or null when
     * there is no committer or nothing was stored.
     *
     * ## WHY AN UPDATE WITH NO `_` CONSTRUCTOR IS NOT SENT TO THE WRITER
     *
     * `UpdateIngestor::ingest()` throws on a payload with no constructor node,
     * because it cannot know what to store. Throwing here would abort the
     * publish of an entry the consumer must still SEE — the dead-letter path
     * is built on exactly this payload arriving and failing to be ingestible.
     * So the decision is taken before the call, and the update is published as
     * a constructor-less identity entry, which is what it is.
     *
     * Every OTHER throw from `ingest()` — a foreign key, a value that will not
     * store, a dead connection — propagates. Those are real failures and the
     * correct response is to publish nothing at all.
     *
     * @param  array<string, mixed>  $update
     * @return array{seq: int, position: int}|null
     */
    private function commit(array $update, ?UpdateIngestor $committer, ?string $frame): ?array
    {
        if ($committer === null) {
            return null;
        }

        $root = $committer->ingest($update, $this->accountId, $frame);

        // Nothing storeable: an RPC result, or a constructor with no curated
        // surface. Published with the sentinels instead (D-042).
        if (! $root instanceof TfUpdate) {
            return null;
        }

        $seq = $root->getAttribute('seq');
        $position = $root->getAttribute('position');

        if (! is_numeric($seq) || ! is_numeric($position)) {
            return null;
        }

        return ['seq' => (int) $seq, 'position' => (int) $position];
    }

    /**
     * Derive the tenancy account id from a teleframe source string.
     *
     * Numeric sources are user-account ids; anything else is treated as a
     * bot token ("id:hash") — the id part before the colon wins when it is
     * numeric. Unknown shapes degrade to 0 (unattributed), never throw.
     */
    public static function accountIdFor(?string $source): int
    {
        if ($source === null || $source === '') {
            return 0;
        }

        if (ctype_digit($source)) {
            return (int) $source;
        }

        $id = str_contains($source, ':') ? substr($source, 0, (int) strpos($source, ':')) : '';

        return $id !== '' && ctype_digit($id) ? (int) $id : 0;
    }
}
