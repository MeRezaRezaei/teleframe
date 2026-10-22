<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel;

use MeRezaRezaei\Teleframe\Control\RelayContext;
use MeRezaRezaei\Teleframe\Handler\InMemoryCache;
use MeRezaRezaei\Teleframe\Laravel\Services\RelayActiveAccount;
use MeRezaRezaei\Teleframe\Laravel\Services\RelayFactory;
use MeRezaRezaei\Teleframe\Laravel\Services\RelaySessionResolver;
use MeRezaRezaei\Teleframe\Laravel\Services\RelayVaultTerminalFactory;
use PHPUnit\Framework\TestCase;

/**
 * W-09 (ACTIVE account = one global switch): the active-context PSR-16 key is
 * tenant/peer-namespaced, so an account switch in tenant A must never
 * re-point tenant B — B's persisted context AND monotonic generation stay
 * byte-for-byte untouched, and a RelayVaultTerminalFactory wired for B does
 * not re-seat on A's switch. The legacy global surface (switchTo()/current()/
 * currentGeneration()) is preserved as BC. Plain PHP — InMemoryCache + a
 * resolver whose clientFactory records which account got built, so the
 * "no cross-scope re-seat" contract is proven end-to-end without a network.
 */
final class AccountStateW09Test extends TestCase
{
    public function test_two_tenants_switch_independently(): void
    {
        $active = new RelayActiveAccount(new InMemoryCache, new RelayFactory(new RelaySessionResolver));

        $active->switchFor('tenant:A', new RelayContext(accountId: 1, dcId: 2, label: 'alice-a'));
        $active->switchFor('tenant:B', new RelayContext(accountId: 2, dcId: 4, label: 'bob-b'));

        self::assertSame(1, $active->currentFor('tenant:A')?->accountId);
        self::assertSame(2, $active->currentFor('tenant:B')?->accountId);
        self::assertSame(1, $active->currentGenerationFor('tenant:A'));
        self::assertSame(1, $active->currentGenerationFor('tenant:B'));

        // A switches again → only A's context + generation move.
        $active->switchFor('tenant:A', new RelayContext(accountId: 3, dcId: 2, label: 'alice-b'));

        self::assertSame(3, $active->currentFor('tenant:A')?->accountId);
        self::assertSame('alice-b', $active->currentFor('tenant:A')?->label);
        self::assertSame(2, $active->currentGenerationFor('tenant:A'));

        self::assertSame(2, $active->currentFor('tenant:B')?->accountId, 'W-09: B context untouched by A switch');
        self::assertSame('bob-b', $active->currentFor('tenant:B')?->label);
        self::assertSame(1, $active->currentGenerationFor('tenant:B'), 'W-09: B generation untouched by A switch');
    }

    public function test_scoped_switch_does_not_touch_the_legacy_global_active(): void
    {
        $active = new RelayActiveAccount(new InMemoryCache, new RelayFactory(new RelaySessionResolver));

        $active->switchTo(new RelayContext(accountId: 7, dcId: 2, label: 'global'));
        self::assertSame(1, $active->currentGeneration(), 'the global switch sits at generation 1');

        $active->switchFor('tenant:B', new RelayContext(accountId: 2, dcId: 4, label: 'bob-b'));

        self::assertSame(
            7,
            $active->current()?->accountId,
            'BC: a scoped switch must not re-point the global ACTIVE context',
        );
        self::assertSame(1, $active->currentGeneration(), 'BC: a scoped switch does not bump the global generation');
        self::assertSame(1, $active->currentGenerationFor('tenant:B'));
    }

    public function test_scoped_terminal_factory_reseats_only_on_its_own_scope_generation(): void
    {
        $client11 = new VaultStubClient;
        $client22 = new VaultStubClient;
        $client33 = new VaultStubClient;
        $built = [];
        $factory = new RelayFactory(new RelaySessionResolver(
            vaultLoader: static fn (string $ref, int $dc): ?string => 'sess-'.$ref,
            clientFactory: function (string $session, int $dc) use (&$built, $client11, $client22, $client33): object {
                $account = (int) substr($session, 5);
                $built[] = $account;

                return match ($account) {
                    11 => $client11,
                    22 => $client22,
                    33 => $client33,
                    default => $client11,
                };
            },
        ));
        $active = new RelayActiveAccount(new InMemoryCache, $factory);

        $terminalA = new RelayVaultTerminalFactory($factory, $active, scope: 'tenant:A');
        $terminalB = new RelayVaultTerminalFactory($factory, $active, scope: 'tenant:B');

        $active->switchFor('tenant:A', new RelayContext(accountId: 11, dcId: 2, label: 'eve'));
        $active->switchFor('tenant:B', new RelayContext(accountId: 22, dcId: 4, label: 'zoe'));

        $dispatchA = $terminalA->dispatch();
        $dispatchB = $terminalB->dispatch();

        $dispatchA('messages.getDialogs', ['limit' => 5]);
        $dispatchB('messages.getDialogs', ['limit' => 5]);

        self::assertSame([11, 22], $built, 'each tenant seated its own account exactly once');
        self::assertSame(1, $terminalA->currentGeneration());
        self::assertSame(1, $terminalB->currentGeneration());
        self::assertSame([['messages.getDialogs', ['limit' => 5]]], $client11->calls);
        self::assertSame([['messages.getDialogs', ['limit' => 5]]], $client22->calls);

        // Tenant A switches → A's terminal re-seats, B's terminal must NOT.
        $active->switchFor('tenant:A', new RelayContext(accountId: 33, dcId: 2, label: 'eve2'));

        $dispatchB('messages.getDialogs', ['limit' => 3]);
        $dispatchA('messages.getDialogs', ['limit' => 3]);

        self::assertSame([11, 22, 33], $built, 'W-09: A re-seats onto 33, B is never rebuilt');
        self::assertSame([['messages.getDialogs', ['limit' => 5]], ['messages.getDialogs', ['limit' => 3]]], $client22->calls, 'W-09: tenant B kept serving on its own account');
        self::assertSame([['messages.getDialogs', ['limit' => 3]]], $client33->calls, 'A re-seated onto the new ACTIVE account');
        self::assertSame(2, $terminalA->currentGeneration(), 'A generation bumped by A switch only');
        self::assertSame(1, $terminalB->currentGeneration(), 'W-09: B generation untouched');
    }
}

/**
 * Fake relayed user-client (shape mirrors RelayVaultTerminalFactoryTest):
 * records every call() the RelayTerminal's default executor makes and
 * answers with a canned RPC result — no network, no session string.
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
