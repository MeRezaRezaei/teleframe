<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel\Services;

use MeRezaRezaei\Teleframe\Control\RelayContext;
use MeRezaRezaei\Teleframe\Handler\InMemoryCache;
use MeRezaRezaei\Teleframe\Laravel\Services\ReadOnlyScope;
use MeRezaRezaei\Teleframe\Laravel\Services\RelayActiveAccount;
use MeRezaRezaei\Teleframe\Laravel\Services\RelayDispatchContext;
use MeRezaRezaei\Teleframe\Laravel\Services\RelayFactory;
use MeRezaRezaei\Teleframe\Laravel\Services\RelaySessionResolver;
use MeRezaRezaei\Teleframe\Laravel\Services\RelayVaultTerminalFactory;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * RelayVaultTerminalFactory: the VAULT DISPATCH callable VaultMtprotoAnswerer
 * consumes. Building it must stay fully lazy (no client, no dial), each
 * dispatch runs the outbound RelayMessage through the AccountRelay onion
 * into RelayTerminal's default executor against the ACTIVE vault account,
 * and a RelayActiveAccount switch bumps the generation so the next dispatch
 * re-seats onto the new account. Plain PHP — real RelayActiveAccount over
 * InMemoryCache + RelayFactory over a resolver whose clientFactory records
 * calls, mirroring RelayFactoryTest's fake scope shape.
 */
final class RelayVaultTerminalFactoryTest extends TestCase
{
    public function test_dispatch_drives_relay_terminal_against_the_vault_account_scope(): void
    {
        $client = new VaultStubClient;
        $built = 0;
        [$factory, $active] = $this->relayHarness($client, $client, $built);

        $active->switchTo(new RelayContext(accountId: 11, dcId: 2, label: 'eve'));
        $vaultTerminal = new RelayVaultTerminalFactory($factory, $active);
        $dispatch = $vaultTerminal->dispatch();

        self::assertSame(0, $built, 'building the factory never builds a client or dials');

        $result = $dispatch('messages.getDialogs', ['limit' => 10]);

        self::assertSame([['messages.getDialogs', ['limit' => 10]]], $client->calls, 'the dispatch drives the bound scope client');
        self::assertSame(['_' => 'messages.getDialogs', 'ok' => true], $result, 'the RPC result array comes back');
        self::assertSame(1, $vaultTerminal->currentGeneration());
    }

    public function test_generation_change_reseats_dispatch_onto_the_switched_account_without_construct_dial(): void
    {
        $clientA = new VaultStubClient;
        $clientB = new VaultStubClient;
        $built = 0;
        [$factory, $active] = $this->relayHarness($clientA, $clientB, $built);

        $active->switchTo(new RelayContext(accountId: 11, dcId: 2, label: 'eve'));
        $vaultTerminal = new RelayVaultTerminalFactory($factory, $active);
        $dispatch = $vaultTerminal->dispatch();

        self::assertSame(0, $built, 'constructing the factory must never build a client or dial');

        $dispatch('messages.getDialogs', ['limit' => 5]);
        self::assertSame([['messages.getDialogs', ['limit' => 5]]], $clientA->calls);
        self::assertSame([], $clientB->calls);
        $builtAfterFirst = $built;
        self::assertSame(1, $builtAfterFirst, 'the first dispatch resolves the ACTIVE scope exactly once');

        $active->switchTo(new RelayContext(accountId: 22, dcId: 4, label: 'zoe'));
        self::assertSame($builtAfterFirst, $built, 'switchTo() itself reads no vault and dials no client');

        $result = $dispatch('messages.getDialogs', ['limit' => 5]);

        self::assertSame([['messages.getDialogs', ['limit' => 5]]], $clientB->calls, 'the generation change re-seats the dispatch onto account B');
        self::assertSame([['messages.getDialogs', ['limit' => 5]]], $clientA->calls, 'account A served only the pre-switch dispatch');
        self::assertSame($builtAfterFirst + 1, $built, 're-seating resolves the switched account exactly once, on dispatch');
        self::assertSame(['_' => 'messages.getDialogs', 'ok' => true], $result);
    }

    public function test_current_generation_increments_after_each_switch(): void
    {
        [$factory, $active] = $this->relayHarness(new VaultStubClient, new VaultStubClient);
        $vaultTerminal = new RelayVaultTerminalFactory($factory, $active);

        self::assertSame(0, $vaultTerminal->currentGeneration(), 'nothing switched yet');
        self::assertSame(0, $active->currentGeneration());

        $active->switchTo(new RelayContext(accountId: 7, dcId: 4, label: 'grace'));

        self::assertSame(1, $vaultTerminal->currentGeneration(), 'the factory mirrors the stored generation');
        self::assertSame(1, $active->currentGeneration(), 'the first switch starts the generation at 1');

        $active->switchTo(new RelayContext(accountId: 8, dcId: 2, label: 'henry'));

        self::assertSame(2, $vaultTerminal->currentGeneration(), 'each switch bumps the generation');
        self::assertSame(2, $active->currentGeneration());
    }

    public function test_dispatch_throws_when_no_active_account_is_switched(): void
    {
        $built = 0;
        [$factory, $active] = $this->relayHarness(new VaultStubClient, new VaultStubClient, $built);
        $dispatch = (new RelayVaultTerminalFactory($factory, $active))->dispatch();

        $this->expectException(RuntimeException::class);

        $dispatch('messages.getDialogs', []);
        self::assertSame(0, $built);
    }

    public function test_rsa_private_key_returns_the_configured_pem(): void
    {
        $pem = "-----BEGIN RSA PRIVATE KEY-----\nMIIEoQ...\n-----END RSA PRIVATE KEY-----\n";
        [$factory, $active] = $this->relayHarness(new VaultStubClient, new VaultStubClient);
        $vaultTerminal = new RelayVaultTerminalFactory($factory, $active, null, static fn (string $key, mixed $default = null): mixed => $key === 'teleframe.relay.vault_terminal.rsa_key' ? $pem : $default);

        self::assertSame($pem, $vaultTerminal->rsaPrivateKey());
    }

    public function test_rsa_private_key_throws_when_unset(): void
    {
        [$factory, $active] = $this->relayHarness(new VaultStubClient, new VaultStubClient);
        $vaultTerminal = new RelayVaultTerminalFactory($factory, $active, null, static fn (string $key, mixed $default = null): mixed => $default);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('teleframe.relay.vault_terminal.rsa_key is unset');

        $vaultTerminal->rsaPrivateKey();
    }

    public function test_dispatch_middleware_sees_the_ticket_context_and_can_abort_without_the_terminal(): void
    {
        $client = new VaultStubClient;
        [$factory, $active] = $this->relayHarness($client, $client);
        $active->switchTo(new RelayContext(accountId: 11, dcId: 2, label: 'eve'));

        $seen = [];
        $middleware = static function (string $method, array $params, RelayDispatchContext $ctx, callable $next) use (&$seen): array {
            $seen[] = [$method, $ctx->accountId, $ctx->userId];

            return ['_' => 'rpc_error', 'error_code' => 403, 'error_message' => 'ABORTED_BY_POLICY'];
        };

        $dispatch = (new RelayVaultTerminalFactory($factory, $active, null, null, null, null, [$middleware]))->dispatch();
        $result = $dispatch('messages.sendMessage', ['peer_id' => 1], ['account_id' => 11, 'user_id' => 5]);

        self::assertSame([['messages.sendMessage', 11, 5]], $seen, 'the middleware sees the ticket-granted account + user');
        self::assertSame(['_' => 'rpc_error', 'error_code' => 403, 'error_message' => 'ABORTED_BY_POLICY'], $result);
        self::assertSame([], $client->calls, 'an aborting middleware never reaches the terminal');
    }

    public function test_dispatch_middleware_delegation_reaches_the_terminal_and_read_only_default_survives(): void
    {
        $client = new VaultStubClient;
        [$factory, $active] = $this->relayHarness($client, $client);
        $active->switchTo(new RelayContext(accountId: 11, dcId: 2, label: 'eve'));

        $ctxSeen = [];
        $middleware = static function (string $method, array $params, RelayDispatchContext $ctx, callable $next) use (&$ctxSeen): array {
            $ctxSeen[] = [$ctx->accountId, $ctx->userId];

            return $next($method, $params);
        };
        $scope = new ReadOnlyScope;

        $dispatch = (new RelayVaultTerminalFactory($factory, $active, null, null, $scope, null, [$middleware]))->dispatch();
        $result = $dispatch('messages.getDialogs', ['limit' => 3], ['account_id' => 11, 'user_id' => 'a-1']);

        self::assertSame([[11, 'a-1']], $ctxSeen, 'the middleware sees the identity BEFORE the terminal runs');
        self::assertSame([['messages.getDialogs', ['limit' => 3]]], $client->calls, 'delegation reaches the terminal exactly once');
        self::assertSame(['_' => 'messages.getDialogs', 'ok' => true], $result);

        // A marked read-only scope still refuses writes THROUGH the onion —
        // the ReadOnlyVaultDispatch default applies further in, exactly like
        // the no-middleware path.
        $scope->begin();
        $refused = $dispatch('messages.sendMessage', ['peer_id' => 1], ['account_id' => 11, 'user_id' => 'a-1']);
        self::assertSame(
            ['_' => 'rpc_error', 'error_code' => 400, 'error_message' => 'READONLY_SCOPE_FORBIDDEN'],
            $refused,
        );
        self::assertSame([['messages.getDialogs', ['limit' => 3]]], $client->calls, 'a refused write never reaches the terminal through the onion');
        $scope->end();

        // Unmarked again: reads pass through the onion to the terminal.
        $passed = $dispatch('messages.getDialogs', ['limit' => 1]);
        self::assertSame([['messages.getDialogs', ['limit' => 1]]], array_slice($client->calls, 1), 'an unmarked scope passes reads through the onion');
        self::assertSame(['_' => 'messages.getDialogs', 'ok' => true], $passed);
    }

    /**
     * @return array{0: RelayFactory, 1: RelayActiveAccount}
     */
    private function relayHarness(VaultStubClient $clientA, VaultStubClient $clientB, int &$built = 0): array
    {
        $factory = new RelayFactory(new RelaySessionResolver(
            vaultLoader: static fn (string $ref, int $dc): ?string => 'sess-'.$ref,
            clientFactory: function (string $session, int $dc) use (&$built, $clientA, $clientB): object {
                $built++;

                return $session === 'sess-11' || $session === 'sess-7' ? $clientA : $clientB;
            },
        ));
        $active = new RelayActiveAccount(new InMemoryCache, $factory);

        return [$factory, $active];
    }
}

/**
 * Fake relayed user-client (the shape RelayFactoryTest / VaultMtprotoAnswerer
 * fakes use): records every call() the RelayTerminal's default executor
 * makes and answers with a canned RPC result, so the factory-bound dispatch
 * can be proven end-to-end without a network or any session string.
 */
final class VaultStubClient
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
