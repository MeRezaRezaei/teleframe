<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Notify;

use InvalidArgumentException;
use JsonException;
use RuntimeException;

/**
 * FileWatchAlertSink — the shipped WatchAlertSinkContract: one JSON object per
 * line, appended, never rewritten.
 *
 * APPEND-ONLY IS THE POINT. A no-show record is evidence ("at HH:MM:SS the
 * backup heartbeat was N seconds overdue") and evidence that gets edited by
 * whatever wrote it is not evidence. Nothing here truncates, rotates or
 * rewrites; the host rotates by whatever already owns its storage directory.
 *
 * WHY A FILE, given the engine's PSR-3 seam already writes a line. Because the
 * PSR-3 line is the failure: it goes wherever the host's logger goes, and the
 * operator's complaint was exactly that a logger line is a place only the host
 * can see. This sink is deliberately the DUMBEST durable thing available — no
 * cache, no queue, no database, no network, no container — so its failure
 * domain cannot overlap the failure it is reporting. A disk that cannot take a
 * 200-byte append is a genuinely broken host, and record() throws.
 *
 * CONCURRENCY. Two watchdog checks in the same second (an hourly tick and a
 * Temporal activity, say) interleave at write time, not at line time: LOCK_EX
 * makes each append atomic, so a line is never torn.
 *
 * Zero regex — string functions, integer range checks and json_encode only.
 */
final class FileWatchAlertSink implements WatchAlertSinkContract
{
    /** The record kind stamped on every line, so a mixed log is still greppable. */
    public const KIND = 'notify.watchdog.no-show';

    /**
     * @param  string  $path  the append-only file; its directory is created 0700 on first write
     * @param  \Closure(): int|null  $now  injected clock (default time())
     *
     * @throws InvalidArgumentException When $path is blank — a sink that cannot
     *                                  name its file is a wiring error, never a
     *                                  silently discarded alert.
     */
    public function __construct(
        private readonly string $path,
        private readonly ?\Closure $now = null,
    ) {
        if (trim($path) === '') {
            throw new InvalidArgumentException('watchdog alert sink path must not be empty.');
        }
    }

    public function path(): string
    {
        return $this->path;
    }

    /**
     * @param  array<string, mixed>  $context
     *
     * @throws JsonException When the row cannot be encoded.
     * @throws RuntimeException When the file cannot be written.
     */
    public function record(WatchAlert $alert, array $context): void
    {
        // A FIXED top-level schema taken from the alert itself, so a reader of
        // the log — a `tail`, a shipper, a human six months from now — never has
        // to know what the caller happened to name its context array today.
        // The context travels verbatim underneath, additive, for anything the
        // caller wants to carry beyond these four numbers.
        $row = [
            'kind' => self::KIND,
            'recorded_at' => ($this->now ?? static fn (): int => time())(),
            'topic' => $alert->topic,
            'expected_every_seconds' => $alert->expectedEvery,
            'last_seen' => $alert->lastSeen,
            'age_seconds' => $alert->age,
        ];

        if ($context !== []) {
            $row['context'] = $context;
        }

        $this->append(
            json_encode($row, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n",
        );
    }

    /** @throws RuntimeException When the line could not be appended. */
    private function append(string $line): void
    {
        $dir = dirname($this->path);
        if (! is_dir($dir) && ! @mkdir($dir, 0700, true) && ! is_dir($dir)) {
            throw new RuntimeException('watchdog alert log directory is not creatable: '.$dir);
        }

        $existed = is_file($this->path);

        // LOCK_EX, not LOCK_NB: a second watchdog in the same second waits for
        // the first's append instead of racing it and losing the finding.
        $written = @file_put_contents($this->path, $line, FILE_APPEND | LOCK_EX);

        if ($written === false || $written === 0) {
            throw new RuntimeException('watchdog alert log write failed: '.$this->path);
        }

        if (! $existed) {
            @chmod($this->path, 0600);
        }
    }
}
