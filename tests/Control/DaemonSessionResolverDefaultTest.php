<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Control;

use MeRezaRezaei\Teleframe\Core\MTProto\SessionData;
use MeRezaRezaei\Teleframe\Laravel\Console\DaemonCommand;
use MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider;
use Orchestra\Testbench\TestCase as TestbenchTestCase;

/**
 * S1 — the daemon's DEFAULT session resolver.
 *
 * DaemonCommand::SESSION_RESOLVER_KEY was declared but never bound by the
 * package, so every tf_accounts row without a config-seed session string was
 * silently skipped and the only working setup demanded a credential-bearing
 * env var. The provider now binds a default that reads the vault's
 * `encrypted` session column through TelegramAccount — the loader approach
 * RelaySessionResolver::defaultVaultLoader() already documents — and only
 * when the host has not bound its own.
 *
 * No database, no credentials: the unknown and undecodable refs both take the
 * null path, which is what a first release with no migrated vault must see.
 */
final class DaemonSessionResolverDefaultTest extends TestbenchTestCase
{
    protected function getApplicationBasePath()
    {
        return dirname(__DIR__, 2);
    }

    protected function getPackageProviders($app): array
    {
        return [TeleframeServiceProvider::class];
    }

    /**
     * The public bind key is populated by a bare boot — a host that only added
     * an account row to the database now gets a dialing daemon.
     */
    public function test_default_session_resolver_is_bound_and_callable(): void
    {
        self::assertTrue(
            $this->app->bound(DaemonCommand::SESSION_RESOLVER_KEY),
            'the provider binds teleclient.daemon.session-resolver by default',
        );

        self::assertIsCallable(
            $this->app->make(DaemonCommand::SESSION_RESOLVER_KEY),
            'the default resolver is a callable(string): ?SessionData',
        );
    }

    /**
     * The default answers null — never throws — for an account that cannot
     * exist, for a ref it cannot name a row by, and on a host whose vault
     * table was never migrated (the daemon reads an unmigrated registry as
     * "no accounts", so the resolver must degrade the same way).
     */
    public function test_default_resolver_returns_null_for_unknown_account_ref(): void
    {
        $resolver = $this->app->make(DaemonCommand::SESSION_RESOLVER_KEY);
        self::assertIsCallable($resolver);

        self::assertNull($resolver('424242'), 'unknown numeric account id resolves to null');
        self::assertNull($resolver('vault:424242'), 'unknown scheme-prefixed id resolves to null');
        self::assertNull($resolver('opaque-host-ref'), 'a ref the default cannot decode resolves to null, with no DB hit');
    }

    /**
     * The default is bound with bindIf, so a host resolver survives. Proven by
     * running the provider's own register() against an app that ALREADY has
     * the key bound: the host callable must still be the one in effect.
     */
    public function test_host_supplied_resolver_is_not_clobbered_by_the_default(): void
    {
        $hostResolver = static fn (string $sessionRef): ?SessionData => new SessionData(2, 'host-owned-auth-key');
        $this->app->bind(DaemonCommand::SESSION_RESOLVER_KEY, static fn (): callable => $hostResolver);

        // force = true: re-run register() even though the provider is already
        // registered, which is exactly the "host bound first" collision.
        $this->app->register(new TeleframeServiceProvider($this->app), true);

        $resolved = $this->app->make(DaemonCommand::SESSION_RESOLVER_KEY);
        self::assertIsCallable($resolved);
        $session = $resolved('vault:7');
        self::assertInstanceOf(SessionData::class, $session, 'the host resolver answered');
        self::assertSame('host-owned-auth-key', $session->authKey, 'the package default did not replace the host binding');
    }
}
