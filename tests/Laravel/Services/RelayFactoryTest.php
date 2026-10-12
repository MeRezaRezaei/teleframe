<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel\Services;

use MeRezaRezaei\Teleframe\Control\AccountRelay;
use MeRezaRezaei\Teleframe\Control\RelayContext;
use MeRezaRezaei\Teleframe\Control\RelayFilter;
use MeRezaRezaei\Teleframe\Control\RelayMessage;
use MeRezaRezaei\Teleframe\Core\MTProto\Connection\EncryptedConnection;
use MeRezaRezaei\Teleframe\Core\MTProto\SessionData;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\RelayFrameDialBridge;
use MeRezaRezaei\Teleframe\Laravel\Services\RelayFactory;
use MeRezaRezaei\Teleframe\Laravel\Services\RelayScope;
use MeRezaRezaei\Teleframe\Laravel\Services\RelaySessionResolver;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * RelayFactory: the AccountRelay seam builder. Building a relay must stay
 * lazy (no scope resolution until AccountRelay::scope()), expose the exact
 * RelayContext it was built with, and run pushed filters through the
 * pipeline so messages reach the (default) terminal unchanged.
 */
final class RelayFactoryTest extends TestCase
{
    public function test_for_account_returns_relay_with_matching_context(): void
    {
        $resolver = new RelaySessionResolver(
            vaultLoader: static fn (string $ref, int $dc): ?string => 'ctx-session',
            clientFactory: static fn (string $session, int $dc): object => new stdClass,
        );
        $factory = new RelayFactory($resolver);
        $ctx = new RelayContext(accountId: 11, dcId: 2, label: 'eve', liveEndpoint: 'wss://example.test');

        $relay = $factory->forAccount($ctx);

        self::assertInstanceOf(AccountRelay::class, $relay);
        self::assertSame($ctx, $relay->context(), 'the relay must expose the exact context it was built with');
    }

    public function test_scope_resolves_lazily_through_resolver(): void
    {
        $resolved = 0;
        $resolver = new RelaySessionResolver(
            vaultLoader: static fn (string $ref, int $dc): ?string => 'lazy-session',
            clientFactory: function (string $session, int $dc) use (&$resolved): object {
                $resolved++;

                return new stdClass;
            },
        );
        $factory = new RelayFactory($resolver);
        $relay = $factory->forAccount(new RelayContext(accountId: 3, dcId: 4, label: 'frank'));

        self::assertSame(0, $resolved, 'building the relay must not touch the resolver');
        $scope = $relay->scope();

        self::assertSame(1, $resolved, 'scope() resolves lazily on first call');
        self::assertInstanceOf(RelayScope::class, $scope);
        self::assertSame(4, $scope->dcId);
    }

    public function test_send_runs_filters_and_passes_message_to_terminal(): void
    {
        $resolver = new RelaySessionResolver(
            vaultLoader: static fn (string $ref, int $dc): ?string => 'filter-session',
            clientFactory: static fn (string $session, int $dc): object => new stdClass,
        );
        $factory = new RelayFactory($resolver);
        $relay = $factory->forAccount(new RelayContext(accountId: 3, dcId: 4, label: 'frank'));

        $seen = new RecordingFilter;
        $relay->push($seen);

        self::assertSame([$seen], $relay->filters(), 'pushed filters are exposed by the relay');

        $message = new RelayMessage(RelayMessage::OUTBOUND, 'messages.getDialogs', ['limit' => 10]);
        $result = $relay->send($message);

        self::assertSame($message, $result, 'the default terminal passes the message through unchanged');
        self::assertSame(['messages.getDialogs'], $seen->recorded(), 'every pushed filter sees the in-flight message');
    }

    public function test_terminal_for_account_stays_lazy_until_first_outbound(): void
    {
        $resolved = 0;
        $factory = new RelayFactory(new RelaySessionResolver(
            vaultLoader: static fn (string $ref, int $dc): ?string => 'terminal-session',
            clientFactory: function (string $session, int $dc) use (&$resolved): object {
                $resolved++;

                return new TerminalStubClient;
            },
        ));

        $terminal = $factory->terminalFor(new RelayContext(accountId: 7, dcId: 4, label: 'grace'));

        self::assertFalse($terminal->resolved(), 'terminalFor() must not resolve the scope');
        self::assertSame(0, $resolved, 'building the terminal must never touch the resolver');

        $terminal(new RelayMessage(RelayMessage::OUTBOUND, 'messages.getDialogs', []));

        self::assertSame(1, $resolved, 'the scope resolves lazily exactly once, on the first outbound');
        self::assertTrue($terminal->resolved());
    }

    public function test_terminal_for_account_dispatches_outbound_against_the_same_relay_scope(): void
    {
        $client = new TerminalStubClient;
        $factory = new RelayFactory(new RelaySessionResolver(
            vaultLoader: static fn (string $ref, int $dc): ?string => 'terminal-session',
            clientFactory: static fn (string $session, int $dc): object => $client,
        ));

        $terminal = $factory->terminalFor(new RelayContext(accountId: 7, dcId: 4, label: 'grace'));

        $reply = $terminal(new RelayMessage(RelayMessage::OUTBOUND, 'messages.sendMessage', ['peer' => 1]));

        self::assertSame([['messages.sendMessage', ['peer' => 1]]], $client->calls, 'the terminal dispatches through the bound scope client');
        self::assertSame(RelayMessage::INBOUND, $reply->direction, 'the RPC reply flows back inbound');
        self::assertSame(['_' => 'messages.sendMessage', 'ok' => true], $reply->params);
    }

    public function test_terminal_for_account_passes_inbound_without_resolving_or_dialing(): void
    {
        $resolved = 0;
        $factory = new RelayFactory(new RelaySessionResolver(
            vaultLoader: static fn (string $ref, int $dc): ?string => 'inbound-session',
            clientFactory: function (string $session, int $dc) use (&$resolved): object {
                $resolved++;

                return new TerminalStubClient;
            },
        ));

        $terminal = $factory->terminalFor(new RelayContext(accountId: 7, dcId: 4, label: 'grace'));

        $message = new RelayMessage(RelayMessage::INBOUND, 'update', ['_' => 'updateNewMessage']);

        self::assertSame($message, $terminal($message), 'inbound belongs to the update stream leg, not the terminal');
        self::assertSame(0, $resolved, 'inbound must never dial the bound client');
        self::assertFalse($terminal->resolved());
    }

    public function test_bridge_for_account_stays_lazy_until_first_use(): void
    {
        $resolved = 0;
        $factory = new RelayFactory(new RelaySessionResolver(
            vaultLoader: static fn (string $ref, int $dc): ?string => 'bridge-session',
            clientFactory: function (string $session, int $dc) use (&$resolved): object {
                $resolved++;

                return new BridgeStubClient(new SessionData($dc, $session));
            },
        ));

        $bridge = $factory->bridgeFor(new RelayContext(accountId: 7, dcId: 4, label: 'grace'));

        self::assertInstanceOf(RelayFrameDialBridge::class, $bridge, 'bridgeFor() yields the frame-dial bridge');
        self::assertSame(0, $resolved, 'building the bridge must never touch the resolver / read the vault');
    }

    public function test_bridge_surface_is_seated_on_the_same_account_scope_the_onion_uses(): void
    {
        $client = new BridgeStubClient(new SessionData(4, 'bridge-session'));
        $factory = new RelayFactory(new RelaySessionResolver(
            vaultLoader: static fn (string $ref, int $dc): ?string => 'bridge-session',
            clientFactory: static fn (string $session, int $dc): object => $client,
        ));
        $ctx = new RelayContext(accountId: 7, dcId: 4, label: 'grace');

        $relay = $factory->forAccount($ctx);
        $scope = $relay->scope();
        $bridge = $factory->bridgeFor($ctx);

        self::assertSame($scope->client, $factory->forAccount($ctx)->scope()->client, 'the onion scope client is the same instance forAccount() returns');
        self::assertSame($scope->client->getSession(), $bridge->surface()->lastSessionData(), 'the bridge connection is seated on the very scope client session the onion resolves');
        self::assertSame('bridge-session', $bridge->surface()->lastSessionData()->authKey);
        self::assertFalse($bridge->surface()->isInited(), 'seating the bridge never dials');
    }

    public function test_bridge_swap_scope_resolver_switches_account_without_build_time_read(): void
    {
        $resolved = 0;
        $factory = new RelayFactory(new RelaySessionResolver(
            vaultLoader: static fn (string $ref, int $dc): ?string => 'swap-'.$ref,
            clientFactory: function (string $session, int $dc) use (&$resolved): object {
                $resolved++;

                return new BridgeStubClient(new SessionData($dc, $session));
            },
        ));

        $ctxA = new RelayContext(accountId: 7, dcId: 4, label: 'grace');
        $ctxB = new RelayContext(accountId: 9, dcId: 2, label: 'zoe');

        $bridge = $factory->bridgeFor($ctxA);

        self::assertSame(0, $resolved, 'building the switchable bridge must never read the vault');

        self::assertSame('swap-7', $bridge->surface()->lastSessionData()->authKey, 'first use seats account A');
        $resolvedAfterFirst = $resolved;

        $bridge->swapScopeResolver(static fn (): mixed => $factory->forAccount($ctxB)->scope());

        self::assertSame($resolvedAfterFirst, $resolved, 'swapping the scope resolver behind the ticket reads no vault at build');
        self::assertSame('swap-9', $bridge->surface()->lastSessionData()->authKey, 'the swap re-seats the same bridge on account B');
        self::assertSame($resolvedAfterFirst + 1, $resolved, 're-seating resolves the swapped account exactly once, on use');
        self::assertFalse($bridge->surface()->isInited(), 'the re-seated bridge still never dials');
    }

    public function test_terminal_outbound_and_bridge_frame_pump_share_one_account(): void
    {
        $client = new BridgeStubClient(new SessionData(4, 'e2e-session'));
        $factory = new RelayFactory(new RelaySessionResolver(
            vaultLoader: static fn (string $ref, int $dc): ?string => 'e2e-session',
            clientFactory: static fn (string $session, int $dc): object => $client,
        ));
        $ctx = new RelayContext(accountId: 7, dcId: 4, label: 'grace');

        $terminal = $factory->terminalFor($ctx);
        $bridge = $factory->bridgeFor($ctx);

        $pumped = [];
        $reply = $terminal(new RelayMessage(RelayMessage::OUTBOUND, 'messages.sendMessage', ['peer' => 1]));
        $bridge->withFramePump(static function (EncryptedConnection $connection, mixed $frame) use (&$pumped): void {
            $pumped[] = [$connection, $frame];
        });
        $bridge->pumpFrame(['op' => 'update', 'seq' => 3]);

        self::assertSame([['messages.sendMessage', ['peer' => 1]]], $client->calls, 'the outbound leg dispatches through the bound account client');
        self::assertSame(RelayMessage::INBOUND, $reply->direction);
        self::assertCount(1, $pumped, 'the inbound frame reaches the seated pump');
        self::assertSame(['op' => 'update', 'seq' => 3], $pumped[0][1]);
        self::assertSame($client->getSession(), $pumped[0][0]->lastSessionData(), 'the pump terminal is seated on the very session the outbound leg used');
        self::assertFalse($pumped[0][0]->isInited(), 'pumping a frame never dials');
    }
}

/**
 * RelayFilter spy: records each seen method and hands the message onward,
 * so a send() chain with zero real work can prove filter ordering and
 * terminal passthrough.
 */
final class RecordingFilter implements RelayFilter
{
    /** @var list<string> */
    private array $seen = [];

    public function handle(RelayMessage $message, callable $next): RelayMessage
    {
        $this->seen[] = $message->method;

        return $next($message);
    }

    public function recorded(): array
    {
        return $this->seen;
    }
}

/**
 * Fake relayed user-client: records every call() the RelayTerminal's
 * default executor makes and answers with a canned RPC result, so the
 * factory-bound terminal can be proven end-to-end without a network or
 * any session string.
 */
final class TerminalStubClient
{
    /** @var list<array{0: string, 1: array<string, mixed>}> */
    public array $calls = [];

    /** @param array<string, mixed> $params */
    public function call(string $method, array $params = []): array
    {
        $this->calls[] = [$method, $params];

        return ['_' => $method, 'ok' => true];
    }
}

/**
 * Bridge-shaped relayed user-client: the same call() recorder as
 * TerminalStubClient PLUS the UserAccountScope-shaped getSession() surface
 * the frame-dial bridge seats its EncryptedConnection from — so a
 * RelayFactory test can prove the bridge terminates against the exact
 * session the outbound leg uses, with zero network and no session string.
 */
final class BridgeStubClient
{
    /** @var list<array{0: string, 1: array<string, mixed>}> */
    public array $calls = [];

    public function __construct(public readonly SessionData $session) {}

    public function getSession(): SessionData
    {
        return $this->session;
    }

    /** @param array<string, mixed> $params */
    public function call(string $method, array $params = []): array
    {
        $this->calls[] = [$method, $params];

        return ['_' => $method, 'ok' => true];
    }
}
