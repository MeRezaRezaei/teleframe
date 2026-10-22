<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use MeRezaRezaei\Teleframe\App\AppRuntime;
use MeRezaRezaei\Teleframe\App\AppSupport;
use MeRezaRezaei\Teleframe\Backup\VaultInterface;
use MeRezaRezaei\Teleframe\Bus\RouteTable;
use MeRezaRezaei\Teleframe\Daemon\Daemon;
use MeRezaRezaei\Teleframe\Handler\HandlerMatcher;
use MeRezaRezaei\Teleframe\Handler\HandlerRegistry;
use MeRezaRezaei\Teleframe\Handler\Middleware\EchoEliminator;
use MeRezaRezaei\Teleframe\Handler\Update;
use MeRezaRezaei\Teleframe\Handler\UpdateDispatcher;
use MeRezaRezaei\Teleframe\Ingest\EntityAggregator;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Laravel\Console\BackupCommand;
use MeRezaRezaei\Teleframe\Laravel\Console\TstoreCommand;
use MeRezaRezaei\Teleframe\Laravel\Models\TfAccount;
use MeRezaRezaei\Teleframe\Laravel\Models\UpdateRoutingRule;
use MeRezaRezaei\Teleframe\Mirror\Models\TfChannel;
use MeRezaRezaei\Teleframe\Mirror\Models\TfChat;
use MeRezaRezaei\Teleframe\Mirror\Models\TfUser;
use MeRezaRezaei\Teleframe\Passport\PassportLoginService;
use MeRezaRezaei\Teleframe\Passport\PassportSupport;
use MeRezaRezaei\Teleframe\Schema\Generator\SchemaRegenerator;
use MeRezaRezaei\Teleframe\Store\Backup\BackupSupport;
use MeRezaRezaei\Teleframe\Store\Backup\DatabaseBackupService;
use MeRezaRezaei\Teleframe\Store\StoreService;
use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * THE public face (roadmap Phase 3 gate): one class composes every module
 * behind PSR seams — it composes, never owns. Module singletons keep their
 * full shape (Teleclient stays byte-stable for existing consumers); the
 * facade is a thin, container-resolvable convenience that wires them.
 *
 * The one cross-module glue the facade legitimately owns: the send path
 * writes the Q2d elimination registry the EchoEliminator reads — explicit
 * replies (Q6) are what keep the pipeline loop-free.
 */
final class Teleframe
{
    /** observe() kind filters: 0 = any (no filtering). */
    public const KIND_ANY = 0;

    /** observe() kind filter: user account ('account' in tf_accounts). */
    public const KIND_ACCOUNT = 1;

    /** observe() kind filter: bot ('bot' in tf_accounts). */
    public const KIND_BOT = 2;

    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    /**
     * @param  array<string, mixed>  $update
     * @return Model|null the hydrated curated root (TfMessage / TfUpdate),
     *                    or null when the constructor has no curated mirror
     *                    surface — nothing storeable.
     */
    public function ingest(array $update, int $accountId): ?Model
    {
        return $this->container->get(UpdateIngestor::class)->ingest($update, $accountId);
    }

    /**
     * @param  array<string, mixed>  $params
     * @param  array<string, mixed>  $response
     * @return Model|null the hydrated curated root, or null when the
     *                    response carries no curated surface.
     */
    public function ingestResponse(string $method, array $params, array $response, int $accountId): ?Model
    {
        return $this->container->get(UpdateIngestor::class)->ingestResponse($method, $params, $response, $accountId);
    }

    /** The D1 entry point: catch-all `*` subscription (matches any constructor). */
    public function onMessage(string|array|\Closure $handler, int $priority = 0): self
    {
        $this->container->get(HandlerRegistry::class)->onMessage($handler, $priority);

        return $this;
    }

    public function on(string $match, string|array|\Closure $handler, int $priority = 0, bool $onOwn = false): self
    {
        $this->container->get(HandlerRegistry::class)->on($match, $handler, $priority, $onOwn);

        return $this;
    }

    /**
     * A first-class programmatic observe/react surface for company apps: an
     * ``onMessage``-style ``*`` handler that ALSO carries an account scope.
     *
     * Each dispatch first checks the uprate's ``Update->accountId`` against
     * the scope and skips (null) on mismatch; scope ``'*'`` matches every
     * account. Registration delegates to the shared handler table through
     * ``onMessage``, so the wrapped observer flows the REAL pipeline — echo
     * elimination, replay dedup, priority ordering and first-match-wins all
     * apply exactly as for any other ``*`` handler.
     *
     * The observed closure signature is ``function (Update $update): void``;
     * the wrapped handler goes through the dispatcher's own parameter
     * resolution (the existing DI-shaped receiving is not re-invented here).
     * String / ``[Class::class, 'method']`` handlers are resolved from the
     * facade's PSR-11 container lazily at dispatch time, like the pipeline.
     *
     * Note the substrate's first-match-wins contract: overlapping observers
     * (same or ``'*'`` scope) see only the highest-priority registration per
     * update — register one observer per scope, or tune ``$priority``.
     *
     * ``$kind`` adds an optional registry-kind filter (``0`` = any, the
     * existing behavior; ``self::KIND_ACCOUNT`` = user account;
     * ``self::KIND_BOT`` = bot). When non-zero the wrapped dispatch resolves
     * the KIND of the frame's ``$update->accountId`` from the ``tf_accounts``
     * registry and skips the frame unless it matches. Regulatory details:
     * the filter acts only when the host exposes a DB seam (the container
     * resolves a connection) — on a plain-PHP / DB-less container every frame
     * is accepted (conservative, no crash); a frame whose account has no
     * registry row, or a registry blip mid-run, resolves to a mismatch and is
     * filtered OUT (strict kind semantics) / accepted (blip), never fatal.
     */
    public function observe(int|string $accountId, string|array|\Closure $handler, int $priority = 0, int $kind = self::KIND_ANY): self
    {
        $this->container->get(HandlerRegistry::class)->onMessage(
            $this->accountScoped($accountId, $handler, $kind),
            $priority,
        );

        return $this;
    }

    /**
     * The programmatic listen/ignore toggle: mark the given (account, peer)
     * pair ``store_only`` so the ingest event path no longer emits app
     * events for it — the notify/log-channel use case, controlled in real
     * time (the ``UpdateRouter`` decision flips on the next lookup).
     *
     * Best-effort by design: when the facade's container can resolve the
     * event dispatcher AND a DB connection the toggle writes a
     * ``tg_update_routing`` row (mode ``store_only``) through the
     * ``UpdateRoutingRule`` model — an unmigrated table is swallowed, no-op.
     * When the container lacks the seam (plain-PHP host) this is a no-op.
     *
     * @todo host-only wiring: bind the tg_update_routing writer for the
     *       plain-PHP container so ignore() is more than a documented no-op.
     */
    public function ignore(int $accountId, int $peerType = 0, int $peerId = 0): self
    {
        if (! $this->routingWritable()) {
            return $this;
        }

        try {
            UpdateRoutingRule::query()->updateOrCreate(
                [
                    'account_id' => $accountId,
                    'peer_type' => $peerType,
                    'peer_id' => $peerId,
                ],
                [
                    'mode' => UpdateRoutingRule::MODE_STORE_ONLY,
                    'priority' => 0,
                ],
            );
        } catch (\Throwable) {
            // tg_update_routing not yet migrated → best-effort no-op.
        }

        return $this;
    }

    /**
     * Drive the REAL pipeline with fixture updates (plain-PHP test entry —
     * the running-mode surface without transport). Returns every non-null
     * handler result in fixture order (echoed/self-originated frames drop).
     *
     * @param  list<array{update?: array, account_id?: int, ts?: int}>  $fixtures
     */
    public function run(array $fixtures): array
    {
        $dispatcher = $this->container->get(UpdateDispatcher::class);
        $results = [];

        foreach ($fixtures as $fixture) {
            $results[] = $dispatcher->dispatch(Update::fromBus(
                (array) ($fixture['update'] ?? []),
                (int) ($fixture['account_id'] ?? 0),
                isset($fixture['ts']) ? (int) $fixture['ts'] : null,
            ));
        }

        return $results;
    }

    public function user(int $accountId, int $tgId): ?TfUser
    {
        return $this->container->get(EntityAggregator::class)->user($accountId, $tgId);
    }

    public function chat(int $accountId, int $tgId): ?TfChat
    {
        return $this->container->get(EntityAggregator::class)->chat($accountId, $tgId);
    }

    public function channel(int $accountId, int $tgId): ?TfChannel
    {
        return $this->container->get(EntityAggregator::class)->channel($accountId, $tgId);
    }

    public function route(string $match, string $target): self
    {
        $this->container->get(RouteTable::class)->set($match, $target);

        return $this;
    }

    public function backup(string $setId): VaultInterface
    {
        if (! $this->container->has(BackupCommand::VAULT_FACTORY_KEY)) {
            throw new \RuntimeException('no backup vault factory bound in the container');
        }

        /** @var callable $factory */
        $factory = $this->container->get(BackupCommand::VAULT_FACTORY_KEY);

        return $factory($setId);
    }

    /**
     * The TStore archive (S1–S11): the account-scoped StoreService seeding
     * the Telegram-backed encrypted object store. Resolves the bind under
     * TstoreCommand::SERVICE_KEY, so `Teleframe::store()->put(...)` and the
     * `teleframe:tstore` CLI share one service instance.
     */
    public function store(): StoreService
    {
        if (! $this->container->has(TstoreCommand::SERVICE_KEY)) {
            throw new \RuntimeException('no tstore service bound in the container');
        }

        $service = $this->container->get(TstoreCommand::SERVICE_KEY);
        if (! $service instanceof StoreService) {
            throw new \RuntimeException(TstoreCommand::SERVICE_KEY.' must bind a '.StoreService::class);
        }

        return $service;
    }

    /**
     * The store-backup orchestrator (design 2026-09-24): dump the host DB
     * → sealed put into the teleframe-backup: bucket → run ledger →
     * heartbeat. Resolves the bind under BackupSupport::SERVICE_KEY, so
     * `Teleframe::storeBackup()->backup()` and the `teleframe:tstore
     * backup|schedule` CLI share one service instance.
     */
    public function storeBackup(): DatabaseBackupService
    {
        if (! $this->container->has(BackupSupport::SERVICE_KEY)) {
            throw new \RuntimeException('no store backup service bound in the container');
        }

        $service = $this->container->get(BackupSupport::SERVICE_KEY);
        if (! $service instanceof DatabaseBackupService) {
            throw new \RuntimeException(BackupSupport::SERVICE_KEY.' must bind a '.DatabaseBackupService::class);
        }

        return $service;
    }

    /**
     * The bot/user app framework orchestrator (design 2026-09-24): declare
     * an app (name, mode bot|user, chat triggers → handler callbacks,
     * scheduled telegram calls), register its triggers onto the SHIPPED
     * handler pipeline, and run its recurring calls through the schedule
     * seam. Resolves the bind under AppSupport::SERVICE_KEY, so
     * `Teleframe::app()->register(...)` and the `teleframe:app` CLI share
     * one AppRuntime instance.
     */
    public function app(): AppRuntime
    {
        if (! $this->container->has(AppSupport::SERVICE_KEY)) {
            throw new \RuntimeException('no app service bound in the container');
        }

        $service = $this->container->get(AppSupport::SERVICE_KEY);
        if (! $service instanceof AppRuntime) {
            throw new \RuntimeException(AppSupport::SERVICE_KEY.' must bind a '.AppRuntime::class);
        }

        return $service;
    }

    /**
     * The Telegram Passport login orchestrator (design 2026-09-24): a
     * configured host receives a passport webhook update, decrypts (shipped
     * PassportDecryptor, untouched), validates, nonce-verifies, resolves the
     * Laravel user, ensures the binding row and rides the host auth through
     * the onLogin hook. Resolves the bind under PassportSupport::SERVICE_KEY,
     * so `Teleframe::passport()->login($update)` and the `Passport` facade
     * share ONE service instance.
     *
     * Unconfigured: resolves (all seams default to memory/fake), login()
     * throws loud on first real use — never silent-drop.
     */
    public function passport(): PassportLoginService
    {
        if (! $this->container->has(PassportSupport::SERVICE_KEY)) {
            throw new \RuntimeException('no passport service bound in the container');
        }

        $service = $this->container->get(PassportSupport::SERVICE_KEY);
        if (! $service instanceof PassportLoginService) {
            throw new \RuntimeException(PassportSupport::SERVICE_KEY.' must bind a '.PassportLoginService::class);
        }

        return $service;
    }

    /**
     * Regenerate the schema layer from the .tl source mirror into the
     * package-generated artifacts. Returns the regeneration counts.
     *
     * @return array<string, mixed>
     */
    public function schemaLayer(?string $schemasDir = null, ?string $outputDir = null): array
    {
        $regenerator = $this->container->get(
            SchemaRegenerator::class,
        );
        $root = dirname(__DIR__, 2);

        return $regenerator->regenerate(
            $schemasDir ?? $root.'/schema/sources',
            $outputDir ?? $root,
        );
    }

    public function daemon(): Daemon
    {
        return $this->container->get(Daemon::class);
    }

    /**
     * The Q2d send path: record the reply (random_id/msg_id) in the shared
     * PSR-16 elimination registry so its echo never re-enters handlers.
     * Existing consumers keep their own send path unchanged.
     *
     * @param  array<string, mixed>  $send
     */
    public function send(int $accountId, array $send): int
    {
        $cache = $this->container->has(CacheInterface::class)
            ? $this->container->get(CacheInterface::class)
            : null;
        if ($cache === null) {
            throw new \RuntimeException('facade send requires a Psr\SimpleCache\CacheInterface binding');
        }

        $sends = new EchoEliminator($cache, new HandlerMatcher($this->container->get(HandlerRegistry::class)));
        $sends->remember($accountId, $send);

        $randomId = (string) ($send['random_id'] ?? '');
        $msgId = (string) ($send['msg_id'] ?? '');

        return ($randomId !== '' ? 1 : 0) + ($msgId !== '' ? 1 : 0);
    }

    /** Service-locator convenience: resolve any composed module by PSR-11. */
    public function resolve(string $abstract): mixed
    {
        return $this->container->get($abstract);
    }

    /**
     * One-hop forwarding: any method not surfaced explicitly resolves to
     * the composed module that owns it — never module logic here.
     */
    public function __call(string $name, array $arguments): mixed
    {
        foreach ($this->owners() as $service) {
            if (method_exists($service, $name)) {
                return $service->{$name}(...$arguments);
            }
        }

        throw new \BadMethodCallException("Teleframe::{$name}() is not a facade or module method.");
    }

    /**
     * Wrap an observer so each dispatch checks the uprate's account against
     * the scope; a mismatch is skipped (null-equivalent). Scope ``'*'``
     * matches every account. The wrapper flows the REAL pipeline (priority,
     * echo elimination, replay dedup, first-match-wins) and the container
     * resolution is the dispatcher's — parameter resolution is not
     * re-invented here, the observable receives the uprate like any handler.
     *
     * ``$kind`` (0 = any): an optional registry-kind filter resolved against
     * the frame's OWN account when the DB seam exists at registration time;
     * a DB-less container collapses the filter to ``KIND_ANY`` (accept all).
     */
    private function accountScoped(int|string $accountId, string|array|\Closure $handler, int $kind = self::KIND_ANY): \Closure
    {
        $kind = $kind !== self::KIND_ANY && $this->dbReachable()
            ? $kind
            : self::KIND_ANY;

        // W-12: `observe()` accepts int|string (string = the '*' wildcard).
        // A numeric-string scope ('7') previously never matched the int
        // accountId under strict !==; normalize any non-'*' scope to int so
        // the documented int scope contract holds regardless of how the
        // caller spelled it.
        $accountId = $accountId === '*' ? '*' : (int) $accountId;

        return function (Update $update) use ($accountId, $handler, $kind): mixed {
            if ($accountId !== '*' && $update->accountId !== $accountId) {
                return null;
            }

            if ($kind !== self::KIND_ANY && ! $this->kindMatches($update->accountId, $kind)) {
                return null;
            }

            return $this->invokeObserved($handler, $update);
        };
    }

    /**
     * Does the frame's account carry the requested registry kind? The
     * ``tf_accounts`` lookup is wrapped: a missing/absent table mid-run is a
     * conservative accept (never crash); a reachable registry with no row for
     * the account resolves to a strict mismatch (the frame is filtered out).
     */
    private function kindMatches(int $accountId, int $kind): bool
    {
        $wanted = $kind === self::KIND_BOT
            ? TfAccount::KIND_BOT
            : TfAccount::KIND_ACCOUNT;

        try {
            $resolved = TfAccount::query()->whereKey($accountId)->value('kind');
        } catch (\Throwable) {
            // tf_accounts not migrated / registry blip → accept, never crash.
            return true;
        }

        return $resolved === $wanted;
    }

    /** Resolve + invoke one scoped observer, mirroring the dispatcher's resolution. */
    private function invokeObserved(string|array|\Closure $handler, Update $update): mixed
    {
        if ($handler instanceof \Closure) {
            return $handler($update);
        }

        if (is_array($handler)) {
            /** @var object $instance */
            $instance = $this->container->get($handler[0]);

            return $instance->{$handler[1]}($update);
        }

        /** @var object|string|array<mixed> $service */
        $service = $this->container->get($handler);

        if (is_array($service) || is_string($service)) {
            return [$service, '__invoke']($update);
        }

        return ($service instanceof \Closure ? $service : [$service, '__invoke'])($update);
    }

    /**
     * Is a DB connection resolvable from the facade's PSR-11 container? The
     * conservative gate for registry-kind filtering: a plain-PHP container
     * without the DB seam gets NO kind filter (accept all) — ``observe()``
     * kind semantics degrade to the pre-bot behavior byte-for-byte.
     */
    private function dbReachable(): bool
    {
        return $this->container->has(ConnectionInterface::class)
            || $this->container->has('db')
            || $this->container->has('db.connection');
    }

    /**
     * Is the host seam resolvable so ignore() can write the DB row? The
     * routing toggle needs the event dispatcher (the Flux of
     * RoutingSettingsChanged) and a DB connection — vanishing in
     * plain-PHP containers, where ignore() is a documented no-op.
     */
    private function routingWritable(): bool
    {
        return
            $this->container->has(Dispatcher::class)
            && (
                $this->container->has(ConnectionInterface::class)
                || $this->container->has('db')
                || $this->container->has('db.connection')
            );
    }

    /** @return list<object> */
    private function owners(): array
    {
        $owners = [
            UpdateIngestor::class,
            EntityAggregator::class,
            RouteTable::class,
            HandlerRegistry::class,
            UpdateDispatcher::class,
            Daemon::class,
        ];

        $resolved = [];
        foreach ($owners as $owner) {
            if ($this->container->has($owner)) {
                $resolved[] = $this->container->get($owner);
            }
        }

        return $resolved;
    }
}
