<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Supervisor;

use MeRezaRezaei\Teleframe\Bus\StreamSchema;

/**
 * The operations read-model's value: one json_encode()-ready snapshot of
 * everything the package ALREADY records about failing work.
 *
 * `daemon_rotation` is deliberately reported as NOT RECORDED — the daemon's
 * rotation/seat state is process-local (Daemon::seat() keeps it in the
 * daemon's own memory), so the snapshot says so instead of inventing a
 * durable store for it. See docs/superpowers/specs/2026-09-26-
 * package-owned-process-supervisor.md ruling 6.
 */
final class OpsSnapshot
{
    /**
     * @param  bool  $bus  false when no Redis seam was available to read (unconfigured host).
     * @param  list<array{entry_id: string, attempts: int, failing: bool}>  $failingIngest  entries with a strike tally, worst first.
     * @param  list<array{id: string, reason: string, error: string, account_id: int, ts: int}>  $deadLetters
     * @param  array<string, int>  $backlog  stream name => entry count.
     * @param  list<array<string, mixed>>  $supervised  Supervisor::report() rows.
     * @param  array<string, string>  $sources  source name => 'ok' | 'unavailable'.
     */
    public function __construct(
        public readonly bool $bus,
        public readonly array $failingIngest = [],
        public readonly array $deadLetters = [],
        public readonly array $backlog = [],
        public readonly array $supervised = [],
        public readonly array $sources = [],
        public readonly string $daemonRotation = 'not-recorded',
    ) {}

    /**
     * @return array<string, mixed>
     */
    /**
     * The real number of failing entries, not the number sampled.
     *
     * The strikes hash counts them exactly; the sample exists only so the
     * panel can SHOW a few. Prefer the true count, and fall back to the
     * sample's size only when no hash is bound at all, which is the only
     * situation where the sample size is the best number available.
     */
    private function failingTotal(): int
    {
        $total = $this->backlog[StreamSchema::STRIKES_KEY] ?? null;

        if (is_int($total)) {
            return $total;
        }

        return count($this->failingIngest);
    }

    public function toArray(): array
    {
        return [
            'bus' => $this->bus,
            'failing_ingest' => $this->failingIngest,
            'dead_letters' => $this->deadLetters,
            'backlog' => $this->backlog,
            'supervised' => $this->supervised,
            'daemon_rotation' => $this->daemonRotation,
            'sources' => $this->sources,
            'summary' => [
                // The TRUE totals, not the size of the sample.
                //
                // `count()` over an already-sliced list reported the SLICE: an
                // operator with 500 failing entries was told 50, on a panel
                // whose whole purpose is telling an operator the scale of an
                // incident. `failing_ingest` had no true total anywhere to
                // contradict it, so nothing else on the page disagreed either.
                // The strikes hash is the one place that knows the real number.
                'failing_ingest' => $this->failingTotal(),
                'dead_letters' => count($this->deadLetters),
                'dead_letter_total' => $this->backlog[StreamSchema::DL] ?? 0,
                'stream_backlog' => $this->backlog[StreamSchema::STREAM] ?? 0,
                'supervised_running' => $this->countRunning(),
            ],
        ];
    }

    private function countRunning(): int
    {
        $running = 0;
        foreach ($this->supervised as $row) {
            if (($row['status'] ?? null) === ProcessState::RUNNING) {
                $running++;
            }
        }

        return $running;
    }
}
