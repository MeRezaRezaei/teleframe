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
use MeRezaRezaei\Teleframe\App\AppRuntime;
use MeRezaRezaei\Teleframe\App\AppSupport;
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
 * APPS RE-SEAT ON THE SAME ROTATION (F10). Accounts re-seated; APP definitions
 * and their scheduled calls did not, so an app's config change needed a
 * RESTART — which made the operator's capability 6 (a bot/user app framework
 * whose apps are registered at runtime) half a feature next to capability 1.
 * reseatApps() now runs on the same rotation: it re-reads
 * teleframe.app.apps, installs any app that is not yet on the shared handler
 * table, and re-arms the composed scheduled-call payload when — and only
 * when — it changed. A {kind:'apps'} payload on the same reload channel is what
 * ANNOUNCES the reseat; the per-rotation re-read is still the real path, for
 * the same reason it is for accounts. TWO STATED LIMITS, both reported out
 * loud rather than hidden: the shared handler table is APPEND-ONLY (a removed
 * app's triggers need a restart to un-install them), and a changed trigger
 * BODY is invisible to the change-detector (a Closure is compared as
 * 'closure', never executed).
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
    /**
     * The `kind` that marks a reload payload as an APP-definition change, as
     * opposed to `AccountLifecycleObserver::RELOAD_KIND` ('accounts'). A frozen
     * wire value on a frozen channel (`tg:bus:reload`), paired with the accounts
     * kind: same channel, discriminated by `kind`, never by omission.
     */
    public const RELOAD_KIND_APPS = 'apps';

    /** Container seam: callable(array<string, mixed>, int): void — the NF5 mirror ingester (verbatim daily loop). */
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
     * F10 — hot reload covers ACCOUNTS but not APP definitions.
     *
     * `AppServiceProvider::boot()` installs every seeded app's triggers onto the
     * shared HandlerRegistry ONCE, and the app registry itself is a lazily
     * resolved singleton built from `teleframe.app.apps`. So an app added or
     * changed at runtime never reached this long-lived process: the provider
     * boot had already run, the singleton was already resolved, and the shared
     * handler table — read live at dispatch — carried only the apps that
     * existed at boot. The operator's capability 6 (a bot/user app framework
     * whose apps are registered at runtime) therefore needed a RESTART for a
     * config change, while capability 1 (accounts) had a real no-restart path.
     *
     * This flag is the apps half of the reload signal. It is SEPARATE from
     * `$reloadRequested` on purpose: an accounts nudge must not re-arm
     * schedules, and an apps nudge must not re-seat workers — the two registries
     * are independent and each reseat is announced in its own terms.
     *
     * NOTE THE GAP IS THE PAYLOAD, NOT ONLY THE WATCHER (F10): the watcher
     * itself works (it is armed and, on the in-memory double, delivered). What
     * was missing is that `{kind:'accounts', account_id, change}` is the only
     * shape `noteReloadPayload()` recognised, so ANY app-definition signal was
     * dropped on the floor — silently, because an unrecognised payload returns
     * without a word. The signal this daemon now acts on for apps is
     * `{kind:'apps', app?: string, change?: string}`:
     *
     *   - `kind:'apps'` is REQUIRED and is the discriminator. Without it the
     *     payload is an accounts payload by omission, and guessing would
     *     re-seat accounts on an app write.
     *   - `app` names WHICH app changed, when the publisher knows it — the
     *     daemon only reports the name it acted on, never an unverified one.
     *   - `change` is free-form provenance ('added' | 'changed' | 'removed').
     *     The daemon does NOT branch on it: the per-rotation config re-read is
     *     the authority, exactly as it is for accounts, so a missing or
     *     misspelled `change` cannot make the reseat lie. It is carried for the
     *     operator's log line only.
     *
     * No publisher for `{kind:'apps'}` exists yet — apps are config, and config
     * has no Eloquent observer to hang one on. That is why the per-rotation
     * re-read below is the real path and this flag only makes the reseat
     * announced: a host that mutates `teleframe.app.apps` in-process gets the
     * reseat on the next rotation with no pub/sub at all.
     */
    private bool $appsReloadRequested = false;

    /**
     * app name => the digest of the definition this process last acted on
     * (triggers + calls + mode). In-process memory, never persisted: it is a
     * change-detector, not a source of truth, and a fresh daemon re-seats from
     * config on its first rotation with no history.
     *
     * @var array<string, string>
     */
    private array $appSeats = [];

    /**
     * The composed installSpec payload (schedule id => spec) this process last
     * handed to the schedule seam. Change-detector for the SCHEDULED-CALL half
     * only: re-arming dials Temporal, so it must happen when the call set
     * changes and NOT on every rotation.
     *
     * @var array<string, array<string, mixed>>
     */
    private array $seatedAppCalls = [];

    /**
     * Config-declared app names already REPORTED as missing from the live
     * registry. One warning per name, not one per rotation: a gap that is
     * reported every tick trains the operator to scroll past it.
     *
     * @var array<string, true>
     */
    private array $reportedAppConfigGaps = [];

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
        // loopback subscribe semantic (in-memory double), 'accounts' and
        // 'apps' reload payloads nudge the next rotation to re-read their
        // registries. A real pub/sub loop needs its own process, so this is
        // never fatal — the per-rotation re-reads are the reliable no-restart
        // path.
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
     *
     * NO MIRROR INGESTER — D-040, 2026-10-13. This used to resolve
     * `self::MIRROR_INGESTER_KEY` and pass it, and because
     * `TeleframeServiceProvider` binds that key unconditionally in every
     * Laravel host, the daemon's bus drain therefore ALWAYS took
     * `IngestConsumer`'s mirror branch and the `Teleclient::ingest()` branch
     * below it was unreachable in a default install. The mirror branch writes
     * no `tf_updates` row at all — it refuses to invent a `(seq, position)`
     * key for a seq-less payload — so the daemon produced no committed row
     * carrying `(account_id, seq, position)`, and that row is what the update
     * bus has to name.
     *
     * The binding stays: it still serves the backfill and RPC-response paths
     * (`MirrorIngesterFactory`). Only the BUS PATH is retired, and
     * `IngestConsumer` still drains pre-migration v1 entries through the seam
     * for any host that passes one.
     */
    private function buildBusDrain(): IngestConsumer
    {
        $adapter = new LaravelRedisAdapter(Redis::connection());
        $routed = $this->handlerSink();

        return new IngestConsumer(
            $adapter,
            null,
            $routed === null
                ? null
                : static fn (array $update, int $accountId): bool => $routed->handle($update, (string) $accountId),
            mirrorIngester: null,
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
            $this->reseatApps();
            $this->drainBusOnce();
        });

        return $daemon;
    }

    /**
     * Best-effort reload watcher: {kind:'accounts'} and {kind:'apps'} payloads
     * on the reload channel nudge the next rotation. `kind` is the
     * discriminator and it is REQUIRED — see noteReloadPayload().
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
     * The per-rotation re-reads in reseatAccounts() and reseatApps() remain
     * the real no-restart path; the warning says so out loud.
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
     * Consume a reload payload (never throws). Two kinds count, and they are
     * NOT interchangeable:
     *
     *  - `{kind:'accounts', ...}` → re-seat workers from tf_accounts
     *  - `{kind:'apps', ...}`    → re-seat APP DEFINITIONS + scheduled calls
     *
     * Before this, only the accounts shape was recognised, so an app-definition
     * signal was silently dropped — the F10 half of the feature. Anything else
     * still returns without a word: an unrecognised payload is somebody else's
     * channel traffic, not this daemon's business.
     */
    private function noteReloadPayload(string $payload): void
    {
        try {
            $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return;
        }

        if (! is_array($decoded)) {
            return;
        }

        match ($decoded['kind'] ?? null) {
            AccountLifecycleObserver::RELOAD_KIND => $this->reloadRequested = true,
            self::RELOAD_KIND_APPS => $this->appsReloadRequested = true,
            default => null,
        };
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
     * The APP half of the hot re-seat (F10). Accounts re-seated every rotation;
     * app definitions and their scheduled calls did not, so an app's config
     * change needed a restart — which made the operator's capability 6 (apps
     * registered at runtime) half a feature: capability 1 had a no-restart path
     * and capability 6 did not.
     *
     * WHAT IT SEATS, and why each half needs its own mechanism:
     *
     *  1. TRIGGERS. An app's triggers live on the SHARED, APPEND-ONLY
     *     HandlerRegistry (a hard rule: the shipped registry has no removal
     *     API). `AppResponder::install()` is idempotent per app name, so
     *     installing an app that is already installed is a safe no-op — but it
     *     means install() alone can only ever ADD reachability, never take it
     *     away. An app that was REMOVED from config therefore keeps its live
     *     triggers for the life of this process. That is a deliberate,
     *     stated limit, not an oversight: this daemon will not rewrite the
     *     shared table. It is reported loudly instead of being hidden.
     *
     *  2. SCHEDULED CALLS. `AppRuntime::installCalls()` composes each app's
     *     calls into SchedulePublisher payloads keyed by
     *     `app-call-{app}-{call}-{digest}` — a DETERMINISTIC function of the
     *     (app, call) pair. So re-installing refreshes the same schedule id in
     *     place rather than stacking a second one, and a changed interval
     *     takes effect on the next rotation with no restart. This is why the
     *     scheduled calls DO hot-reload where the triggers only add.
     *
     * WHY THIS RUNS EVERY ROTATION rather than on the signal alone: the pub/sub
     * half is best-effort and, with the production adapter, refused outright (see
     * armReloadWatch()). The accounts reseat is the real path for exactly this
     * reason, and the apps reseat follows the same rule — the registry is
     * re-read, so an app defined at runtime is covered with no signal at all.
     * The signal only makes the reseat ANNOUNCED.
     *
     * THE SOURCE OF TRUTH IS THE RESOLVED REGISTRY, NOT THE CONFIG ROWS — and
     * that difference is reported, never papered over. `teleclient.app.registry`
     * is a lazily resolved singleton seeded from `teleframe.app.apps` at FIRST
     * resolve, so a config row added after that resolve is in config and NOT in
     * the registry. This daemon will not forgetInstance() that singleton to force
     * a re-seed: doing so would DISCARD every app a host defined at runtime,
     * which is the exact use this reseat exists to serve. So a config row with
     * no registry entry is reported as a gap, once, by name.
     *
     * THE FIRST ROTATION IS NOT A CHANGE. The provider's boot() already
     * installed the seeded apps, so the very first pass would otherwise print
     * "installed app X" for apps the operator never touched. The digest of what
     * this process has acted on is recorded on the first pass WITHOUT
     * announcing, so only a genuine change is ever reported.
     *
     * Unconfigured hosts are a no-op: `AppSupport::SERVICE_KEY` is not bound, so
     * the registry is never even resolved — the same cost posture
     * `AppServiceProvider::installTriggers()` takes.
     */
    private function reseatApps(): void
    {
        if ($this->appsReloadRequested) {
            $this->appsReloadRequested = false;
            $this->components->info('Reload signal received — re-reading the app definitions.');
        }

        if (! app()->bound(AppSupport::SERVICE_KEY)) {
            return; // no app framework in this process — nothing to reseat
        }

        try {
            $runtime = app(AppSupport::SERVICE_KEY);
        } catch (Throwable $e) {
            $this->components->warn(sprintf(
                'Could not resolve %s to re-seat app definitions: %s',
                AppSupport::SERVICE_KEY,
                $e->getMessage(),
            ));

            return;
        }

        if (! $runtime instanceof AppRuntime) {
            return;
        }

        $desired = $this->appDigests($runtime);
        $firstPass = $this->appSeats === [];

        $this->reportAppConfigGaps($desired);

        foreach ($desired as $name => $digest) {
            $seated = $this->appSeats[$name] ?? null;

            if ($seated === $digest) {
                continue;
            }

            // Added (null) or CHANGED (a different digest under the same name).
            // Both install: install() is idempotent per name, so a changed app
            // re-registers its triggers as a no-op and the DEFINITION CHANGE
            // ITSELF is reported rather than silently half-applied.
            $installed = $this->installApp($runtime, $name);

            $this->appSeats[$name] = $digest;

            if ($firstPass) {
                continue; // the provider's boot() already installed these
            }

            $this->components->info(sprintf(
                'Re-seated app [%s] — %d trigger(s) installed (app definitions are re-read every rotation).',
                $name,
                $installed,
            ));
        }

        // A REMOVED app cannot be un-installed: the shared handler table is
        // append-only by hard rule. Say so out loud instead of letting the
        // operator believe the removal took.
        foreach (array_keys($this->appSeats) as $name) {
            if (isset($desired[$name])) {
                continue;
            }

            unset($this->appSeats[$name]);

            if ($firstPass) {
                continue;
            }

            $this->components->warn(sprintf(
                'App [%s] is gone from teleframe.app.apps, but its triggers stay installed: the shared handler '
                .'table is append-only and has no removal API, so a restart is the only way to un-install them.',
                $name,
            ));
        }

        $this->reseatAppCalls($runtime, $firstPass);
    }

    /**
     * Report a config-declared app that the LIVE registry does not hold, once
     * per name.
     *
     * This is the honest form of F10's second half. The registry is a lazily
     * resolved singleton seeded at first resolve, so a `teleframe.app.apps` row
     * written after that resolve is invisible to the daemon — the app is in
     * config, it has no triggers on the shared table, it has no schedule armed,
     * and nothing anywhere says so. The daemon cannot fix that here (re-seeding
     * would discard every runtime-defined app), so it NAMES the gap instead of
     * re-reading config and printing a healthy-looking nothing.
     *
     * @param  array<string, string>  $seated  app name => digest, from the live registry
     */
    private function reportAppConfigGaps(array $seated): void
    {
        foreach ((array) config('teleframe.app.apps', []) as $row) {
            $name = is_array($row) ? (string) ($row['name'] ?? '') : '';

            if ($name === '' || isset($seated[$name]) || isset($this->reportedAppConfigGaps[$name])) {
                continue;
            }

            $this->reportedAppConfigGaps[$name] = true;

            $this->components->warn(sprintf(
                'App [%s] is declared in teleframe.app.apps but the live app registry does not hold it — its '
                .'triggers and scheduled calls are NOT installed in this process. The registry is seeded at first '
                .'resolve; a host that writes config after that needs the app defined through the registry (or a '
                .'restart) for it to be reachable here.',
                $name,
            ));
        }
    }

    /**
     * Install one app's triggers onto the shared handler table.
     *
     * Never throws: a shadowed trigger (AppResponder's refuse-loud) or a
     * malformed definition must not take the daemon's rotation down with it —
     * the account reseat warns and continues for the same reason.
     *
     * @return int triggers installed (0 when already installed, or on refusal)
     */
    private function installApp(AppRuntime $runtime, string $name): int
    {
        try {
            $app = $runtime->resolve($name);
        } catch (Throwable $e) {
            $this->components->warn(sprintf('Could not resolve app [%s]: %s', $name, $e->getMessage()));

            return 0;
        }

        try {
            return $runtime->responder()->install($app);
        } catch (Throwable $e) {
            $this->components->warn(sprintf('Could not install app [%s]: %s', $name, $e->getMessage()));

            return 0;
        }
    }

    /**
     * Re-arm every registered app's SCHEDULED CALLS onto the schedule seam.
     *
     * Separate from reseatApps() on purpose: this DIALS the schedule host
     * (Temporal) and must not run on every rotation. It runs only when the
     * composed call set actually changed, which is the event that needs it —
     * AppCallSpec::callId() is a deterministic function of the (app, call) pair,
     * so a re-install refreshes that schedule in place instead of stacking a
     * duplicate, and a CHANGED interval takes effect without a restart.
     *
     * THE FIRST PASS DOES NOT ARM. Arming app schedules is the host's explicit
     * `teleframe:app calls --install` gesture, and it is NOT this daemon's boot
     * behaviour today. Making the daemon arm them at startup would be a
     * behaviour change wearing a hot-reload's clothes — a fresh dial to Temporal
     * in the rotation path of every host that declared a call. The first pass
     * only RECORDS, so the daemon's boot cost and behaviour are unchanged and a
     * later change to the call set re-arms as intended.
     *
     * Never throws: a schedule host that is down or unbound must not take the
     * rotation down. It warns, exactly as the account half does.
     */
    private function reseatAppCalls(AppRuntime $runtime, bool $firstPass): void
    {
        $spec = $runtime->calls();

        if ($spec === $this->seatedAppCalls) {
            return; // unchanged — never re-dial the schedule host for nothing
        }

        $this->seatedAppCalls = $spec;

        if ($firstPass || $spec === []) {
            return; // record only; an empty call set has nothing to arm
        }

        try {
            $status = $runtime->installCalls();
        } catch (Throwable $e) {
            $this->components->warn(sprintf(
                'Could not re-arm %d app scheduled call(s): %s',
                count($spec),
                $e->getMessage(),
            ));

            return;
        }

        $this->components->info(sprintf(
            'Re-armed %d app scheduled call(s) onto the schedule seam.',
            count($status),
        ));

        // The no-work contract AppCommand::calls() already enforces: a driver
        // that skipped every schedule means no job will fire, and saying
        // "re-armed" over that is the same visible no-op this half exists to
        // remove.
        foreach ($status as $id => $state) {
            if (! str_starts_with($state, 'skipped')) {
                continue;
            }

            $this->components->warn(sprintf(
                'App scheduled call [%s] was NOT armed (%s) — no job will fire for it.',
                $id,
                $state,
            ));
        }
    }

    /**
     * A stable digest per registered app: its mode, its triggers (match,
     * priority, on-own and the handler's SHAPE — a Closure is identified as
     * "closure", never serialized) and its calls (name, interval, method and
     * params). Change-detector input only, so it must not embed anything that
     * varies run to run: `spl_object_id` on a handler would make an unchanged
     * config look changed on every rotation.
     *
     * Zero regex — the shape is assembled by type, not by pattern.
     *
     * @return array<string, string> app name => digest
     */
    private function appDigests(AppRuntime $runtime): array
    {
        $digests = [];

        foreach ($runtime->registry()->all() as $app) {
            $shape = ['mode' => $app->mode->value];

            foreach ($app->triggers() as $trigger) {
                $shape['triggers'][] = [
                    $trigger->match,
                    $trigger->priority,
                    $trigger->onOwn ? 1 : 0,
                    $this->handlerShape($trigger->handler),
                ];
            }

            foreach ($app->calls() as $call) {
                $shape['calls'][] = [
                    $call->name,
                    $call->intervalSeconds,
                    $call->method,
                    $call->params,
                ];
            }

            $digests[$app->name] = hash('sha256', (string) json_encode($shape));
        }

        return $digests;
    }

    /**
     * The COMPARABLE shape of one handler reference — a PSR-11 string is itself,
     * a [Class, method] pair is its two strings, a Closure is the literal
     * 'closure'. Deliberately coarse: a changed closure BODY is invisible to
     * the digest (it cannot be compared without executing it), which is a
     * stated limit, not a silent one — the next section says so.
     */
    private function handlerShape(string|array|\Closure $handler): string
    {
        if ($handler instanceof \Closure) {
            return 'closure';
        }

        if (is_array($handler)) {
            return 'callable:'.implode('::', array_map(
                static fn (mixed $part): string => is_scalar($part) ? (string) $part : get_debug_type($part),
                $handler,
            ));
        }

        return 'service:'.$handler;
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
        // NO MIRROR INGESTER — D-040, and for the same reason as
        // buildBusDrain() above: this is the BUS PATH, and the bus is where
        // `UpdateIngestor` has to be the writer, because its committed row is
        // what a bus entry names. See that method for the full argument.
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
            null,
            $routed === null
                ? null
                : static fn (array $update, int $accountId): bool => $routed->handle($update, (string) $accountId),
            mirrorIngester: null,
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
