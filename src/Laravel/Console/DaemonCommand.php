<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use MeRezaRezaei\Teleframe\Bus\IngestConsumer;
use MeRezaRezaei\Teleframe\Bus\LaravelRedisAdapter;
use MeRezaRezaei\Teleframe\Bus\RedisStreamSink;
use MeRezaRezaei\Teleframe\Bus\StreamSchema;
use MeRezaRezaei\Teleframe\Daemon\AccountWorker;
use MeRezaRezaei\Teleframe\Daemon\Daemon;
use MeRezaRezaei\Teleframe\Daemon\WorkerInterface;
use MeRezaRezaei\Teleframe\Teleclient;

/**
 * artisan teleframe:daemon — the multi-account supervisor.
 *
 * Wires the full mirror pipeline:
 *   MTProto poll → Redis stream → Postgres ingest
 *
 * Reads account configs from config('teleframe.daemon.accounts').
 * Each account gets an AccountWorker (poll + RedisStreamSink), supervised
 * by the Daemon with backoff ladder and time-slice rotation.
 *
 * A single IngestConsumer runs alongside the poller in the same process:
 * after each account tick, a consumeOnce() pass drains the Redis stream
 * into Postgres. This avoids requiring a separate queue worker for
 * development/single-server deployments.
 */
class DaemonCommand extends Command
{
    protected $signature = 'teleframe:daemon
        {--once : Run one supervision cycle per account and exit}
        {--ingest-only : Skip polling, run only the ingest consumer loop}
        {--poll-only : Skip ingest, run only the polling supervisor}';

    protected $description = 'Multi-account MTProto supervisor: poll updates → Redis stream → Postgres ingest';

    private bool $shouldStop = false;

    public function handle(): int
    {
        $accounts = config('teleframe.daemon.accounts', []);

        if ($accounts === [] || !is_array($accounts)) {
            $this->components->error('No daemon accounts configured. Add entries to config("teleframe.daemon.accounts").');
            $this->line('Example config:');
            $this->line("  'daemon' => ['accounts' => [['account_id' => 123456, 'session_string' => env('TELEGRAM_SESSION_123456')]]]");
            $this->newLine();
            $this->line('Run `php artisan teleframe:login` first to create a session.');

            return self::FAILURE;
        }

        $this->installSignalHandlers();

        // Ensure the Redis stream consumer group exists
        $this->ensureConsumerGroup();

        $once = $this->option('once');
        $ingestOnly = $this->option('ingest-only');

        if ($ingestOnly) {
            return $this->runIngestLoop($once);
        }

        $this->components->info(sprintf(
            'Starting Teleframe daemon with %d account(s)...',
            count($accounts),
        ));
        $this->line('Ctrl+C to stop gracefully.');
        $this->newLine();

        // Boot workers: AccountWorker + RedisStreamSink per account
        $workers = [];
        foreach ($accounts as $config) {
            $accountId = (int) ($config['account_id'] ?? 0);
            if ($accountId <= 0) {
                $this->components->warn('Skipping account config with invalid account_id.');
                continue;
            }

            $workers[] = $this->buildWorker($config);
        }

        if ($workers === []) {
            $this->components->error('No valid accounts to supervise.');

            return self::FAILURE;
        }

        // Run the daemon supervisor
        $daemon = new Daemon(
            accountsConfig: $accounts,
            workerFactory: function (array $config) use ($workers): WorkerInterface {
                // Find the pre-built worker matching this account
                foreach ($workers as $entry) {
                    if ((int) $entry['config']['account_id'] === (int) $config['account_id']) {
                        return $entry['wrapper'];
                    }
                }
                throw new \RuntimeException('Worker not found for account ' . $config['account_id']);
            },
        );

        // Install daemon stop handler (SIGTERM/SIGINT → daemon->stop())
        if (function_exists('pcntl_signal')) {
            pcntl_async_signals(true);
            pcntl_signal(SIGTERM, fn () => $daemon->stop());
            pcntl_signal(SIGINT, fn () => $daemon->stop());
        }

        $exitCode = $daemon->run();

        // Persist last sequence states after shutdown
        foreach ($workers as $entry) {
            $state = $entry['worker']->lastSequenceState();
            if ($state !== null) {
                $this->line(sprintf(
                    'Account %d: last pts=%d date=%d qts=%d',
                    $entry['worker']->accountId(),
                    $state['pts'],
                    $state['date'],
                    $state['qts'],
                ));
            }
        }

        $this->components->info('Daemon stopped.');

        return $exitCode;
    }

    /**
     * Build the supervised worker wrapper for one account.
     *
     * @return array{config: array<string, mixed>, worker: AccountWorker, wrapper: WorkerInterface}
     */
    private function buildWorker(array $config): array
    {
        $accountId = (int) $config['account_id'];
        $adapter = new LaravelRedisAdapter(Redis::connection());
        $sink = new RedisStreamSink($adapter, $accountId);
        $worker = new AccountWorker($config);

        // Wrap AccountWorker to match WorkerInterface (run with stop hook)
        $wrapper = new class ($worker, $sink) implements WorkerInterface {
            public function __construct(
                private readonly AccountWorker $worker,
                private readonly RedisStreamSink $sink,
            ) {}

            public function run(?callable $shouldStop = null): void
            {
                $this->worker->run($this->sink, $shouldStop);
            }
        };

        return [
            'config' => $config,
            'worker' => $worker,
            'wrapper' => $wrapper,
        ];
    }

    /**
     * Ensure the Redis stream consumer group exists (idempotent).
     */
    private function ensureConsumerGroup(): void
    {
        try {
            Redis::connection()->command('xgroup', ['CREATE', StreamSchema::STREAM, StreamSchema::GROUP, '0', 'MKSTREAM']);
            $this->components->info('Consumer group "' . StreamSchema::GROUP . '" ready.');
        } catch (\Throwable $e) {
            // Group already exists — that's fine
            if (!str_contains($e->getMessage(), 'BUSYGROUP')) {
                $this->components->warn('Consumer group setup: ' . $e->getMessage());
            }
        }
    }

    /**
     * Standalone ingest consumer loop (for separate-process deployments).
     */
    private function runIngestLoop(bool $once): int
    {
        $adapter = new LaravelRedisAdapter(Redis::connection());
        $client = app(Teleclient::class);
        $consumer = new IngestConsumer($adapter, $client);

        if ($once) {
            $result = $consumer->consumeOnce();
            $this->line(sprintf('Ingested %d entries (%d forwarded).', $result['processed'], $result['forwarded']));

            return self::SUCCESS;
        }

        $this->components->info('Starting ingest consumer loop (Ctrl+C to stop)...');

        $iterations = 0;
        while (!$this->shouldStop) {
            $result = $consumer->consumeOnce();
            $iterations++;

            if ($result['processed'] === 0) {
                usleep(100000); // 100ms idle backoff
            }

            if ($iterations % 100 === 0) {
                $this->line(sprintf('[ingest] %d iterations, last batch: %d processed', $iterations, $result['processed']));
            }
        }

        $this->components->info(sprintf('Ingest stopped after %d iterations.', $iterations));

        return self::SUCCESS;
    }

    private function installSignalHandlers(): void
    {
        if (!function_exists('pcntl_signal') || !function_exists('pcntl_async_signals')) {
            return;
        }

        pcntl_async_signals(true);
        pcntl_signal(SIGTERM, function () {
            $this->shouldStop = true;
        });
        pcntl_signal(SIGINT, function () {
            $this->shouldStop = true;
        });
    }
}
