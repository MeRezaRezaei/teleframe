<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Daemon;

use Closure;
use InvalidArgumentException;
use MeRezaRezaei\Teleframe\Core\Contracts\UpdateSinkInterface;
use MeRezaRezaei\Teleframe\Core\Exceptions\DcMigrationException;
use MeRezaRezaei\Teleframe\Core\Exceptions\Rpc\FloodWaitException;
use MeRezaRezaei\Teleframe\Core\MTProto\SessionData;
use MeRezaRezaei\Teleframe\Laravel\Services\TeleframeClient;
use MeRezaRezaei\Teleframe\Laravel\Services\UpdatePollerService;
use MeRezaRezaei\Teleframe\Core\Services\UserAccountScope;
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
     * @param array<string, mixed> $accountConfig {account_id: positive int, session_string?: string, dc?: int, api_id?: int, api_hash?: string}
     * @param (callable(array<string, mixed>, int|null): UserAccountScope)|null $scopeFactory
     *        transport seam: tests inject a fake; the default builds a live teleframe scope
     *        (SessionData::importString + optional DC override, credentials from the config or TELEGRAM_* env).
     * @param array{pts?: int, date?: int, qts?: int, seq?: int}|null $resumeState difference cursor to resume from (config-stored)
     * @param (callable(int): int)|null $sleep injectable sleeper for the FloodWait backoff
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
     * @param (callable(): bool)|null $shouldStop
     */
    public function run(UpdateSinkInterface $sink, ?callable $shouldStop = null): void
    {
        $shouldStop = $shouldStop === null ? static fn (): bool => false : $shouldStop(...);
        $state = $this->resumeState;
        $dc = isset($this->accountConfig['dc']) ? (int) $this->accountConfig['dc'] : null;
        $unexpected = 0;

        while (! $shouldStop()) {
            $poller = new AccountPoller($sink);

            if ($state !== null) {
                $poller->setSequenceState($state);
            }

            try {
                $poller->pollUser($this->stopAware($this->buildScope($dc), $poller, $shouldStop));

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
                    $dc = $e->getDcId(); // rebuild the scope at the new DC, resume the window
                    continue;
                }

                if ($e instanceof FloodWaitException) {
                    $this->interruptibleSleep(UpdatePollerService::secondsToWait($e), $shouldStop);

                    continue;
                }

                if (++$unexpected >= self::MAX_UNEXPECTED_FAILURES) {
                    throw $e;
                }
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
        return new class ($inner, $poller, $shouldStop) extends UserAccountScope {
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

                    throw new RuntimeException('shutdown requested before ' . $method);
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
     * @param Closure(): bool $shouldStop
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
     * @param array{pts?: int, date?: int, qts?: int, seq?: int}|null $before
     * @param array{pts: int, date: int, qts: int, seq: int}|null $after
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
        $sessionString = (string) ($config['session_string'] ?? '');

        if ($sessionString === '') {
            throw new InvalidArgumentException(
                'account config requires session_string for the live scope factory',
            );
        }

        $session = SessionData::importString($sessionString);

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
