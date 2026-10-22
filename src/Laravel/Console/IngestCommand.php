<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Console;

use Illuminate\Console\Command;
use MeRezaRezaei\Teleframe\Bus\StreamSchema;

/**
 * artisan teleframe:ingest — the bus drain loop (plan Phase 3, Task 3).
 *
 * Deliberately a thin shell: all consumption logic lives in the consumer
 * resolved from the container at self::CONSUMER_CLASS (string literal on
 * purpose — the Bus consumer lands from the parallel Task 2), so tests and
 * host apps can swap the implementation by rebinding that key.
 */
final class IngestCommand extends Command
{
    /** Container key the consumer is resolved from; string literal on purpose (parallel-landing Task 2). */
    public const CONSUMER_CLASS = 'MeRezaRezaei\Teleframe\Bus\IngestConsumer';

    protected $signature = 'teleframe:ingest
        {--once : Process a single consumeOnce() batch and exit}
        {--max=0 : Daemon loop: stop after this many iterations (0 = run until signaled)}
        {--replay-dead-letters : Move dead-lettered updates back onto the live stream and exit. Entries that are not retryable are left in place for forensics.}';

    protected $description = 'Consume the teleframe Redis bus stream into Postgres truth';

    protected $aliases = [];

    private bool $shouldStop = false;

    public function handle(): int
    {
        $consumer = $this->getLaravel()->make(self::CONSUMER_CLASS);

        // An operator's only route back from a dead letter. The consumer has
        // had a correct `replayDeadLetter()` all along — it strips the
        // reason/error tag, re-publishes the FULL original payload onto the
        // live stream, and deletes the dead-letter copy — but nothing called
        // it, so the dead-letter stream was a write-only sink: updates arrived
        // there and the only reachable surface anywhere was a COUNT on the
        // doctor panel. `teleframe:doctor` reports how many are waiting and
        // offered no way to do anything about it.
        if ((bool) $this->option('replay-dead-letters')) {
            $replayed = $consumer->replayDeadLetter();
            $this->info("Replayed {$replayed} dead-lettered update(s) back onto ".StreamSchema::STREAM.'.');

            if ($replayed === 0) {
                $this->components->warn('Nothing was replayed. Either the dead-letter stream is empty, or every entry in it is poison and stays put for forensics.');
            }

            return self::SUCCESS;
        }

        // XREADGROUP throws NOGROUP on a stream it has never seen. This
        // command is the separate-process deployment the docs recommend, and
        // it created no group, so against a fresh Redis it died on its very
        // first batch with
        //   NOGROUP No such key 'tg:stream:updates' or consumer group
        //   'teleclient' in XREADGROUP
        // and the loop never started. The invariant lives on the consumer so
        // it cannot drift from the daemon's copy again.
        //
        // A host that rebinds CONSUMER_CLASS to its own consumer is taking on
        // the same contract: it must ensure its own group.
        //
        // STOP, do not fall through. Without the group every xreadgroup throws
        // NOGROUP, so continuing reports "processed 0 entries" and exits 0 —
        // the command claims to have run an ingest that never happened. This
        // printed an error and then carried on regardless, which is the exact
        // false-success shape this pass exists to remove.
        if (! $consumer->ensureGroup()) {
            $this->components->error('Could not create the Redis consumer group — refusing to run.');

            return self::FAILURE;
        }

        if ((bool) $this->option('once')) {
            $this->info(sprintf('processed %d entries', $consumer->consumeOnce()['processed']));

            return self::SUCCESS;
        }

        // Daemon loop: consumeOnce() batches until --max iterations or a
        // graceful SIGTERM/SIGINT (honored after the current batch
        // completes). Without pcntl (Windows) there is no signal handling:
        // --max is then the only stop, so warn when the loop is unbounded.
        $max = max(0, (int) $this->option('max'));

        if (! $this->installSignalHandlers() && $max === 0) {
            $this->warn('pcntl unavailable (Windows?) — no signal handling; use --max to bound the loop');
        }

        $iterations = 0;
        while (! $this->shouldStop) {
            $batch = $consumer->consumeOnce();
            $iterations++;

            if ($batch['processed'] === 0) {
                usleep(100000); // 100ms idle backoff between empty batches
            }

            if ($max > 0 && $iterations >= $max) {
                break;
            }
        }

        $this->info(sprintf('ingest stopped after %d iterations', $iterations));

        return self::SUCCESS;
    }

    /**
     * Best-effort pcntl wiring. @return bool true when handlers were armed.
     */
    private function installSignalHandlers(): bool
    {
        if (! function_exists('pcntl_signal') || ! function_exists('pcntl_async_signals')) {
            return false;
        }

        pcntl_async_signals(true);
        $halt = function (int $signal): void {
            $this->shouldStop = true;
        };
        pcntl_signal(SIGTERM, $halt);
        pcntl_signal(SIGINT, $halt);

        return true;
    }
}
