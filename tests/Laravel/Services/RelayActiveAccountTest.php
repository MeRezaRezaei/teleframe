<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel\Services;

use MeRezaRezaei\Teleframe\Control\RelayContext;
use MeRezaRezaei\Teleframe\Control\RelayTerminal;
use MeRezaRezaei\Teleframe\Handler\InMemoryCache;
use MeRezaRezaei\Teleframe\Laravel\Services\RelayActiveAccount;
use MeRezaRezaei\Teleframe\Laravel\Services\RelayFactory;
use MeRezaRezaei\Teleframe\Laravel\Services\RelaySessionResolver;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * RelayActiveAccount (Wave E): switchTo() persists the opaque active context
 * under `relay:active:account` and returns a fresh, fully lazy RelayTerminal
 * (no vault read, no client build, no dial); current() round-trips it back.
 * Plain PHP — InMemoryCache + a resolver whose factories would throw if
 * touched, so the "never dials on switch" contract is proven directly.
 */
final class RelayActiveAccountTest extends TestCase
{
    public function test_switch_to_persists_the_active_context_and_stays_fully_lazy(): void
    {
        $resolved = 0;
        $active = new RelayActiveAccount(
            new InMemoryCache,
            new RelayFactory(new RelaySessionResolver(
                vaultLoader: static function (string $ref, int $dc): ?string {
                    return null;
                },
                clientFactory: function (string $session, int $dc) use (&$resolved): object {
                    $resolved++;

                    throw new RuntimeException('relay must never dial during a switch');
                },
            )),
        );

        $terminal = $active->switchTo(new RelayContext(accountId: 11, dcId: 2, label: 'eve'));

        self::assertInstanceOf(RelayTerminal::class, $terminal);
        self::assertFalse($terminal->resolved(), 'switchTo() must not resolve the scope');
        self::assertSame(0, $resolved, 'switching must never build a client or dial');

        $current = $active->current();
        self::assertNotNull($current);
        self::assertSame(11, $current->accountId);
        self::assertSame(2, $current->dcId);
        self::assertSame('eve', $current->label);
    }

    public function test_current_is_null_when_nothing_switched(): void
    {
        $active = new RelayActiveAccount(new InMemoryCache, new RelayFactory(new RelaySessionResolver));

        self::assertNull($active->current());
    }

    public function test_current_round_trips_a_real_context(): void
    {
        $active = new RelayActiveAccount(new InMemoryCache, new RelayFactory(new RelaySessionResolver));

        $active->switchTo(new RelayContext(accountId: 7, dcId: 4, label: 'grace', liveEndpoint: 'wss://example.test'));

        $current = $active->current();
        self::assertNotNull($current);
        self::assertSame(7, $current->accountId);
        self::assertSame(4, $current->dcId);
        self::assertSame('grace', $current->label);
    }

    public function test_switch_replaces_the_active_context_and_binds_a_fresh_terminal(): void
    {
        $active = new RelayActiveAccount(new InMemoryCache, new RelayFactory(new RelaySessionResolver));

        $active->switchTo(new RelayContext(accountId: 7, dcId: 4, label: 'grace'));
        $terminal = $active->switchTo(new RelayContext(accountId: 8, dcId: 2, label: 'henry'));

        self::assertInstanceOf(RelayTerminal::class, $terminal);
        self::assertFalse($terminal->resolved());
        self::assertSame(8, $active->current()?->accountId, 'the LATEST switch wins');
        self::assertSame('henry', $active->current()?->label);
    }

    public function test_malformed_active_payload_is_treated_as_no_active_account(): void
    {
        $cache = new InMemoryCache;
        $cache->set('relay:active:account', 'not-json{{{');
        $active = new RelayActiveAccount($cache, new RelayFactory(new RelaySessionResolver));

        self::assertNull($active->current());
    }
}
