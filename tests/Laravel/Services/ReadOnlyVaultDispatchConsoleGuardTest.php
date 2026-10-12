<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel\Services;

use MeRezaRezaei\Teleframe\Control\RelayContext;
use MeRezaRezaei\Teleframe\Handler\InMemoryCache;
use MeRezaRezaei\Teleframe\Laravel\Services\ReadOnlyScope;
use MeRezaRezaei\Teleframe\Laravel\Services\RelayActiveAccount;
use MeRezaRezaei\Teleframe\Laravel\Services\RelayFactory;
use MeRezaRezaei\Teleframe\Laravel\Services\RelayReadOnlyGrants;
use MeRezaRezaei\Teleframe\Laravel\Services\RelaySessionResolver;
use MeRezaRezaei\Teleframe\Laravel\Services\RelayVaultTerminalFactory;
use PHPUnit\Framework\TestCase;

/**
 * C5 — the read-only guard must be armed in the CONSOLE, not only over HTTP.
 *
 * The guard's check was always correct and deny-by-default; what did not exist
 * was the SIGNAL that arms it in the one process that dispatches RPCs. The
 * only producer of a read-only frame was the HTTP route middleware
 * ScopeTelegramReadOnly, which never runs inside the long-lived
 * `teleframe:relay` daemon, so isReadOnly() was permanently false there and
 * every method reached the managed account. The absence of a test for the
 * console path is what let that ship: the guard was exercised by its own
 * HTTP-flavoured test and disarmed in production.
 *
 * These tests build the terminal EXACTLY the way RelayCommand does — a real
 * RelayVaultTerminalFactory over RelayFactory/RelayActiveAccount, a real
 * ReadOnlyScope that no HTTP middleware ever touched, and the per-connection
 * ticket grant the answerer threads in as the dispatch's third argument. No
 * session, no guard, no HTTP, no wire. The assertion is the one that matters:
 * a mutating method is REFUSED and a read-only method PASSES, with the scope
 * never armed by a request frame.
 */
final class ReadOnlyVaultDispatchConsoleGuardTest extends TestCase
{
    public function test_console_path_refuses_a_mutating_method_with_a_read_only_ticket(): void
    {
        $client = new VaultStubClient;
        [$factory, $active, $grants] = $this->consoleHarness($client);

        // The daemon's ticket validator: a ticket minted from a read-only
        // session redeems into a read-only grant for this connection.
        $grants->remember(11, 5, true);

        $dispatch = (new RelayVaultTerminalFactory(
            $factory,
            $active,
            readOnlyScope: new ReadOnlyScope,
            readOnlyGrants: $grants,
        ))->dispatch();

        $active->switchTo(new RelayContext(accountId: 11, dcId: 2, label: 'relay'));

        $refused = $dispatch('messages.sendMessage', ['peer_id' => 1, 'message' => 'hi'], ['account_id' => 11, 'user_id' => 5]);

        self::assertSame(
            ['_' => 'rpc_error', 'error_code' => 400, 'error_message' => 'READONLY_SCOPE_FORBIDDEN'],
            $refused,
            'the console daemon must refuse a write for a read-only connection — the exact gap that let this ship',
        );
        self::assertSame([], $client->calls, 'a refused write never reaches the vault client');
    }

    public function test_console_path_passes_a_read_only_method_for_the_same_connection(): void
    {
        $client = new VaultStubClient;
        [$factory, $active, $grants] = $this->consoleHarness($client);
        $grants->remember(11, 5, true);

        $dispatch = (new RelayVaultTerminalFactory(
            $factory,
            $active,
            readOnlyScope: new ReadOnlyScope,
            readOnlyGrants: $grants,
        ))->dispatch();
        $active->switchTo(new RelayContext(accountId: 11, dcId: 2, label: 'relay'));

        $result = $dispatch('messages.getDialogs', ['limit' => 5], ['account_id' => 11, 'user_id' => 5]);

        self::assertSame([['messages.getDialogs', ['limit' => 5]]], $client->calls);
        self::assertSame(['_' => 'messages.getDialogs', 'ok' => true], $result, 'the guard is a read-only guard, not a mute');
    }

    /**
     * The other half of the attack: even the methods that ARE locally answered
     * by the answerer must be refused by the guard itself, because any other
     * consumer of this dispatch inherits the classification.
     */
    public function test_console_path_refuses_identity_takeover_and_history_destruction(): void
    {
        $client = new VaultStubClient;
        [$factory, $active, $grants] = $this->consoleHarness($client);
        $grants->remember(11, 5, true);

        $dispatch = (new RelayVaultTerminalFactory(
            $factory,
            $active,
            readOnlyScope: new ReadOnlyScope,
            readOnlyGrants: $grants,
        ))->dispatch();
        $active->switchTo(new RelayContext(accountId: 11, dcId: 2, label: 'relay'));

        $attacks = [
            'messages.deleteMessages',
            'messages.forwardMessages',
            'account.updateUsername',
            'account.changePhone',
            'account.deleteAccount',
            'auth.exportLoginToken',
            'auth.exportAuthorization',
        ];

        foreach ($attacks as $method) {
            $result = $dispatch($method, [], ['account_id' => 11, 'user_id' => 5]);
            self::assertSame('rpc_error', $result['_'], $method.' must be refused on a read-only connection');
            self::assertSame('READONLY_SCOPE_FORBIDDEN', $result['error_message']);
        }

        self::assertSame([], $client->calls, 'no refused method reaches the vault client');
    }

    /**
     * Deny by default: a grant the daemon cannot determine — no recorded entry,
     * a malformed identity, a grant the answerer could not normalize — is
     * READ-ONLY. A guard that cannot decide must never decide "allow".
     */
    public function test_undeterminable_grant_fails_closed(): void
    {
        $client = new VaultStubClient;
        [$factory, $active, $grants] = $this->consoleHarness($client);

        $dispatch = (new RelayVaultTerminalFactory(
            $factory,
            $active,
            readOnlyScope: new ReadOnlyScope,
            readOnlyGrants: $grants,
        ))->dispatch();
        $active->switchTo(new RelayContext(accountId: 11, dcId: 2, label: 'relay'));

        $undeterminable = [
            'no grant at all' => null,
            'a grant for an identity that never redeemed a ticket' => ['account_id' => 99, 'user_id' => 5],
            'a grant with no account' => ['user_id' => 5],
            'a grant with an out-of-range account' => ['account_id' => 0, 'user_id' => 5],
        ];

        foreach ($undeterminable as $label => $grant) {
            $result = $dispatch('messages.sendMessage', ['peer_id' => 1], $grant);
            self::assertSame(
                ['_' => 'rpc_error', 'error_code' => 400, 'error_message' => 'READONLY_SCOPE_FORBIDDEN'],
                $result,
                $label.' must fail closed',
            );
        }

        self::assertSame([], $client->calls);
    }

    /**
     * A ticket minted before the read-only flag existed, or by a caller that
     * declined to state an intent, is UNDETERMINED — and undetermined means
     * read-only. This is the migration path: an in-flight ticket from before
     * the upgrade must not become a write-capable one.
     */
    public function test_ticket_without_a_stated_intent_is_read_only(): void
    {
        $client = new VaultStubClient;
        [$factory, $active, $grants] = $this->consoleHarness($client);

        // RelayCommand's validator shape: `$grant['read_only'] ?? true`.
        $undeterminedGrant = ['account_id' => 11, 'user_id' => 5, 'read_only' => null];
        $grants->remember($undeterminedGrant['account_id'], $undeterminedGrant['user_id'], $undeterminedGrant['read_only'] ?? true);

        $dispatch = (new RelayVaultTerminalFactory(
            $factory,
            $active,
            readOnlyScope: new ReadOnlyScope,
            readOnlyGrants: $grants,
        ))->dispatch();
        $active->switchTo(new RelayContext(accountId: 11, dcId: 2, label: 'relay'));

        $refused = $dispatch('messages.sendMessage', [], ['account_id' => 11, 'user_id' => 5]);

        self::assertSame('rpc_error', $refused['_']);
        self::assertSame([], $client->calls);
    }

    /**
     * Per-CONNECTION, not per-process: two sockets on the same terminal must
     * not share or leak scope state. A read-only ticket leaves its neighbour
     * fully capable, and the reverse.
     */
    public function test_two_concurrent_connections_do_not_share_scope_state(): void
    {
        $client = new VaultStubClient;
        [$factory, $active, $grants] = $this->consoleHarness($client);

        // Connection A redeemed a READ-ONLY ticket; connection B redeemed a
        // full-power one, for a different account and a different user.
        $grants->remember(11, 5, true);
        $grants->remember(12, 6, false);

        $dispatch = (new RelayVaultTerminalFactory(
            $factory,
            $active,
            readOnlyScope: new ReadOnlyScope,
            readOnlyGrants: $grants,
        ))->dispatch();
        $active->switchTo(new RelayContext(accountId: 11, dcId: 2, label: 'relay'));

        $writeA = ['peer_id' => 1, 'message' => 'from A'];
        $writeB = ['peer_id' => 2, 'message' => 'from B'];
        $grantA = ['account_id' => 11, 'user_id' => 5];
        $grantB = ['account_id' => 12, 'user_id' => 6];

        // Interleave them: A is refused, B is NOT, A is refused again.
        $a1 = $dispatch('messages.sendMessage', $writeA, $grantA);
        $b1 = $dispatch('messages.sendMessage', $writeB, $grantB);
        $a2 = $dispatch('messages.sendMessage', $writeA, $grantA);

        self::assertSame('READONLY_SCOPE_FORBIDDEN', $a1['error_message'], 'connection A is read-only');
        self::assertSame('READONLY_SCOPE_FORBIDDEN', $a2['error_message'], 'and stays read-only after its neighbour wrote');
        self::assertSame(['_' => 'messages.sendMessage', 'ok' => true], $b1, 'connection B holds a full-power grant and is unaffected by A');
        self::assertSame([['messages.sendMessage', $writeB]], $client->calls, 'only connection B ever reached the vault client');
    }

    /**
     * The read-only intent is MONOTONIC toward the restrictive: a read-write
     * ticket minted later for an identity already established read-only can
     * never widen the running connection (a race must not loosen the guard).
     */
    public function test_a_later_read_write_grant_cannot_widen_a_read_only_identity(): void
    {
        $client = new VaultStubClient;
        [$factory, $active, $grants] = $this->consoleHarness($client);

        $grants->remember(11, 5, true);
        $grants->remember(11, 5, false);

        $dispatch = (new RelayVaultTerminalFactory(
            $factory,
            $active,
            readOnlyScope: new ReadOnlyScope,
            readOnlyGrants: $grants,
        ))->dispatch();
        $active->switchTo(new RelayContext(accountId: 11, dcId: 2, label: 'relay'));

        $result = $dispatch('messages.sendMessage', [], ['account_id' => 11, 'user_id' => 5]);

        self::assertSame('READONLY_SCOPE_FORBIDDEN', $result['error_message']);
        self::assertSame([], $client->calls);
    }

    /**
     * Disarm: a closed connection leaves nothing behind, and forgetting can
     * only ever move an identity to UNDETERMINED — which is still read-only.
     */
    public function test_forgetting_a_connection_leaves_it_read_only_not_writable(): void
    {
        $client = new VaultStubClient;
        [$factory, $active, $grants] = $this->consoleHarness($client);
        $grants->remember(11, 5, false);
        self::assertSame(1, $grants->count());

        $grants->forget(11, 5);

        self::assertSame(0, $grants->count(), 'the grant is disarmed');

        $dispatch = (new RelayVaultTerminalFactory(
            $factory,
            $active,
            readOnlyScope: new ReadOnlyScope,
            readOnlyGrants: $grants,
        ))->dispatch();
        $active->switchTo(new RelayContext(accountId: 11, dcId: 2, label: 'relay'));

        $result = $dispatch('messages.sendMessage', [], ['account_id' => 11, 'user_id' => 5]);

        self::assertSame('READONLY_SCOPE_FORBIDDEN', $result['error_message'], 'disarming must never open the guard back up');
        self::assertSame([], $client->calls);
    }

    /**
     * A host that wired NO registry gets one anyway, already fail-closed: the
     * guard is live by DEFAULT rather than depending on an opt-in binding (the
     * reason it was disarmed in the first place).
     */
    public function test_the_guard_is_armed_by_default_without_an_injected_registry(): void
    {
        $client = new VaultStubClient;
        [$factory, $active] = $this->consoleHarness($client);

        $terminal = new RelayVaultTerminalFactory(
            $factory,
            $active,
            readOnlyScope: new ReadOnlyScope,
        );
        $dispatch = $terminal->dispatch();
        $active->switchTo(new RelayContext(accountId: 11, dcId: 2, label: 'relay'));

        $result = $dispatch('messages.sendMessage', [], ['account_id' => 11, 'user_id' => 5]);

        self::assertSame('READONLY_SCOPE_FORBIDDEN', $result['error_message'], 'an unwired terminal still fails closed');
        self::assertSame([], $client->calls);
        self::assertInstanceOf(RelayReadOnlyGrants::class, $terminal->readOnlyGrants(), 'the factory built its own fail-closed registry');
    }

    /**
     * The documented opt-out is the ONLY way to turn the guard off, and it is
     * a constructor flag — never an accident of wiring.
     */
    public function test_the_enforce_opt_out_disables_the_grant_derived_guard(): void
    {
        $client = new VaultStubClient;
        [$factory, $active, $grants] = $this->consoleHarness($client);
        $grants->remember(11, 5, true);

        $dispatch = (new RelayVaultTerminalFactory(
            $factory,
            $active,
            readOnlyScope: new ReadOnlyScope,
            readOnlyGrants: $grants,
            enforceReadOnlyGrant: false,
        ))->dispatch();
        $active->switchTo(new RelayContext(accountId: 11, dcId: 2, label: 'relay'));

        $result = $dispatch('messages.sendMessage', [], ['account_id' => 11, 'user_id' => 5]);

        self::assertSame(['_' => 'messages.sendMessage', 'ok' => true], $result, 'the explicit opt-out is honoured (teleframe.relay.read_only.enforce=false)');
        self::assertSame([['messages.sendMessage', []]], $client->calls);
    }

    /**
     * The HTTP frame and the per-connection grant compose: a host scope
     * opened by the middleware is still honoured when the ticket carries a
     * full-power grant.
     */
    public function test_the_host_scope_still_overrides_a_full_power_ticket(): void
    {
        $client = new VaultStubClient;
        [$factory, $active, $grants] = $this->consoleHarness($client);
        $grants->remember(11, 5, false);

        $scope = new ReadOnlyScope;
        $dispatch = (new RelayVaultTerminalFactory(
            $factory,
            $active,
            readOnlyScope: $scope,
            readOnlyGrants: $grants,
        ))->dispatch();
        $active->switchTo(new RelayContext(accountId: 11, dcId: 2, label: 'relay'));

        $scope->begin();
        $result = $dispatch('messages.sendMessage', [], ['account_id' => 11, 'user_id' => 5]);

        self::assertSame('READONLY_SCOPE_FORBIDDEN', $result['error_message'], 'a read-only host scope is never widened by a ticket');
        self::assertSame([], $client->calls);
    }

    /**
     * @return array{0: RelayFactory, 1: RelayActiveAccount, 2: RelayReadOnlyGrants}
     */
    private function consoleHarness(VaultStubClient $client): array
    {
        $factory = new RelayFactory(new RelaySessionResolver(
            vaultLoader: static fn (string $ref, int $dc): ?string => 'sess-'.$ref,
            clientFactory: static fn (string $session, int $dc): object => $client,
        ));
        $active = new RelayActiveAccount(new InMemoryCache, $factory);

        return [$factory, $active, new RelayReadOnlyGrants];
    }
}
