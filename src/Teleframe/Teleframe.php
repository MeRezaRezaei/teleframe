<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe;

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
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChat;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUser;
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
    public function __construct(
        private readonly ContainerInterface $container,
    ) {
    }

    /** @param array<string, mixed> $update */
    public function ingest(array $update, int $accountId): TlInstanceModel
    {
        return $this->container->get(UpdateIngestor::class)->ingest($update, $accountId);
    }

    /**
     * @param array<string, mixed> $params
     * @param array<string, mixed> $response
     */
    public function ingestResponse(string $method, array $params, array $response, int $accountId): ?TlInstanceModel
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
     * Drive the REAL pipeline with fixture updates (plain-PHP test entry —
     * the running-mode surface without transport). Returns every non-null
     * handler result in fixture order (echoed/self-originated frames drop).
     *
     * @param list<array{update?: array, account_id?: int, ts?: int}> $fixtures
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

    public function user(int $accountId, int $tgId): ?TlUser
    {
        return $this->container->get(EntityAggregator::class)->user($accountId, $tgId);
    }

    public function chat(int $accountId, int $tgId): ?TlChat
    {
        return $this->container->get(EntityAggregator::class)->chat($accountId, $tgId);
    }

    public function channel(int $accountId, int $tgId): ?TlChat
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
     * Regenerate the schema layer from the .tl source mirror into the
     * package-generated artifacts. Returns the regeneration counts.
     *
     * @return array<string, mixed>
     */
    public function schemaLayer(?string $schemasDir = null, ?string $outputDir = null): array
    {
        $regenerator = $this->container->get(
            \MeRezaRezaei\Teleframe\Schema\Generator\SchemaRegenerator::class,
        );
        $root = dirname(__DIR__, 2);

        return $regenerator->regenerate(
            $schemasDir ?? $root . '/schema/sources',
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
     * @param array<string, mixed> $send
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

    /** @return list<object> */
    private function owners(): array
    {
        $owners = [
            Teleclient::class,
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