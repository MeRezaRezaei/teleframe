<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Redis;
use JsonException;
use MeRezaRezaei\Teleframe\AccountLifecycle\AccountAdded;
use MeRezaRezaei\Teleframe\AccountLifecycle\AccountLifecycleObserver;
use MeRezaRezaei\Teleframe\AccountLifecycle\AccountRemoved;
use MeRezaRezaei\Teleframe\AccountLifecycle\BotAdded;
use MeRezaRezaei\Teleframe\Bus\IngestConsumer;
use MeRezaRezaei\Teleframe\Bus\LaravelRedisAdapter;
use MeRezaRezaei\Teleframe\Bus\RedisConnectionContract;
use MeRezaRezaei\Teleframe\Bus\RedisStreamSink;
use MeRezaRezaei\Teleframe\Bus\StreamSchema;
use MeRezaRezaei\Teleframe\Core\MTProto\SessionData;
use MeRezaRezaei\Teleframe\Daemon\AccountWorker;
use MeRezaRezaei\Teleframe\Daemon\Daemon;
use MeRezaRezaei\Teleframe\Daemon\WorkerInterface;
use MeRezaRezaei\Teleframe\Handler\HandlerSink;
use MeRezaRezaei\Teleframe\Handler\UpdateDispatcher;
use MeRezaRezaei\Teleframe\Ingest\AccountRouteSeeder;
use MeRezaRezaei\Teleframe\Laravel\Models\TfAccount;
use MeRezaRezaei\Teleframe\Teleclient;
use Throwable;

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
 * on each rotation, a consumeOnce() pass drains the Redis stream
 * into Postgres. This avoids requiring a separate queue worker for
 * development/single-server deployments.
 *
 * DB-backed registry (Cycle-4 sub-item 2): when the tf_accounts table exists
 * and has rows, the enabled rows become the LIVE account set (the legacy
 * config seed only supplies dialable credentials — session_string — for the
 * account_ids the registry lists; registry rows without a matching seed
 * entry are dialed through the host's session_ref → SessionData resolver
 * seam when one is bound, warned and skipped otherwise).
 * When the table is empty or not migrated, the config seed governs as-is.
 *
 * Hot re-seat WITHOUT restart (Cycle-5 item 1): the seated worker set is a
 * mutable map keyed by account_id (self::$workerEntries). Every rotation
 * the daemon re-reads the tf_accounts registry, diffs it against the seated
 * set (Daemon::seat()) and seats/stop workers for added/removed accounts,
 * leaving unchanged accounts running (no reconnect). A tg:bus:reload
 * 'accounts' payload (AccountLifecycleObserver publishes {kind:'accounts',
 * account_id, change} on StreamSchema::RELOAD_CHANNEL) is consumed by the
 * watcher armed in armReloadWatch(): it first tries the non-blocking
 * psubscribe() contract and falls back to the loopback subscribe() the
 * in-memory ArrayRedis double honors. With the production adapter BOTH
 * refuse — a real SUBSCRIBE blocks, and the installed predis has no
 * non-blocking read — and the refusal is now reported as a warning
 * instead of being swallowed, so the dead watcher can no longer read as
 * a live one. The per-rotation tf_accounts registry re-read is therefore
 * the real hot-reload path; it needs no pub/sub at all.
 *
 * Auto-registration on discovery (Cycle-5 item 2): when a NEW account is
 * seated, the daemon (1) dispatches AccountAdded / BotAdded on the event
 * dispatcher, and (2) pre-seeds the account's default route through the
 * route-seeder seam (AccountRouteSeeder default: a store_only catch-all at
 * peer 0/0 + a Redis-2 cache refresh) so the router answers for the account
 * immediately. On unseat it dispatches AccountRemoved and forgets the
 * account's routing cache. The DB-discovery event path exists ALONGSIDE the
 * AccountLifecycleObserver's Eloquent path — a host that writes tf_accounts
 * rows on another process still gets the events locally when the reseat
 * spots the diff.
 *
 * The SESSION_RESOLVER_KEY callable turns an opaque session_ref into
 * SessionData (never a session string); the default container has no
 * resolver bound — registry-only accounts (no config-seed entry) are dialed
 * ONLY when the host binds one.
 */
class DaemonCommand extends Command
{
    /** Container seam: callable(array<string,mixed>, int): void — the NF5 mirror ingester (verbatim daily loop). */
    public const MIRROR_INGESTER_KEY = 'teleclient.ingest.mirror-ingester';

    /**
     * Container seam: callable(array<string,mixed>): array{config, worker, wrapper} —
     * replaces the default AccountWorker builder in tests (hot re-seat loop
     * coverage without dialing the wire). Same seam the daemon uses for
     * boot-time AND re-seat worker builds, so seats stay consistent.
     */
    public const WORKER_FACTORY_KEY = 'teleclient.daemon.worker-factory';

    /**
     * Container seam: callable(string): ?SessionData — turns an OPAQUE
     * tf_accounts.session_ref into dialable SessionData for registry rows
     * that have no config-seed credentials (Cycle-5 item 2 discovery). Never
     * a session string in or out.
     *
     * BOUND BY DEFAULT since 2026-09-27, via bindIf in
     * TeleframeServiceProvider: it loads the session from the encrypted
     * telegram_accounts column. It was previously unbound, so a host whose
     * only accounts lived in the database had every row skipped with
     * "no session resolver — skipped" and a daemon that looked healthy while
     * dialing nothing. bindIf means a host binding its own still wins.
     */
    public const SESSION_RESOLVER_KEY = 'teleclient.daemon.session-resolver';

    /**
     * Container seam: object (AccountRouteSeeder default) — pre-seeds the
     * account-level default route on discovery (seed()) and forgets the hot
     * routing cache on unseat (forget()). Hosts may rebind any object with
     * the same two methods.
     */
    public const ROUTE_SEEDER_KEY = 'teleclient.daemon.route-seeder';

    protected $signature = 'teleframe:daemon
        {--once : Run one supervision cycle per account and exit}
        {--ingest-only : Skip polling, run only the ingest consumer loop}
        {--poll-only : Skip ingest, run only the polling supervisor}';

    protected $description = 'Multi-account MTProto supervisor: poll updates → Redis stream → Postgres ingest';

    private bool $shouldStop = false;

    /** @var array<int, array{config: array<string, mixed>, worker: object, wrapper: WorkerInterface}> */
    private array $workerEntries = [];

    private bool $reloadRequested = false;

    /**
     * In-process memo of session_ref → credentials from the resolver seam:
     * a registry-only account that stays seated is NOT re-resolved on every
     * rotation. Reset per command run (a fresh handle() re-dials the vault).
     *
     * @var array<string, array{session_data: SessionData}|null>
     */
    private array $resolvedCredentials = [];

    public function handle(): int
    {
        $accounts = $this->resolveAccounts();

        if ($accounts === []) {
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

        // Warm the reload watcher best-effort: when the bus honors the
        // loopback subscribe semantic (in-memory double), 'accounts' reload
        // payloads nudge the next rotation to re-read the registry. A real
        // pub/sub loop needs its own process, so this is never fatal — the
        // per-rotation registry re-read is the reliable no-restart path.
        $this->armReloadWatch();

        // Supervise only configs with a valid positive account_id.
        $validAccounts = [];
        foreach ($accounts as $config) {
            $accountId = (int) ($config['account_id'] ?? 0);
            if ($accountId <= 0) {
                $this->components->warn('Skipping account config with invalid account_id.');

                continue;
            }

            $validAccounts[] = $config;
        }

        if ($validAccounts === []) {
            $this->components->error('No valid accounts to supervise.');

            return self::FAILURE;
        }

        // Run the daemon supervisor. Workers are built lazily and recorded
        // in self::$workerEntries (keyed by account_id); the daemon's loop
        // re-reads the tf_accounts registry each rotation and re-seats the
        // set with zero restart (Cycle-5 item 1).
        $daemon = $this->buildDaemon($validAccounts);

        // Install daemon stop handler (SIGTERM/SIGINT → daemon->stop())
        if (function_exists('pcntl_signal')) {
            pcntl_async_signals(true);
            pcntl_signal(SIGTERM, fn () => $daemon->stop());
            pcntl_signal(SIGINT, fn () => $daemon->stop());
        }

        $exitCode = $daemon->run();

        // Why did it stop? Daemon::run() stores per-account failures in
        // lastErrors(), and handle() never read them. A revoked session
        // therefore produced: a start line, ~82s of nothing, then an
        // UNCONDITIONAL "Daemon stopped." and exit 1. Under the systemd
        // `Restart=always` / docker `restart: always` that every long-running
        // daemon uses, that is an unbounded restart loop re-dialling a
        // permanently dead session every ~82 seconds, printing the same two
        // lines forever.
        $lastErrors = $daemon->lastErrors();
        if ($lastErrors !== []) {
            $this->newLine();
            $this->components->error(sprintf('Daemon stopped with %d account error(s):', count($lastErrors)));
            foreach ($lastErrors as $accountId => $reason) {
                $this->line(sprintf('  account %s: %s', (string) $accountId, $reason));
            }
            $this->newLine();
            $this->line('A revoked or expired auth key cannot recover by retrying — re-run `php artisan teleframe:login` for that account.');
        }

        // Persist last sequence states after shutdown
        foreach ($this->workerEntries as $entry) {
            if (! $entry['worker'] instanceof AccountWorker) {
                continue; // host/fake workers have no sequence cursor to persist
            }

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

        // Only a clean exit gets the reassuring line. Printing it after a
        // failure made a revoked session look like a graceful shutdown.
        if ($exitCode === self::SUCCESS) {
            $this->components->info('Daemon stopped.');
        }

        return $exitCode;
    }

    /**
     * The bus drain, built once and reused. Building it per rotation would
     * re-resolve the client and the handler sink every tick.
     */
    private ?IngestConsumer $busDrain = null;

    /**
     * One drain pass of the update bus, on the daemon's own rotation.
     *
     * Failure is contained: a drain that throws must never take the polling
     * daemon down, because the daemon is also the thing keeping sessions warm.
     * It is reported and the next rotation tries again.
     */
    private function drainBusOnce(): void
    {
        try {
            $consumer = $this->busDrain ??= $this->buildBusDrain();
            $consumer->ensureGroup();
            $result = $consumer->consumeOnce();

            if ($result['processed'] > 0) {
                $this->line(sprintf('[bus] drained %d entries (%d forwarded)', $result['processed'], $result['forwarded']));
            }
        } catch (Throwable $e) {
            $this->components->warn('[bus] drain failed: '.$e->getMessage());
        }
    }

    /**
     * The daemon-mode consumer, wired to the same pipeline the standalone
     * ingest loop uses.
     */
    private function buildBusDrain(): IngestConsumer
    {
        $adapter = new LaravelRedisAdapter(Redis::connection());
        $client = app(Teleclient::class);
        $mirror = app()->bound(self::MIRROR_INGESTER_KEY) ? app(self::MIRROR_INGESTER_KEY) : null;
        $routed = $this->handlerSink();

        return new IngestConsumer(
            $adapter,
            $client,
            null,
            $routed === null
                ? null
                : static fn (array $update, int $accountId): bool => $routed->handle($update, (string) $accountId),
            $mirror,
        );
    }

    /**
     * The daemon's account list: the DB-backed tf_accounts registry when it
     * has enabled rows, otherwise the config('teleframe.daemon.accounts')
     * bootstrap seed (Cycle-4 option A — DB is the live registry).
     *
     * @return list<array<string, mixed>>
     */
    private function resolveAccounts(): array
    {
        $seed = (array) config('teleframe.daemon.accounts', []);
        $registry = $this->registryAccountConfigs();

        if ($registry === []) {
            return $seed; // table empty / not migrated — the seed still governs
        }

        $seedById = [];
        foreach ($seed as $entry) {
            $seedById[(int) ($entry['account_id'] ?? 0)] = $entry;
        }

        $resolver = $this->sessionResolver();
        $memoized = $this->resolvedCredentials;

        $accounts = [];
        foreach ($registry as $row) {
            $accountId = (int) $row['account_id'];
            $credentials = $seedById[$accountId] ?? null;

            if ($credentials === null && $resolver !== null) {
                $sessionRef = (string) $row['session_ref'];

                if (array_key_exists($sessionRef, $memoized)) {
                    // Same opaque ref resolved earlier in this process: reuse
                    // the answer instead of re-dialing the resolver vault on
                    // every rotation for accounts that stay seated.
                    $credentials = $memoized[$sessionRef];
                } else {
                    // Registry-only discovery: the host's session_ref → SessionData
                    // resolver provides the dialable credential — never a session
                    // string.
                    $session = $resolver($sessionRef);
                    $credentials = $session instanceof SessionData ? ['session_data' => $session] : null;
                    $memoized[$sessionRef] = $credentials;
                }

                if ($credentials === null) {
                    $this->components->warn(sprintf(
                        'Registry account %d: the daemon session resolver returned no SessionData — skipped.',
                        $accountId,
                    ));
                }
            }

            if ($credentials === null) {
                // No legacy credentials and no resolver answer. session_ref stays
                // opaque in logs — it is never a session string, but it is also
                // not printed here by design.
                $this->components->warn(sprintf(
                    'Registry account %d has no dialable credentials (config seed) and no session resolver — skipped.',
                    $accountId,
                ));

                continue;
            }

            // Registry shapes membership/DC; the seed (or the resolver)
            // supplies the live credentials for this id.
            $accounts[] = array_replace($row, $credentials);
        }

        $this->resolvedCredentials = $memoized;

        return $accounts;
    }

    /**
     * Enabled tf_accounts rows mapped to AccountWorker config shapes.
     * Best-effort: an unmigrated table reads as empty (config-seed fallback).
     *
     * @return list<array{account_id: int, dc: int, kind: string, label: string, session_ref: string}>
     */
    private function registryAccountConfigs(): array
    {
        try {
            $rows = TfAccount::query()->where('enabled', true)->orderBy('id')->get();
        } catch (\Exception) {
            return []; // tf_accounts not migrated yet
        }

        $configs = [];
        foreach ($rows as $row) {
            $configs[] = [
                'account_id' => (int) $row->id,
                'dc' => (int) $row->dc_id,
                'kind' => (string) $row->kind,
                'label' => (string) $row->label,
                'session_ref' => (string) $row->session_ref,
            ];
        }

        return $configs;
    }

    /**
     * The supervised worker set, keyed by account_id: lazily built by the
     * daemon's workerFactory (boot AND hot re-seat go through the same
     * seam), so seats stay consistent and unchanged accounts never rebuild.
     */
    private function seatWorker(array $config): void
    {
        $accountId = (int) $config['account_id'];

        if (isset($this->workerEntries[$accountId])) {
            return; // already seated — keep it running, no reconnect
        }

        $this->workerEntries[$accountId] = $this->buildWorker($config);
    }

    /**
     * Build the supervised worker wrapper for one account.
     *
     * @return array{config: array<string, mixed>, worker: object, wrapper: WorkerInterface}
     */
    private function buildWorker(array $config): array
    {
        $factory = app()->bound(self::WORKER_FACTORY_KEY) ? app(self::WORKER_FACTORY_KEY) : null;

        if (is_callable($factory)) {
            $entry = $factory($config);

            if (! is_array($entry) || ! isset($entry['config'], $entry['worker'], $entry['wrapper'])) {
                throw new \RuntimeException(
                    'The '.self::WORKER_FACTORY_KEY.' factory must return {config, worker, wrapper}.',
                );
            }

            $worker = $entry['worker'];
            $wrapper = $entry['wrapper'];

            if (! is_object($worker)) {
                throw new \RuntimeException('The '.self::WORKER_FACTORY_KEY.' factory worker must be an object.');
            }

            if (! $wrapper instanceof WorkerInterface) {
                throw new \RuntimeException(
                    'The '.self::WORKER_FACTORY_KEY.' factory wrapper must implement WorkerInterface.',
                );
            }

            return [
                'config' => (array) $entry['config'],
                'worker' => $worker,
                'wrapper' => $wrapper,
            ];
        }

        $accountId = (int) $config['account_id'];
        $adapter = $this->busAdapter();
        $sink = new RedisStreamSink($adapter, $accountId);
        $worker = new AccountWorker($config);

        // Wrap AccountWorker to match WorkerInterface (run with stop hook)
        $wrapper = new class($worker, $sink) implements WorkerInterface
        {
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
     * The bus adapter the worker sinks write through: the container-bound
     * RedisConnectionContract wins (tests bind the in-memory double), the
     * illuminate/redis facade connection is the production fallback.
     */
    private function busAdapter(): RedisConnectionContract
    {
        try {
            return app()->make(RedisConnectionContract::class);
        } catch (Throwable) {
            return new LaravelRedisAdapter(Redis::connection());
        }
    }

    /**
     * Build the supervisor for the bootstrap account set, wiring the
     * rotation-top re-seat: every iteration re-reads the tf_accounts
     * registry and diff-seats the mutable worker set (hot re-seat).
     *
     * @param  list<array<string, mixed>>  $accounts
     */
    private function buildDaemon(array $accounts): Daemon
    {
        $daemon = new Daemon(
            accountsConfig: $accounts,
            workerFactory: function (array $config): WorkerInterface {
                $this->seatWorker($config);

                return $this->workerEntries[(int) $config['account_id']]['wrapper'];
            },
        );

        // Re-read the live registry each rotation, AND drain the bus.
        //
        // The drain used to exist only inside runIngestLoop(), which runs
        // solely under --ingest-only. In the DEFAULT mode the daemon produced
        // updates onto tg:stream:updates and consumed nothing: the stream grew
        // without bound, tf_messages/tf_users/tf_chats stayed empty, no
        // handler ran, and no Centrifugo event fired — while every health
        // signal (the process, teleframe:doctor, the "Starting Teleframe
        // daemon with N account(s)" line) looked perfectly healthy. The
        // docblock at the top of this file claimed a per-tick drain that never
        // existed.
        $daemon->onLoop(function () use ($daemon): void {
            $this->reseatAccounts($daemon);
            $this->drainBusOnce();
        });

        return $daemon;
    }

    /**
     * Best-effort reload watcher: {kind:'accounts'} payloads on the reload
     * channel nudge the next rotation.
     *
     * Two shapes, tried in order:
     *
     * 1. psubscribe() — the non-blocking contract (returns promptly, handle
     *    exposes only stop()). Only LaravelRedisAdapter has it, and it
     *    refuses with a RuntimeException naming why (predis has no
     *    non-blocking read; Illuminate's createSubscription() is an
     *    unbounded blocking loop on the shared connection).
     * 2. subscribe() — the loopback shape only the in-memory ArrayRedis
     *    double can honor.
     *
     * A refusal is NO LONGER SILENT: it is surfaced as a warning. It used
     * to be swallowed, which made the whole pub/sub half look alive while
     * `reloadRequested` could never be set and the "Reload signal
     * received" line was unreachable — in production hot reload was dead.
     * The per-rotation tf_accounts re-read in reseatAccounts() remains the
     * real no-restart path; the warning says so out loud.
     */
    private function armReloadWatch(): void
    {
        $adapter = $this->busAdapter();

        if (method_exists($adapter, 'psubscribe')) {
            try {
                $adapter->psubscribe([StreamSchema::RELOAD_CHANNEL], function (string $channel, string $payload): void {
                    $this->noteReloadPayload($payload);
                });

                return;
            } catch (Throwable $e) {
                $this->components->warn(sprintf(
                    'Non-blocking reload watch on %s is UNAVAILABLE: %s',
                    StreamSchema::RELOAD_CHANNEL,
                    $e->getMessage(),
                ));
            }
        }

        try {
            $adapter->subscribe(StreamSchema::RELOAD_CHANNEL, function (string $channel, string $payload): void {
                $this->noteReloadPayload($payload);
            });
        } catch (Throwable $e) {
            $this->components->warn(sprintf(
                'Reload watch on %s could not be armed (%s); hot re-seat falls back to the '
                .'per-rotation tf_accounts registry re-read.',
                StreamSchema::RELOAD_CHANNEL,
                $e->getMessage(),
            ));
        }
    }

    /**
     * Consume a reload payload (never throws): only a well-formed
     * {kind:'accounts', ...} nudge counts.
     */
    private function noteReloadPayload(string $payload): void
    {
        try {
            $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return;
        }

        if (is_array($decoded) && ($decoded['kind'] ?? null) === AccountLifecycleObserver::RELOAD_KIND) {
            $this->reloadRequested = true;
        }
    }

    /**
     * The live-account sync (hot re-seat body): re-read the tf_accounts
     * registry (or the config-seed fallback), diff it against the seated
     * set and seat/stop workers for added/removed accounts. Unchanged
     * accounts are left running — no reconnect. Runs every rotation.
     *
     * Discovery side-effects (Cycle-5 item 2): a NEWLY seated account gets
     * its AccountAdded/BotAdded lifecycle event dispatched and its default
     * route pre-seeded (so the router answers + a rule row exists); a
     * stopped account gets AccountRemoved and its routing cache forgotten.
     */
    private function reseatAccounts(Daemon $daemon): void
    {
        if ($this->reloadRequested) {
            $this->reloadRequested = false;
            $this->components->info('Reload signal received — re-reading the account registry.');
        }

        $desired = [];
        foreach ($this->resolveAccounts() as $config) {
            $desired[(int) $config['account_id']] = $config;
        }

        $plan = $this->planReseat($desired, $daemon->seatedIds());

        foreach ($plan['stop'] as $accountId) {
            $departedConfig = $this->workerEntries[$accountId]['config'] ?? [];
            // A stop is NOT always a removal. planReseat() also stops an id whose
            // CONFIG changed — it is still in the registry and is seated again on
            // the next rotation. Firing AccountRemoved + forgetting the route for
            // it would tell every listener a live account went away, which is a
            // different (and larger) lie than the one being fixed here.
            $stillRegistered = isset($desired[$accountId]);

            $daemon->unseat($accountId);
            unset($this->workerEntries[$accountId]);

            if ($stillRegistered) {
                $this->components->warn(sprintf(
                    'Restarting AccountWorker for account %d — its tf_accounts row CHANGED, so the seated '
                    .'config is stale (a rotated credential only takes effect on a rebuilt worker).',
                    (int) $accountId,
                ));

                continue;
            }

            $this->announceRemoved((int) $accountId, $departedConfig);
            $this->forgetRoute((int) $accountId);
            $this->components->warn(sprintf(
                'Stopped AccountWorker for account %d (removed from the tf_accounts registry).',
                $accountId,
            ));
        }

        foreach ($plan['seat'] as $config) {
            try {
                $accountId = (int) $config['account_id'];
                $daemon->seat($config);
                $this->announceAdded($config);
                $this->seedRoute($accountId);
                $this->components->info(sprintf(
                    'Seated AccountWorker for account %d (new in the tf_accounts registry).',
                    $accountId,
                ));
            } catch (Throwable $e) {
                $this->components->warn(sprintf(
                    'Could not seat AccountWorker for account %d: %s',
                    (int) $config['account_id'],
                    $e->getMessage(),
                ));
            }
        }
    }

    /**
     * The daemon's session-ref resolver seam (SESSION_RESOLVER_KEY):
     * callable(string): ?SessionData, or null when the host did not bind one.
     */
    private function sessionResolver(): ?callable
    {
        if (! app()->bound(self::SESSION_RESOLVER_KEY)) {
            return null;
        }

        $resolver = app(self::SESSION_RESOLVER_KEY);

        return is_callable($resolver) ? $resolver : null;
    }

    /**
     * The daemon's route-seeder seam (ROUTE_SEEDER_KEY): an object exposing
     * seed(int)/forget(int) (AccountRouteSeeder default) — or null when
     * nothing is bound.
     */
    private function routeSeeder(): ?object
    {
        if (! app()->bound(self::ROUTE_SEEDER_KEY)) {
            return null;
        }

        $seeder = app(self::ROUTE_SEEDER_KEY);

        return is_object($seeder) ? $seeder : null;
    }

    /**
     * Pre-seed the account-level default route on discovery (best-effort):
     * never blocks a seat when the routing surface is missing.
     */
    private function seedRoute(int $accountId): void
    {
        try {
            $seeder = $this->routeSeeder();

            if ($seeder instanceof AccountRouteSeeder) {
                $seeder->seed($accountId);
            } elseif (is_callable($seeder)) {
                $seeder($accountId);
            }
        } catch (Throwable) {
            // a host without the routing surface never blocks discovery
        }
    }

    /**
     * Forget the account's hot routing cache on unseat (best-effort).
     */
    private function forgetRoute(int $accountId): void
    {
        try {
            $seeder = $this->routeSeeder();

            if ($seeder instanceof AccountRouteSeeder) {
                $seeder->forget($accountId);
            }
        } catch (Throwable) {
            // best-effort cache hygiene, never fatal
        }
    }

    /**
     * Dispatch AccountAdded / BotAdded for a newly discovered account. The
     * event mirrors AccountLifecycleObserver's Eloquent-path payloads, but
     * fires on the DB-discovery path — the running daemon notifies local
     * listeners without any TfAccount write going through this process.
     */
    private function announceAdded(array $config): void
    {
        $events = $this->eventDispatcher();
        if ($events === null) {
            return;
        }

        $accountId = (int) $config['account_id'];
        $kind = (string) ($config['kind'] ?? TfAccount::KIND_ACCOUNT);
        $label = isset($config['label']) ? (string) $config['label'] : null;

        try {
            if ($kind === TfAccount::KIND_BOT) {
                $events->dispatch(new BotAdded($accountId, $label));

                return;
            }

            $events->dispatch(new AccountAdded($accountId, TfAccount::KIND_ACCOUNT, $label));
        } catch (Throwable) {
            // a failing host listener must never break the daemon loop
        }
    }

    /**
     * Dispatch AccountRemoved for an unseated account (kind/label from the
     * departing worker's config, best-effort).
     */
    private function announceRemoved(int $accountId, array $departedConfig): void
    {
        $events = $this->eventDispatcher();
        if ($events === null) {
            return;
        }

        $kind = (string) ($departedConfig['kind'] ?? TfAccount::KIND_ACCOUNT);
        $label = isset($departedConfig['label']) ? (string) $departedConfig['label'] : null;

        try {
            $events->dispatch(new AccountRemoved($accountId, $kind, $label));
        } catch (Throwable) {
            // a failing host listener must never break the daemon loop
        }
    }

    /**
     * The app's event dispatcher, guarded: a host with no dispatcher (or a
     * broken container) simply skips lifecycle events.
     */
    private function eventDispatcher(): ?Dispatcher
    {
        try {
            return app(Dispatcher::class);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Pure diff between the desired registry set and the seated set.
     *
     * ADDED, REMOVED, and CHANGED — the third case used to be invisible.
     * This diff only ever seated ids that were ABSENT and stopped ids that were
     * REMOVED, so an account whose ROW was EDITED — the case that matters most,
     * because a ROTATED SESSION is an edit — stayed seated and its worker was
     * never rebuilt. `Daemon::seat()` early-returns for an already-seated id and
     * `seatWorker()` early-returns too, so nothing downstream could notice either:
     * the plan was `seat=[] stop=[]` and the old session stayed dialed until the
     * daemon was restarted by hand.
     *
     * `AccountLifecycleObserver::updated()` publishes `change: 'changed'` on the
     * reload channel, but `noteReloadPayload()` only flipped a boolean regardless
     * of `change`, so the signal that specifically announced a rotation was
     * treated exactly like an add.
     *
     * A CHANGED id therefore goes into `stop` like a removed one; the rotation
     * after this one seats it again from the new config. That costs one rotation
     * of downtime for a rotated credential, which is the correct trade against
     * running on a session the operator has already replaced.
     *
     * @param  array<int, array<string, mixed>>  $desired  desired configs keyed by account_id
     * @param  list<int>  $seatedIds  currently seated account ids
     * @return array{seat: list<array<string, mixed>>, stop: list<int>}
     */
    private function planReseat(array $desired, array $seatedIds): array
    {
        $plan = ['seat' => [], 'stop' => []];

        foreach ($desired as $accountId => $config) {
            $accountId = (int) $accountId;

            if (! in_array($accountId, $seatedIds, true)) {
                $plan['seat'][] = $config;

                continue;
            }

            if ($this->seatedConfigChanged($accountId, $config)) {
                $plan['stop'][] = $accountId;
            }
        }

        foreach ($seatedIds as $accountId) {
            if (! isset($desired[$accountId])) {
                $plan['stop'][] = $accountId;
            }
        }

        return $plan;
    }

    /**
     * Does the config this daemon seated for `$accountId` differ from the one
     * now desired?
     *
     * No recorded config means UNKNOWN, not changed: `seatWorker()` is what
     * records it, and a worker built by some other path must not be torn down on
     * a comparison this process cannot actually make.
     *
     * @param  array<string, mixed>  $desired
     */
    private function seatedConfigChanged(int $accountId, array $desired): bool
    {
        $seated = $this->workerEntries[$accountId]['config'] ?? null;

        if (! is_array($seated)) {
            return false;
        }

        return self::configFingerprint($seated) !== self::configFingerprint($desired);
    }

    /**
     * A key-ORDER-insensitive digest of one account config.
     *
     * Objects compare BY CLASS, not by state: the daemon memoises
     * session_ref → credentials for the life of the run
     * (`$this->resolvedCredentials`), so an unchanged ref hands back the same
     * SessionData instance every rotation. Comparing object state would rebuild
     * every worker every rotation for a row that did not change — a worse bug
     * than the one being fixed. A rotated credential arrives as a changed
     * `session_ref` (or a changed seed field), which IS a scalar and IS compared.
     *
     * @param  array<string, mixed>  $config
     */
    private static function configFingerprint(array $config): string
    {
        return hash('sha256', (string) json_encode(self::normalise($config)));
    }

    /**
     * @return array<string, mixed>|string
     */
    private static function normalise(mixed $value): mixed
    {
        if (is_object($value)) {
            return 'object:'.$value::class;
        }

        if (! is_array($value)) {
            return $value;
        }

        $normalised = [];
        foreach ($value as $key => $item) {
            $normalised[(string) $key] = self::normalise($item);
        }

        ksort($normalised);

        return $normalised;
    }

    /**
     * Ensure the Redis stream consumer group exists (idempotent).
     */
    private function ensureConsumerGroup(): void
    {
        try {
            Redis::connection()->command('xgroup', ['CREATE', StreamSchema::STREAM, StreamSchema::GROUP, '0', 'MKSTREAM']);
            $this->components->info('Consumer group "'.StreamSchema::GROUP.'" ready.');
        } catch (Throwable $e) {
            // Group already exists — that's fine
            if (! str_contains($e->getMessage(), 'BUSYGROUP')) {
                $this->components->warn('Consumer group setup: '.$e->getMessage());
            }
        }
    }

    /**
     * @deprecated Superseded by the drain-on-rotation path, which calls
     *             IngestConsumer::ensureGroup() directly. The invariant now
     *             lives in exactly one place; this stays only so the existing
     *             boot-time call site keeps working.
     */ /**
     * Standalone ingest consumer loop (for separate-process deployments).
     */
    private function runIngestLoop(bool $once): int
    {
        $adapter = new LaravelRedisAdapter(Redis::connection());
        $client = app(Teleclient::class);
        $mirror = app()->bound(self::MIRROR_INGESTER_KEY) ? app(self::MIRROR_INGESTER_KEY) : null;
        $routed = $this->handlerSink();
        // THE ROUTED BRANCH DELIVERS (2026-09-26 audit fix, BREAK 2). The
        // daemon passed `onRouted: null`, so a host that configured a bus
        // route (tg:bus:routes) had its entries re-published to the target
        // stream and then dropped: they never reached the handler pipeline,
        // so no declarative app could ever answer a routed update. With the
        // sink wired, a routed entry fans into the SAME pipeline as an
        // un-routed one — one payload shape, one pipeline.
        //
        // A host with no app framework booted (or a container that cannot
        // build the dispatcher) still ingests: null means "no pipeline
        // here", never a fatal.
        $consumer = new IngestConsumer(
            $adapter,
            $client,
            null,
            $routed === null
                ? null
                : static fn (array $update, int $accountId): bool => $routed->handle($update, (string) $accountId),
            $mirror,
        );

        if ($once) {
            $result = $consumer->consumeOnce();
            $this->line(sprintf('Ingested %d entries (%d forwarded).', $result['processed'], $result['forwarded']));

            return self::SUCCESS;
        }

        $this->components->info('Starting ingest consumer loop (Ctrl+C to stop)...');

        $iterations = 0;
        while (! $this->shouldStop) {
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

    /**
     * The handler-pipeline sink the routed bus branch fans into (BREAK 2
     * fix), or null when this process has no pipeline to fan into. Built
     * from the container's UpdateDispatcher, so it carries the SAME
     * registry, onion, echo elimination and dedup as every other intake
     * row. Never throws: a host that has not booted the app framework
     * simply has no sink.
     */
    private function handlerSink(): ?HandlerSink
    {
        try {
            return new HandlerSink(app(UpdateDispatcher::class));
        } catch (Throwable) {
            return null;
        }
    }

    private function installSignalHandlers(): void
    {
        if (! function_exists('pcntl_signal') || ! function_exists('pcntl_async_signals')) {
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
