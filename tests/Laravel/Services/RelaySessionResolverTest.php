<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel\Services;

use MeRezaRezaei\Teleframe\Control\RelayContext;
use MeRezaRezaei\Teleframe\Laravel\Services\RelayFactory;
use MeRezaRezaei\Teleframe\Laravel\Services\RelayScope;
use MeRezaRezaei\Teleframe\Laravel\Services\RelaySessionResolver;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use UnexpectedValueException;

/**
 * RelaySessionResolver: vault session string → live RelayScope behind the
 * AccountRelay seam. Offline by construction — vault loader and client
 * factory are injected fakes; no Laravel container, cache, or DB, and no
 * dialing. The session string is asserted to flow to the client factory
 * and to be absent from every observable output of the resolver.
 */
final class RelaySessionResolverTest extends TestCase
{
    public function test_for_account_builds_scope_with_expected_dc_and_client(): void
    {
        $client = new stdClass;
        $resolver = new RelaySessionResolver(
            vaultLoader: static fn (string $ref, int $dc): ?string => 'fake-session-for-42',
            clientFactory: static fn (string $session, int $dc): object => $client,
        );

        $scope = $resolver->forAccount(new RelayContext(accountId: 42, dcId: 5, label: 'alice'));

        self::assertInstanceOf(RelayScope::class, $scope);
        self::assertSame(5, $scope->dcId);
        self::assertSame($client, $scope->client);
        self::assertFalse($scope->connected, 'a freshly built scope is never marked connected');
    }

    public function test_account_id_and_dc_flow_from_context_to_vault_loader(): void
    {
        $seenRef = null;
        $seenDc = null;
        $resolver = new RelaySessionResolver(
            vaultLoader: function (string $ref, int $dc) use (&$seenRef, &$seenDc): ?string {
                $seenRef = $ref;
                $seenDc = $dc;

                return 'ctx-flow-session';
            },
            clientFactory: static fn (string $session, int $dc): object => new stdClass,
        );

        $resolver->forAccount(new RelayContext(accountId: 123, dcId: 7, label: 'bob'));

        self::assertSame('123', $seenRef);
        self::assertSame(7, $seenDc);
    }

    public function test_session_string_flows_to_client_factory_and_is_never_echoed(): void
    {
        $secret = 'SUPER-SECRET-VAULT-SESSION';
        $seenSession = null;
        $resolver = new RelaySessionResolver(
            vaultLoader: static fn (string $ref, int $dc): ?string => $secret,
            clientFactory: function (string $session, int $dc) use (&$seenSession): object {
                $seenSession = $session;

                return new stdClass;
            },
        );

        $scope = $resolver->forAccount(new RelayContext(accountId: 1, dcId: 2, label: 'carol'));

        self::assertSame($secret, $seenSession, 'the exact decrypted session string reaches the client factory');
        self::assertStringNotContainsString($secret, var_export($scope, true), 'RelayScope must never carry/echo the session string');
        $scope->connect();
        self::assertStringNotContainsString($secret, var_export($scope, true), 'connect() is a no-op marker and must not leak the session');
    }

    public function test_scope_is_lazy_until_account_relay_scope_is_called(): void
    {
        $built = 0;
        $resolver = new RelaySessionResolver(
            vaultLoader: static fn (string $ref, int $dc): ?string => 'lazy-session',
            clientFactory: function (string $session, int $dc) use (&$built): object {
                $built++;

                return new stdClass;
            },
        );
        $factory = new RelayFactory($resolver);
        $relay = $factory->forAccount(new RelayContext(accountId: 7, dcId: 3, label: 'dave'));

        self::assertSame(0, $built, 'building the relay must not resolve the scope');
        $relay->scope();
        self::assertSame(1, $built, 'scope() resolves lazily on first call');
        $relay->scope();
        self::assertSame(2, $built, 'every scope() call re-resolves through the resolver');
    }

    public function test_missing_vault_session_throws_without_echoing_any_credential(): void
    {
        $resolver = new RelaySessionResolver(
            vaultLoader: static fn (string $ref, int $dc): ?string => null,
            clientFactory: static fn (string $session, int $dc): object => new stdClass,
        );

        try {
            $resolver->forAccount(new RelayContext(accountId: 9, dcId: 4, label: 'erin'));
            self::fail('expected a RuntimeException when the vault holds no session');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('no vault session', $e->getMessage());
            self::assertStringNotContainsString('SUPER', $e->getMessage(), 'the failure must not echo any credential-shaped text');
        }
    }

    public function test_non_object_client_from_factory_throws(): void
    {
        $resolver = new RelaySessionResolver(
            vaultLoader: static fn (string $ref, int $dc): ?string => 'non-object-session',
            clientFactory: function (string $session, int $dc) {
                return 'not-an-object';
            },
        );

        $this->expectException(UnexpectedValueException::class);

        $resolver->forAccount(new RelayContext(accountId: 5, dcId: 1, label: 'frank'));
    }
}
