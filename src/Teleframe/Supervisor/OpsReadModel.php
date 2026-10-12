<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Supervisor;

use Closure;
use MeRezaRezaei\Teleframe\Bus\RedisConnectionContract;
use MeRezaRezaei\Teleframe\Bus\StreamSchema;
use Throwable;

/**
 * The operations read-model: a read-only projection over state the package
 * ALREADY records, surfaced as JSON (dashboard + `teleframe:supervise
 * --status`) instead of being left in a Redis hash with no reader — which is
 * exactly where Horizon's "failed jobs" ledger sat before this class.
 *
 * Sources, all pre-existing:
 *   - `StreamSchema::STRIKES_KEY` — the ingest retry ledger (IngestConsumer
 *     writes a per-entry strike tally; only the consumer ever read it back);
 *   - `StreamSchema::DL` — the dead-letter stream;
 *   - `StreamSchema::STREAM` — the undrained backlog;
 *   - a supervisor state provider (the live Supervisor's report()).
 *
 * No new storage is invented. Reads are TOLERANT by design: a host with no
 * Redis seam, or a connection without the xrange primitive, gets the sources
 * that DO answer plus an explicit note in `sources` — never a fatal and never
 * a silently-empty page pretending there is nothing wrong.
 *
 * Constructible with no container and no Redis: pass null and every read is
 * skipped (`bus: false`).
 */
final class OpsReadModel
{
    /** Container seam: RedisConnectionContract for the bus state. */
    public const REDIS_KEY = 'teleclient.supervisor.redis';

    /** Container seam: callable(): list<array<string, mixed>> — the live Supervisor's report rows. */
    public const SUPERVISOR_STATE_KEY = 'teleclient.supervisor.state';

    /** Entries with at least this many strikes count as "failing" in the summary. */
    private const FAILING_AT = 1;

    /**
     * @param  Closure(): list<array<string, mixed>>  $supervised  returns Supervisor::report() rows (injected, zero-regex).
     * @param  int  $limit  max rows per list in the snapshot.
     */
    public function __construct(
        private readonly ?RedisConnectionContract $redis = null,
        private readonly ?Closure $supervised = null,
        private readonly int $limit = 50,
    ) {}

    public function snapshot(): OpsSnapshot
    {
        if ($this->redis === null) {
            return new OpsSnapshot(
                bus: false,
                supervised: $this->supervisedRows(),
                sources: ['supervisor' => 'ok'],
            );
        }

        $sources = ['supervisor' => 'ok'];

        $failingTotal = null;

        try {
            // failingIngest() SLICES to the panel limit; the operator also needs
            // the number the slice came from, or a 500-entry incident reads as
            // 50 with nothing on the page to contradict it. The strike hash is
            // already read here, so the true count costs one count(), not a
            // second round trip.
            $failingTotal = $this->failingTotal();
            $strikes = $this->failingIngest();
            $sources['strikes'] = 'ok';
        } catch (Throwable) {
            $strikes = [];
            $sources['strikes'] = 'unavailable';
        }

        try {
            $deadLetters = $this->deadLetters();
            $sources['dead_letter'] = 'ok';
        } catch (Throwable) {
            $deadLetters = [];
            $sources['dead_letter'] = 'unavailable';
        }

        try {
            $backlog = [
                StreamSchema::STREAM => $this->redis->llen(StreamSchema::STREAM),
                StreamSchema::DL => $this->redis->llen(StreamSchema::DL),
            ];
            $sources['backlog'] = 'ok';
        } catch (Throwable) {
            $backlog = [];
            $sources['backlog'] = 'unavailable';
        }

        if ($failingTotal !== null) {
            $backlog[StreamSchema::STRIKES_KEY] = $failingTotal;
        }

        return new OpsSnapshot(
            bus: true,
            failingIngest: $strikes,
            deadLetters: $deadLetters,
            backlog: $backlog,
            supervised: $this->supervisedRows(),
            sources: $sources,
        );
    }

    /** The snapshot as JSON, for the ops endpoint and `--status`. */
    public function toJson(int $flags = 0): string
    {
        return json_encode($this->snapshot()->toArray(), $flags | JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }

    /**
     * The strike ledger, worst first. `attempts` is the consumer's own tally:
     * it resets to 0 on the entry's first success, so only entries that are
     * still failing carry a positive count.
     *
     * @return list<array{entry_id: string, attempts: int, failing: bool}>
     */
    /**
     * How many entries are still failing, exactly.
     *
     * The panel shows a SAMPLE, bounded by the limit so a page load cannot be
     * made to serialise an unbounded hash; this is the number behind that
     * sample, so "failing entries" on the panel means what it says.
     */
    private function failingTotal(): int
    {
        $hash = $this->redis?->hgetall(StreamSchema::STRIKES_KEY) ?? [];

        $failing = 0;
        foreach ($hash as $attempts) {
            if ((int) $attempts >= self::FAILING_AT) {
                $failing++;
            }
        }

        return $failing;
    }

    private function failingIngest(): array
    {
        $hash = $this->redis?->hgetall(StreamSchema::STRIKES_KEY) ?? [];

        $rows = [];
        foreach ($hash as $entryId => $attempts) {
            $count = (int) $attempts;
            if ($count < self::FAILING_AT) {
                continue; // reset by a later success — not a failure.
            }

            $rows[] = [
                'entry_id' => (string) $entryId,
                'attempts' => $count,
                'failing' => true,
            ];
        }

        // Worst first, then newest id — a total order without a sort callback
        // comparison on mixed types.
        usort($rows, static function (array $a, array $b): int {
            return [$b['attempts'], $b['entry_id']] <=> [$a['attempts'], $a['entry_id']];
        });

        return array_slice($rows, 0, $this->limit);
    }

    /**
     * The dead-letter stream's most recent entries, NEWEST FIRST via
     * XREVRANGE. An adapter without it degrades to the count-only answer
     * rather than failing the whole page.
     *
     * @return list<array{id: string, reason: string, error: string, account_id: int, ts: int}>
     */
    private function deadLetters(): array
    {
        $redis = $this->redis;
        if ($redis === null) {
            return [];
        }

        // XREVRANGE, not XRANGE. XRANGE with '-'->'+' returns the OLDEST
        // entries first, so an operator triaging a live incident saw the
        // oldest 50 dead letters while the ones that had just failed — the
        // ones actually being debugged — were hidden past the slice. The
        // docblock above claimed "most recent" and the code did the opposite,
        // which is worse than a missing feature: the tool looked authoritative
        // while pointing at the wrong end of the problem.
        $raw = $redis->xrevrange(StreamSchema::DL, '+', '-', $this->limit);
        $rows = [];

        foreach (self::normalizeEntries($raw) as $id => $fields) {
            $rows[] = [
                'id' => (string) $id,
                'reason' => (string) ($fields['reason'] ?? ''),
                'error' => (string) ($fields['error'] ?? ''),
                'account_id' => (int) ($fields['account_id'] ?? 0),
                'ts' => (int) ($fields['ts'] ?? 0),
            ];
        }

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function supervisedRows(): array
    {
        if ($this->supervised === null) {
            return [];
        }

        try {
            return ($this->supervised)();
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Normalize a driver-dependent XRANGE reply into id => fields. Tolerates
     * phpredis' associative shape and predis' flat [id, [k, v, ...]] pairs —
     * the same normalization IngestConsumer performs.
     *
     * @param  array<mixed>  $raw
     * @return array<string, array<string, string>>
     */
    private static function normalizeEntries(array $raw): array
    {
        $entries = [];

        foreach ($raw as $id => $fields) {
            if (! is_array($fields)) {
                continue;
            }

            $isList = array_is_list($fields);
            $entryId = is_int($id) ? (string) ($fields[0] ?? $id) : (string) $id;
            $body = is_int($id) ? array_slice($fields, 1) : $fields;

            $paired = [];
            if (array_is_list($body)) {
                for ($i = 0, $n = count($body); $i + 1 < $n; $i += 2) {
                    $paired[(string) $body[$i]] = (string) $body[$i + 1];
                }
            } else {
                foreach ($body as $key => $value) {
                    $paired[(string) $key] = (string) $value;
                }
            }

            $entries[$entryId] = $paired;
        }

        return $entries;
    }
}
