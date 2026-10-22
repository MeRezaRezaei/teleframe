<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Daemon;

use Closure;
use InvalidArgumentException;
use MeRezaRezaei\Teleframe\Bus\RedisStreamSink;
use MeRezaRezaei\Teleframe\Core\Contracts\UpdateSinkInterface;
use MeRezaRezaei\Teleframe\Core\Exceptions\DcMigrationException;
use MeRezaRezaei\Teleframe\Core\Exceptions\Rpc\FloodWaitException;
use MeRezaRezaei\Teleframe\Core\MTProto\SessionData;
use MeRezaRezaei\Teleframe\Core\Services\UserAccountScope;
use MeRezaRezaei\Teleframe\Laravel\Services\TeleframeClient;
use MeRezaRezaei\Teleframe\Laravel\Services\UpdatePollerService;
use RuntimeException;
use Throwable;

/**
 * One account's supervised poll loop (plan Phase 3, Task 4): builds a
 * teleframe user scope from the account config, drives
 * UpdatePollerService::pollUser() against the given sink and supervises
 * the failure policy the stock poller keeps internal:
 *
 *  - FloodWait  → interruptible sleep (clamped by the poller's
 *                 secondsToWait), chunked so $shouldStop lands within a
 *                 second, then poll again;
 *  - DC migration → rebuild the scope at the DC Telegram moved us to and
 *                 resume the adopted difference window;
 *  - unexpected → retry up to MAX_UNEXPECTED_FAILURES consecutive
 *                 attempts without observable progress, then rethrow so
 *                 the supervisor (Daemon) can isolate the account.
 *
 * The scope (MTProto client + its cached encrypted connection, i.e. the
 * socket fd) is built ONCE and reused across FloodWait and unexpected-error
 * retries — the connection is only rebuilt when the DC changes. The client
 * already evicts a dead connection internally on transport failure, so the
 * retry re-dials only when the socket actually died; steady-state retries
 * keep the same fd instead of churning one per loop iteration.
 *
 * pollUser() returning normally always means "stop() won" — a clean
 * shutdown, never an error. The sequence cursor survives every restart
 * (resume ctor arg in, lastSequenceState() out) so the host app can
 * persist it between daemon runs. No redis here: the sink is injected —
 * bus wiring belongs to the bootstrap (docs/bus.md).
 */
final class AccountWorker
{
    /** Unexpected failures with no progress between before the error escapes to the supervisor. */
    public const MAX_UNEXPECTED_FAILURES = 3;

    private const SLEEP_CHUNK_SECONDS = 1;

    private readonly Closure $scopeFactory;

    private readonly Closure $sleep;

    /** @var array{pts: int, date: int, qts: int, seq: int}|null */
    private ?array $lastState = null;

    /**
     * @param  array<string, mixed>  $accountConfig  {account_id: positive int, session_string?: string, session_data?: SessionData, dc?: int, api_id?: int, api_hash?: string}
     * @param  (callable(array<string, mixed>, int|null): UserAccountScope)|null  $scopeFactory
     *                                                                                           transport seam: tests inject a fake; the default builds a live teleframe scope
     *                                                                                           (a pre-resolved SessionData from the config or SessionData::importString on the
     *                                                                                           session_string, optional DC override, credentials from the config or TELEGRAM_* env).
     * @param  array{pts?: int, date?: int, qts?: int, seq?: int}|null  $resumeState  difference cursor to resume from (config-stored)
     * @param  (callable(int): int)|null  $sleep  injectable sleeper for the FloodWait backoff
     */
    public function __construct(
        private readonly array $accountConfig,
        ?callable $scopeFactory = null,
        private readonly ?array $resumeState = null,
        ?callable $sleep = null,
    ) {
        if ((int) ($this->accountConfig['account_id'] ?? 0) <= 0) {
            throw new InvalidArgumentException('account config requires a positive int account_id');
        }

        $this->scopeFactory = $scopeFactory !== null
            ? Closure::fromCallable($scopeFactory)
            : self::buildLiveScope(...);
        $this->sleep = $sleep !== null ? Closure::fromCallable($sleep) : Closure::fromCallable('sleep');
    }

    public function accountId(): int
    {
        return (int) $this->accountConfig['account_id'];
    }

    /**
     * The most recently observed difference cursor — capture after run()
     * ends (any reason) to persist it as the account's resume state.
     *
     * @return array{pts: int, date: int, qts: int, seq: int}|null
     */
    public function lastSequenceState(): ?array
    {
        return $this->lastState;
    }

    /**
     * Poll until $shouldStop (null = never) or a supervised give-up.
     *
     * @param  (callable(): bool)|null  $shouldStop
     */
    public function run(UpdateSinkInterface $sink, ?callable $shouldStop = null): void
    {
        $shouldStop = $shouldStop === null ? static fn (): bool => false : $shouldStop(...);
        $state = $this->resumeState;
        $dc = isset($this->accountConfig['dc']) ? (int) $this->accountConfig['dc'] : null;
        $unexpected = 0;
        $scope = null;

        while (! $shouldStop()) {
            $poller = new AccountPoller($sink);

            // D-041: the frames the producer mints are stamped with the TENANCY
            // account id, which is this config's `account_id` — the same value
            // DaemonCommand hands to RedisStreamSink's constructor and that the
            // ingest will carry as the row's `account_id`. NOT the Telegram user
            // id the poller can see on its own scope. This is the composition
            // root that holds both the poller and the account config, which is
            // why the injection lives here and not in the poller.
            $poller->setProducerAccountId($this->accountId());

            // D-041, second half: the sink READS the frame this poller minted.
            // Under the S1 inversion the poller mints it, the sink commits the
            // row under it and publishes the committed key — and the sink is
            // constructed by DaemonCommand BEFORE this worker exists, so this
            // is the one place both halves are in reach of each other.
            if ($sink instanceof RedisStreamSink) {
                $sink->withProducerFrameSource($poller);
            }

            if ($state !== null) {
                $poller->setSequenceState($state);
            }

            try {
                // The scope is built once and reused across retries so the
                // socket fd survives FloodWait / unexpected-error backoffs;
                // only a DC migration drops it (the connection is DC-bound).
                $scope ??= $this->buildScope($dc);

                $poller->pollUser($this->stopAware($scope, $poller, $shouldStop));

                // pollUser() only returns when stop() won: clean shutdown.
                $this->lastState = $poller->getSequenceState();

                return;
            } catch (Throwable $e) {
                $fresh = $poller->getSequenceState();

                if (self::progressed($state, $fresh)) {
                    $unexpected = 0; // the account polled fine between the failures
                }
                $state = $fresh ?? $state;
                $this->lastState = $state;

                if ($e instanceof DcMigrationException) {
                    $dc = $e->getDcId(); // connection is DC-bound: drop the cached scope so the next iteration dials at the new DC
                    $scope = null;

                    continue;
                }

                if ($e instanceof FloodWaitException) {
                    // Same scope reused: RPC-level backoff keeps the connection
                    // valid, so the fd is not re-acquired on resume.
                    $this->interruptibleSleep(UpdatePollerService::secondsToWait($e), $shouldStop);

                    continue;
                }

                if (++$unexpected >= self::MAX_UNEXPECTED_FAILURES) {
                    throw $e;
                }
                // Unexpected failure: reuse the scope for the next attempt —
                // a transport-level failure already evicted the dead
                // connection inside Client::call(), so the retry re-dials
                // only if the socket actually died.
            }
        }
    }

    /**
     * Wrap a scope so every outgoing RPC first checks $shouldStop: when a
     * stop is requested the poller is stopped (its loop breaks on the next
     * error-loop decision) and a private control exception aborts the
     * in-flight getDifference — the poller sees running=false and breaks,
     * so the exception never escapes pollUser().
     */
    private function stopAware(UserAccountScope $inner, UpdatePollerService $poller, Closure $shouldStop): UserAccountScope
    {
        return new class($inner, $poller, $shouldStop) extends UserAccountScope
        {
            public function __construct(
                private readonly UserAccountScope $inner,
                private readonly UpdatePollerService $poller,
                private readonly Closure $shouldStop,
            ) {
                parent::__construct($inner->mtproto, $inner->session);
            }

            public function call(string $method, array $params = []): array
            {
                if (($this->shouldStop)()) {
                    $this->poller->stop();

                    throw new RuntimeException('shutdown requested before '.$method);
                }

                $result = $this->inner->call($method, $params);
                $this->session = $this->inner->session; // keep the live session visible to the poller

                return $result;
            }
        };
    }

    private function buildScope(?int $dc): UserAccountScope
    {
        return ($this->scopeFactory)($this->accountConfig, $dc);
    }

    /**
     * Sleep in stop-responsive 1s chunks; the production sleeper is real
     * sleep(), tests inject a recorder.
     *
     * @param  Closure(): bool  $shouldStop
     */
    private function interruptibleSleep(int $seconds, Closure $shouldStop): void
    {
        for ($slept = 0; $slept < $seconds && ! $shouldStop(); $slept += self::SLEEP_CHUNK_SECONDS) {
            ($this->sleep)(self::SLEEP_CHUNK_SECONDS);
        }
    }

    /**
     * Did the cursor move between two failures? (Any of pts/date/qts/seq
     * strictly higher — the account made progress, so a later failure is
     * NOT consecutive to the earlier one.) No baseline = no progress.
     *
     * @param  array{pts?: int, date?: int, qts?: int, seq?: int}|null  $before
     * @param  array{pts: int, date: int, qts: int, seq: int}|null  $after
     */
    private static function progressed(?array $before, ?array $after): bool
    {
        if ($before === null || $after === null) {
            return false;
        }

        foreach (['pts', 'date', 'qts', 'seq'] as $key) {
            if ($after[$key] > (int) ($before[$key] ?? 0)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The production transport: a live teleframe user scope. Credentials
     * come from the account config with TELEGRAM_* env fallback (teleframe
     * config parity); the live wire itself stays behind teleframe's
     * tri-state live flag (config('teleframe.live_mode') / TELEFRAME_LIVE)
     * — nothing here forces the network on. Also the provider's default
     * backfill scope resolver (daemon.accounts registry).
     */
    public static function buildLiveScope(array $config, ?int $dc = null): UserAccountScope
    {
        $session = $config['session_data'] ?? null;

        if (! $session instanceof SessionData) {
            $sessionString = (string) ($config['session_string'] ?? '');

            if ($sessionString === '') {
                throw new InvalidArgumentException(
                    'account config requires session_string or session_data for the live scope factory',
                );
            }

            $session = SessionData::importString($sessionString);
        }

        if ($dc !== null && $dc !== $session->dcId) {
            $session->dcId = $dc; // DcMigration rebuild target
        }

        $apiId = (int) ($config['api_id'] ?? getenv('TELEGRAM_API_ID') ?: 0);
        $apiHash = (string) ($config['api_hash'] ?? getenv('TELEGRAM_API_HASH') ?: '');

        return (new TeleframeClient(defaultApiId: $apiId, defaultApiHash: $apiHash))
            ->user(
                accountId: (int) ($config['account_id'] ?? 0) ?: null,
                session: $session,
                dcId: $dc,
            );
    }
}
