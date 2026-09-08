<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Console;

use Illuminate\Console\Command;

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
        {--max=0 : Daemon loop: stop after this many iterations (0 = run until signaled)}';

    protected $description = 'Consume the teleframe Redis bus stream into Postgres truth';

    protected $aliases = [];

    private bool $shouldStop = false;

    public function handle(): int
    {
        $consumer = $this->getLaravel()->make(self::CONSUMER_CLASS);

        if ((bool) $this->option('once')) {
            $this->info(sprintf('processed %d entries', $consumer->consumeOnce()['processed']));

            return self::SUCCESS;
        }

        // Daemon loop: consumeOnce() batches until --max iterations or a
        // graceful SIGTERM/SIGINT (honored after the current batch
        // completes). Without pcntl (Windows) there is no signal handling:
        // --max is then the only stop, so warn when the loop is unbounded.
        $max = max(0, (int) $this->option('max'));

        if (!$this->installSignalHandlers() && $max === 0) {
            $this->warn('pcntl unavailable (Windows?) — no signal handling; use --max to bound the loop');
        }

        $iterations = 0;
        while (!$this->shouldStop) {
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
        if (!function_exists('pcntl_signal') || !function_exists('pcntl_async_signals')) {
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
