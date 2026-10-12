<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Services;

use Closure;
use MeRezaRezaei\Teleframe\Control\AccountRelay;
use MeRezaRezaei\Teleframe\Control\RelayContext;
use MeRezaRezaei\Teleframe\Control\RelayMessage;
use MeRezaRezaei\Teleframe\Control\RelayTerminal;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Builds the VAULT DISPATCH callable that VaultMtprotoAnswerer consumes:
 * `(string $method, array $params): array`. Each dispatch reads the ACTIVE
 * vault account + its monotonic generation (cheap PSR-16 reads through
 * RelayActiveAccount); when the generation differs from the seated one, the
 * cached AccountRelay/RelayTerminal pair is dropped and re-seated on
 * RelayFactory::forAccount(newCtx) — whose scope resolver is lazy, so a
 * re-seat never reads the vault, never builds a client, and never dials.
 * The outbound RelayMessage then runs the AccountRelay filter onion and is
 * dispatched by the RelayTerminal's default executor against the SAME lazy
 * scope RelayFactory::terminalFor()/bridgeFor() serve; the RPC result array
 * comes back.
 *
 * Never touches a session string; relay-telemetry-only logging (PSR-3,
 * silent by default). Plain-PHP constructible: the config reader is an
 * injected callable (production default: Laravel's config() helper, resolved
 * only when rsaPrivateKey() is actually read).
 *
 * W-09 (tenant-scoped ACTIVE): an optional ``$scope`` namespaces the
 * active-context reads to RelayActiveAccount::currentFor() /
 * currentGenerationFor(), so a factory wired for tenant A re-seats only when
 * A's OWN generation changes — a switch in tenant B writes a different PSR-16
 * key and never pivots this terminal. With no scope (the provider's shared
 * singleton) the factory preserves the legacy global behavior exactly.
 *
 * Relay dispatch middlewares: a host may inject a list of policy middlewares
 * via the constructor (the provider resolves the container binding
 * `teleframe.relay.dispatch-middleware`, see the key constant). Each middleware
 * is an onion layer around the terminal dispatch — it receives the method,
 * params, a per-connection RelayDispatchContext (account + Laravel user
 * granted by the WS ticket, plus the read-only scope) and the next callable.
 * With NO middlewares the dispatch is byte-for-byte the historical
 * ReadOnlyVaultDispatch/raw terminal; middlewares only come into play when a
 * host binds them.
 */
final class RelayVaultTerminalFactory
{
    /**
     * Service-container key hosting the relay dispatch middlewares: a list of
     * callables `(string $method, array $params, RelayDispatchContext $ctx, callable $next): array`.
     * The provider binds it to an empty list by default — host override wins.
     */
    public const DISPATCH_MIDDLEWARE_KEY = 'teleframe.relay.dispatch-middleware';

    /** @var Closure(string, mixed): mixed */
    private readonly Closure $config;

    /** @var list<callable(string, array<string, mixed>, RelayDispatchContext, callable): array<string, mixed>> */
    private readonly array $dispatchMiddlewares;

    private ?AccountRelay $relay = null;

    private ?RelayTerminal $terminal = null;

    private ?int $seatedGeneration = null;

    /** @var RelayReadOnlyGrants|null lazily built when the host injected none */
    private ?RelayReadOnlyGrants $readOnlyGrants;

    /**
     * @param  callable(string $key, mixed $default): mixed|null  $config  teleframe config reader (default: config())
     * @param  ReadOnlyScope|null  $readOnlyScope  when injected, dispatch() is wrapped in
     *                                             ReadOnlyVaultDispatch so a read-only scope (Laravel-user
     *                                             session) refuses state-changing RPCs; null keeps the raw
     *                                             dispatch with zero overhead (standalone default)
     * @param  string|null  $scope  optional tenant/peer scope narrowing the
     *                              ACTIVE-context reads (W-09); null keeps the
     *                              legacy global behavior
     * @param  list<callable(string $method, array<string, mixed> $params, RelayDispatchContext $ctx, callable $next): array<string, mixed>>|null  $dispatchMiddlewares
     *                                                                                                                                                                   optional policy onion around the terminal dispatch; each middleware
     *                                                                                                                                                                   sees the per-connection RelayDispatchContext (ticket-granted account +
     *                                                                                                                                                                   Laravel user + read-only scope) and delegates through $next. Empty
     *                                                                                                                                                                   (default) keeps the historical dispatch byte-for-byte.
     * @param  RelayReadOnlyGrants|null  $readOnlyGrants  the per-CONNECTION read-only registry the
     *                                                    terminal arms its guard from (the ticket a socket
     *                                                    redeemed). Null = the factory builds a private
     *                                                    one, so the guard is live by default and fails
     *                                                    closed on an undeterminable grant.
     * @param  bool  $enforceReadOnlyGrant  teleframe.relay.read_only.enforce — false opts out of the
     *                                      grant-derived guard entirely (unsafe)
     * @param  bool  $undeterminedGrantIsReadOnly  teleframe.relay.read_only.undetermined_grant —
     *                                             false makes an undeterminable grant fail OPEN
     *                                             (unsafe); the default keeps the guard fail-closed
     */
    public function __construct(
        private readonly RelayFactory $factory,
        private readonly RelayActiveAccount $activeAccount,
        private readonly ?LoggerInterface $logger = null,
        ?callable $config = null,
        private readonly ?ReadOnlyScope $readOnlyScope = null,
        private readonly ?string $scope = null,
        ?array $dispatchMiddlewares = null,
        ?RelayReadOnlyGrants $readOnlyGrants = null,
        private readonly bool $enforceReadOnlyGrant = true,
        private readonly bool $undeterminedGrantIsReadOnly = true,
    ) {
        $this->config = Closure::fromCallable($config ?? static fn (string $key, mixed $default = null): mixed => config($key, $default));
        $this->dispatchMiddlewares = $dispatchMiddlewares ?? [];
        $this->readOnlyGrants = $readOnlyGrants;
    }

    /**
     * The per-connection read-only registry this terminal arms its guard
     * from, building a private (already fail-closed) one when the host
     * injected none — the guard is therefore live by DEFAULT rather than
     * depending on an opt-in binding.
     */
    public function readOnlyGrants(): RelayReadOnlyGrants
    {
        return $this->readOnlyGrants ??= new RelayReadOnlyGrants($this->undeterminedGrantIsReadOnly);
    }

    /**
     * The ACTIVE account context the dispatch seats, narrowed to the
     * factory's tenant/peer scope when one is set (W-09).
     */
    private function currentContext(): ?RelayContext
    {
        return $this->scope === null
            ? $this->activeAccount->current()
            : $this->activeAccount->currentFor($this->scope);
    }

    /**
     * The ACTIVE generate state the dispatch re-seats on, narrowed to the
     * factory's tenant/peer scope when one is set (W-09) — a switch in the
     * GLOBAL or another tenant's scope does not bump it.
     */
    private function currentGenerationScoped(): int
    {
        return $this->scope === null
            ? $this->activeAccount->currentGeneration()
            : $this->activeAccount->currentGenerationFor($this->scope);
    }

    /**
     * The vault dispatch callable: re-seats on account switch (generation
     * change within THIS factory's scope), then runs the outbound
     * RelayMessage through the onion into the RelayTerminal and hands back
     * the decoded result array. When a ReadOnlyScope was injected, the
     * closure is wrapped in ReadOnlyVaultDispatch — a read-only HOST scope
     * (Laravel-user request frame) is enforced against the READ-ONLY
     * allow-list, AND the per-CONNECTION ticket grant the answerer threads in
     * as the third argument is enforced against the same allow-list through
     * the per-connection RelayReadOnlyGrants registry (finding C5: the
     * console daemon has no request frame, so the grant is what arms the
     * guard there). A grant the registry cannot determine is read-only.
     *
     * With NO middlewares the returned callable IS the historical one: the
     * raw closure (or its ReadOnlyVaultDispatch wrapper) — byte-for-byte
     * backward compatible, receiving `(string $method, array $params)`.
     * When middlewares are injected, the returned callable additionally
     * accepts a third `$ticketGrant` argument (the normalized WS-ticket grant
     * the answerer threaded into the dispatch) and runs every middleware in
     * order as an onion around the terminal: each middleware decides or
     * delegates with `$next($method, $params)`.
     *
     * @return callable(string $method, array<string, mixed> $params, (array{account_id: int, user_id: int|string|null}|null) $ticketGrant): array<string, mixed>
     */
    public function dispatch(): callable
    {
        $inner = function (string $method, array $params, ?array $ticketGrant = null): array {
            $ctx = $this->currentContext();
            $generation = $this->currentGenerationScoped();

            if ($ctx === null) {
                throw new RuntimeException('relay vault terminal: no active account — call RelayActiveAccount::switchFor first');
            }

            if ($generation !== $this->seatedGeneration || $this->terminal === null) {
                $this->relay = $this->factory->forAccount($ctx);
                $this->terminal = $this->factory->terminalFor($ctx);
                $this->seatedGeneration = $generation;

                $this->logger?->info(sprintf(
                    'relay vault terminal: seated account id=%d (dc=%d) at generation %d',
                    $ctx->accountId,
                    $ctx->dcId,
                    $generation,
                ));
            }

            if ($this->relay === null) {
                throw new RuntimeException('relay vault terminal: account relay could not be seated');
            }

            $reply = $this->relay->send(
                new RelayMessage(RelayMessage::OUTBOUND, $method, $params),
                $this->terminal,
            );

            if (! $reply->isInbound()) {
                $this->logger?->warning(sprintf(
                    'relay vault terminal: outbound %s did not produce an inbound reply',
                    $method,
                ));
            }

            return $reply->params;
        };

        $terminal = $inner;
        if ($this->readOnlyScope !== null) {
            $terminal = new ReadOnlyVaultDispatch(
                $inner,
                $this->readOnlyScope,
                $this->readOnlyGrants(),
                $this->enforceReadOnlyGrant,
            );
        }

        if ($this->dispatchMiddlewares === []) {
            return $terminal;
        }

        // Policy onion: each middleware gets the per-connection context
        // (ticket-granted account + Laravel user + read-only scope) and a
        // $next it may call to continue. The outermost callable is the one
        // the answerer invokes — it still accepts the historical 2-arg form,
        // and the optional 3rd grant is normalized into the context.
        $middlewares = $this->dispatchMiddlewares;
        $readOnlyScope = $this->readOnlyScope;

        return static function (string $method, array $params, ?array $ticketGrant = null) use ($terminal, $middlewares, $readOnlyScope): array {
            $context = RelayDispatchContext::fromTicketGrant($ticketGrant, $readOnlyScope);

            $chain = $terminal;
            foreach (array_reverse($middlewares) as $middleware) {
                $next = $chain;
                $chain = static fn (string $m, array $p): array => $middleware($m, $p, $context, $next);
            }

            return $chain($method, $params);
        };
    }

    /**
     * The generation stored by RelayActiveAccount — consumers poll it
     * (alongside current()) on every dispatch to detect an account switch
     * within THIS factory's tenant/peer scope (W-09: without a scope this is
     * exactly the legacy GLOBAL generation).
     */
    public function currentGeneration(): int
    {
        return $this->currentGenerationScoped();
    }

    /**
     * The relay's RSA private key (PEM) that identifies this terminal's DH
     * handshake to browsers — config teleframe.relay.vault_terminal.rsa_key.
     *
     * @throws RuntimeException when the key is unset or not a non-empty string
     */
    public function rsaPrivateKey(): string
    {
        $key = ($this->config)('teleframe.relay.vault_terminal.rsa_key', '');

        if (! is_string($key) || trim($key) === '') {
            throw new RuntimeException('relay vault terminal: teleframe.relay.vault_terminal.rsa_key is unset');
        }

        return $key;
    }
}
