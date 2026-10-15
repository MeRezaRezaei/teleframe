<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Control;

use Closure;
use RuntimeException;

/**
 * The control-layer transport terminal: the callable RelayPipeline hands a
 * message to once the filter onion has decided it passes. Binds one relayed
 * account to its live transport — an OUTBOUND message (the web client asking
 * our relay to call Telegram) is dispatched to the bound account client and
 * the RPC reply comes back as the produced INBOUND RelayMessage, so the
 * same filter chain can rewrite or drop data on both legs of a conversation.
 *
 * The scope is resolved lazily ONLY on the first outbound dispatch (never at
 * construction, never for INBOUND), mirroring the AccountRelay seam's "never
 * dialed until asked" rule. Inbound updates do not belong here — they flow
 * across AccountUpdateStream (BUS → committed key → hydrator → INBOUND →
 * filters → publisher), which runs the identical RelayFilter/RelyPipeline
 * onion. That leg carries the COMMITTED IDENTITY ROW, not the raw wire update
 * (D-044: `tf_updates` stores zero JSON, so the decoded update array is not
 * reconstructible from the row); this terminal never sees it and never needs
 * to.
 *
 * Plain PHP, no facades, no credentials: the scope resolver (production:
 * AccountRelay::scope() over the RelayFactory, which yields a RelayScope
 * whose `client` is the live UserAccountScope) and the client executor are
 * injected callables — tests pass fakes. The default executor dispatches via
 * the bound client's call(string, array) surface and never touches a session
 * string; nothing here is ever logged.
 */
final class RelayTerminal
{
    /** @var (Closure(): mixed)|null  lazy live scope for the bound account */
    private readonly ?Closure $scopeResolver;

    /** @var (Closure(mixed, RelayMessage): RelayMessage)|null  transport executor */
    private readonly ?Closure $executor;

    private bool $resolved = false;

    private mixed $scope = null;

    /**
     * @param  (callable(): mixed)|null  $scopeResolver  production: the account's AccountRelay::scope()
     * @param  (callable(mixed $scope, RelayMessage): RelayMessage)|null  $executor  production null → default call() dispatch
     */
    public function __construct(
        ?callable $scopeResolver = null,
        ?callable $executor = null,
    ) {
        $this->scopeResolver = $scopeResolver === null ? null : $scopeResolver(...);
        $this->executor = $executor === null ? null : $executor(...);
    }

    /**
     * RelayPipeline terminal contract: dispatch OUTBOUND against the bound
     * client and return the produced reply; pass INBOUND straight through
     * (it belongs to AccountUpdateStream's leg — the committed identity row —
     * not this terminal).
     */
    public function __invoke(RelayMessage $message): RelayMessage
    {
        if ($message->isInbound()) {
            return $message;
        }

        $executor = $this->executor ?? $this->defaultExecutor();

        return $executor($this->resolveScope(), $message);
    }

    /** Whether the bound scope has been resolved (first outbound dispatch). */
    public function resolved(): bool
    {
        return $this->resolved;
    }

    /**
     * Lazily bind: resolve the live scope once, on the first outbound
     * dispatch, and reuse it for the life of the terminal — the transport
     * decides once whose client is live, instead of re-dialing per message.
     */
    private function resolveScope(): mixed
    {
        if (! $this->resolved) {
            if ($this->scopeResolver === null) {
                throw new RuntimeException('relay terminal: no scope resolver injected — wire AccountRelay::scope() or a fake');
            }

            $this->scope = ($this->scopeResolver)();
            $this->resolved = true;
        }

        return $this->scope;
    }

    /**
     * Production executor: dispatch the outbound method to the bound client's
     * call(string, array) surface and wrap the RPC result as the produced
     * INBOUND reply (params = result array; raw left for the transport).
     * Accepts either the RelayScope holder (object with a `client`) or the
     * raw client object itself, so both real and fake scopes dispatch.
     */
    private function defaultExecutor(): Closure
    {
        return static function (mixed $scope, RelayMessage $message): RelayMessage {
            $client = $scope;
            if (is_object($scope) && is_object($scope->client ?? null)) {
                $client = $scope->client;
            }

            if (! is_object($client) || ! method_exists($client, 'call')) {
                throw new RuntimeException(sprintf(
                    'relay terminal: bound scope for %s must expose a client with call(string, array) — got %s',
                    $message->method,
                    is_object($scope) ? $scope::class : get_debug_type($scope),
                ));
            }

            $result = $client->call($message->method, $message->params);

            return new RelayMessage(
                RelayMessage::INBOUND,
                $message->method,
                is_array($result) ? $result : [],
            );
        };
    }
}
