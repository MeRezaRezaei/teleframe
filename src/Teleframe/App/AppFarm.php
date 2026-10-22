<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\App;

use MeRezaRezaei\Teleframe\Bus\StreamSchema;
use MeRezaRezaei\Teleframe\Laravel\Services\TeleframeClient;
use MeRezaRezaei\Teleframe\Store\Schedules\NullSchedulePublisher;
use MeRezaRezaei\Teleframe\Store\Schedules\SchedulePublisher;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Throwable;

/**
 * AppFarm (Cycle 12 wave 2) — runs MULTIPLE instances of the same declarative
 * app definition, one per account/config/credentials set. A single definition
 * is shared (first-exact-match in AppRegistry); the farm seats one AppRuntime
 * per account, each with its own transport resolver (mode + accountId → scope
 * through TeleframeClient::userFromVault/botFromVault or a host-supplied
 * factory), so the same app answers and runs scheduled calls against every
 * account it is farmed across without duplicating the definition.
 *
 * Hot-reload: the farm re-reads the shared registry on reseat() and re-seats
 * runtimes when a definition's digest changes (triggers/calls/mode added or
 * removed); a removed definition is reported, not silently dropped. A reload
 * payload on tg:bus:reload with kind 'apps' is consumed by consumeReloadPayload()
 * / armReloadWatch() and announces the reseat; the per-rotation re-read stays
 * the real path (the pub/sub half is best-effort, mirroring the daemon).
 *
 * Plain-PHP constructible, zero container/facade calls inside — standalone-smoke
 * discipline. Credentials are resolved at call time through the transport
 * factory; the farm never carries a session string.
 */
final class AppFarm
{
    public const RELOAD_KIND_APPS = 'apps';

    /** @var array<string, AppRuntime> accountId-keyed runtimes ("" = default) */
    private array $runtimes = [];

    /** @var array<string, string> app name => digest of last-seated definition */
    private array $seats = [];

    /** @var array<string, true> account ids already configured */
    private array $accounts = [];

    /** @var callable|null */
    private mixed $transportFactory;

    private readonly SchedulePublisher $schedules;

    private bool $reloadRequested = false;

    /**
     * @param  AppRegistry  $registry  shared definition table
     * @param  mixed  $accountsOrTransport  either a list of account ids/configs OR a transport factory callable(AppMode, accountId): mixed
     * @param  callable|null  $transportFactory  host vault/DC resolver; wins over a callable passed as $accountsOrTransport's sibling
     */
    public function __construct(
        private readonly AppRegistry $registry,
        mixed $accountsOrTransport = [],
        ?callable $transportFactory = null,
        ?SchedulePublisher $schedules = null,
        private readonly ?LoggerInterface $logger = null,
        private readonly ?CacheInterface $sends = null,
        private readonly ?\Closure $now = null,
    ) {
        if (is_callable($accountsOrTransport) && ! is_array($accountsOrTransport)) {
            $this->transportFactory = $accountsOrTransport;
            $accounts = [];
        } else {
            $this->transportFactory = $transportFactory;
            $accounts = is_array($accountsOrTransport) ? $accountsOrTransport : [];
        }

        $this->schedules = $schedules ?? new NullSchedulePublisher;

        foreach ($accounts as $account) {
            if (is_array($account)) {
                $id = (string) ($account['account_id'] ?? $account['id'] ?? $account['accountId'] ?? '');
                if ($id === '') {
                    $id = (string) (count($this->accounts) + 1);
                }
                $this->accounts[$id] = true;
                if (isset($account['transport']) && is_callable($account['transport'])) {
                    // per-account transport factory override stored under a sibling map
                    $this->accountTransports[$id] = $account['transport'];
                }
            } else {
                $id = (string) $account;
                $this->accounts[$id] = true;
            }
        }
    }

    /** @var array<string, callable> per-account transport override */
    private array $accountTransports = [];

    /** The shared definition table this farm reads. */
    public function registry(): AppRegistry
    {
        return $this->registry;
    }

    /** Configured account ids ("" never appears; default account is implicit). */
    public function accounts(): array
    {
        return array_keys($this->accounts);
    }

    /** Add an account to the farm (id or config row). */
    public function addAccount(mixed $account, ?callable $transport = null): self
    {
        if (is_array($account)) {
            $id = (string) ($account['account_id'] ?? $account['id'] ?? $account['accountId'] ?? '');
            if ($id === '') {
                $id = (string) (count($this->accounts) + 1);
            }
            $this->accounts[$id] = true;
            if ($transport !== null || (isset($account['transport']) && is_callable($account['transport']))) {
                $this->accountTransports[$id] = $transport ?? $account['transport'];
            }
        } else {
            $id = (string) $account;
            $this->accounts[$id] = true;
            if ($transport !== null) {
                $this->accountTransports[$id] = $transport;
            }
        }

        return $this;
    }

    /** Remove an account from the farm (its runtime is dropped). */
    public function removeAccount(mixed $accountId): self
    {
        $id = (string) $accountId;
        unset($this->accounts[$id], $this->accountTransports[$id], $this->runtimes[$id]);

        return $this;
    }

    /**
     * The AppRuntime for one (app, account) pair. Created lazily on first
     * use and cached; the same definition is shared, only the transport
     * (credentials) differ. A null account id resolves to the default
     * (single-account) runtime.
     */
    public function for(string $name, mixed $accountId = null): AppRuntime
    {
        return $this->runtime($name, $accountId);
    }

    /** Alias: runtime(...) */
    public function runtime(string $name, mixed $accountId = null): AppRuntime
    {
        $key = $accountId === null ? '' : (string) $accountId;

        if (! isset($this->runtimes[$key])) {
            $this->runtimes[$key] = $this->makeRuntime($key === '' ? null : $key);
        }

        return $this->runtimes[$key];
    }

    /** Alias: get(...) */
    public function get(string $name, mixed $accountId = null): AppRuntime
    {
        return $this->for($name, $accountId);
    }

    /** Alias: instance(...) */
    public function instance(string $name, mixed $accountId = null): AppRuntime
    {
        return $this->for($name, $accountId);
    }

    /** Alias: resolve(...) — mirrors AppRegistry::resolve posture for the farm. */
    public function resolve(string $name, mixed $accountId = null): AppRuntime
    {
        return $this->for($name, $accountId);
    }

    /** Alias: runtimeFor(...) */
    public function runtimeFor(string $name, mixed $accountId = null): AppRuntime
    {
        return $this->for($name, $accountId);
    }

    /**
     * All runtimes currently seated, keyed by account id ("" = default).
     *
     * @return array<string, AppRuntime>
     */
    public function runtimes(?string $name = null): array
    {
        // name is accepted for symmetry but the farm is per-account; definitions
        // are shared in the registry, not per-runtime.
        return $this->runtimes;
    }

    /** Alias: instances(...) */
    public function instances(?string $name = null): array
    {
        return $this->runtimes($name);
    }

    /** Alias: all(...) */
    public function all(?string $name = null): array
    {
        return $this->runtimes($name);
    }

    /** Alias: make(...) */
    public function make(string $name, mixed $accountId = null): AppRuntime
    {
        return $this->for($name, $accountId);
    }

    /** Alias: build(...) */
    public function build(string $name, mixed $accountId = null): AppRuntime
    {
        return $this->for($name, $accountId);
    }

    /** Alias: create(...) */
    public function create(string $name, mixed $accountId = null): AppRuntime
    {
        return $this->for($name, $accountId);
    }

    /** Alias: seat(...) — returns the runtime for the account (creates lazily). */
    public function seat(mixed $first, mixed $second = null): AppRuntime
    {
        [$name, $account] = $this->splitNameAccount($first, $second);

        return $this->for($name ?? '', $account);
    }

    /** Alias: farm(...) — array of runtimes for the given accounts (or all). */
    public function farm(mixed $name = null, mixed $accounts = null): mixed
    {
        if (is_array($accounts)) {
            $out = [];
            foreach ($accounts as $account) {
                $out[(string) $account] = $this->for(is_string($name) ? $name : '', $account);
            }

            return $out;
        }

        if ($name === null) {
            return $this->runtimes();
        }

        // single account passed as second arg
        if ($accounts !== null) {
            return $this->for(is_string($name) ? $name : '', $accounts);
        }

        return $this->for(is_string($name) ? $name : '', null);
    }

    /** Alias: forAccount(...) — flexible argument order. */
    public function forAccount(mixed $first, mixed $second = null): AppRuntime
    {
        [$name, $account] = $this->splitNameAccount($first, $second);

        return $this->for($name ?? '', $account);
    }

    /** Alias: account(...) */
    public function account(mixed $accountId): AppRuntime
    {
        return $this->runtime('', $accountId);
    }

    /**
     * @return array{0: string|null, 1: mixed}
     */
    private function splitNameAccount(mixed $first, mixed $second): array
    {
        if (is_string($first) && ($second === null || is_int($second) || (is_string($second) && ctype_digit($second)))) {
            return [$first, $second];
        }

        if (is_string($second)) {
            return [$second, $first];
        }

        return [null, $first];
    }

    /**
     * Hot-reload: re-read the shared registry and re-seat runtimes when a
     * definition's digest changes. Returns the list of app names that changed
     * (added or removed) since the last reseat.
     *
     * @return list<string>
     */
    public function reseat(): array
    {
        $changed = [];
        $desired = $this->registry->all();

        foreach ($desired as $name => $definition) {
            $digest = $this->digest($definition);
            if (! isset($this->seats[$name]) || $this->seats[$name] !== $digest) {
                $this->seats[$name] = $digest;
                $changed[] = (string) $name;

                foreach ($this->runtimes as $runtime) {
                    try {
                        $runtime->responder()->install($definition);
                    } catch (Throwable $e) {
                        $this->logger?->warning('telegram.app.farm.reseat.install-failed', [
                            'app' => $name,
                            'exception' => $e,
                        ]);
                    }
                }
            }
        }

        foreach (array_keys($this->seats) as $name) {
            if (isset($desired[$name])) {
                continue;
            }

            unset($this->seats[$name]);
            $changed[] = (string) $name;
        }

        return $changed;
    }

    /** Alias: reseatApps() */
    public function reseatApps(): array
    {
        return $this->reseat();
    }

    /** Alias: reseatDefinitions() */
    public function reseatDefinitions(): array
    {
        return $this->reseat();
    }

    /**
     * Consume a reload payload from tg:bus:reload. Recognises kind 'apps'
     * (and 'app') and re-seats; an accounts payload is ignored (the daemon
     * owns account re-seating). Never throws.
     */
    public function consumeReloadPayload(string $payload): void
    {
        $this->noteReloadPayload($payload);
    }

    /** Alias: noteReloadPayload(...) */
    public function noteReloadPayload(string $payload): void
    {
        try {
            $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return;
        }

        if (! is_array($decoded)) {
            return;
        }

        $kind = (string) ($decoded['kind'] ?? '');
        if ($kind === self::RELOAD_KIND_APPS || $kind === 'app') {
            $this->reloadRequested = true;
            $this->reseat();
        }
    }

    /** Alias: reload(...) */
    public function reload(?string $payload = null): void
    {
        if ($payload !== null) {
            $this->consumeReloadPayload($payload);
        } else {
            $this->reloadRequested = true;
            $this->reseat();
        }
    }

    /** Whether a reload has been requested since the last reseat. */
    public function reloadRequested(): bool
    {
        return $this->reloadRequested;
    }

    /**
     * Best-effort arm a reload watcher on a bus redis connection, mirroring
     * the daemon. Tries psubscribe() first, then subscribe(); a refusal is
     * swallowed (the per-rotation re-read stays the real path).
     */
    public function armReloadWatch(mixed $redis = null): void
    {
        if ($redis === null) {
            return;
        }

        $cb = function (string $channel, string $payload): void {
            $this->noteReloadPayload($payload);
        };

        if (method_exists($redis, 'psubscribe')) {
            try {
                $redis->psubscribe([StreamSchema::RELOAD_CHANNEL], $cb);

                return;
            } catch (Throwable) {
                // fall through
            }
        }

        try {
            $redis->subscribe(StreamSchema::RELOAD_CHANNEL, $cb);
        } catch (Throwable) {
            // best-effort
        }
    }

    /** Alias: watch(...) */
    public function watch(mixed $redis = null): void
    {
        $this->armReloadWatch($redis);
    }

    /** Digest of a definition (mode + triggers + calls). */
    private function digest(AppDefinition $definition): string
    {
        return sha1(serialize([
            $definition->name,
            $definition->mode->value,
            count($definition->triggers()),
            count($definition->calls()),
            array_map(static fn (AppTrigger $t): array => [$t->match, $t->priority, $t->onOwn], $definition->triggers()),
            array_map(static fn (AppCall $c): array => [$c->name, $c->intervalSeconds, $c->method, $c->params], $definition->calls()),
        ]));
    }

    private function makeRuntime(mixed $accountId): AppRuntime
    {
        $factory = $this->accountTransports[(string) ($accountId ?? '')] ?? $this->transportFactory;
        $accountIdValue = $accountId;

        $transport = function (AppMode $mode) use ($factory, $accountIdValue): mixed {
            if ($factory !== null) {
                try {
                    return $factory($mode, $accountIdValue);
                } catch (Throwable $e) {
                    // fall through to default? No — rethrow as AppException via resolver? AppRuntime wraps? Transport callable invoked inside AppTransportResolver?
                }
            }

            $client = new TeleframeClient;

            return match ($mode) {
                AppMode::Bot => $client->bot(),
                AppMode::User => $client->user(),
            };
        };

        // Wrap so failures surface as AppException (AppTransportResolver does this)
        $wrapped = function (AppMode $mode) use ($transport, $accountIdValue): mixed {
            try {
                return $transport($mode);
            } catch (Throwable $e) {
                throw new AppException(
                    'app farm transport: cannot resolve '.$mode->transportName().' scope for account '.(string) ($accountIdValue ?? 'default')
                    .' (unconfigured credentials or a failing resolver): '.$e->getMessage(),
                    $e,
                );
            }
        };

        return new AppRuntime(
            registry: $this->registry,
            schedules: $this->schedules,
            transport: $wrapped,
            logger: $this->logger,
            now: $this->now,
            sends: $this->sends,
        );
    }
}
