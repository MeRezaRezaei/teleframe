<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel\Services;

use Illuminate\Support\Facades\Cache;
use MeRezaRezaei\Teleframe\Laravel\Services\RelayTicketBroker;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;

/**
 * One-shot redeem semantics behind the /apiws auth gate (finding W-11):
 * a ticket must die with its first redeem, so a racing -- or sequential --
 * second redemption of the same ticket observes null. The historical
 * get-then-delete read model left a TOCTOU window a second authenticated
 * socket could slip through; the consume is now a single atomic operation
 * on a Redis-backed cache (GET+DEL via eval) and best-effort elsewhere.
 *
 * This runs against the array cache (the REAL broker + the real store
 * plumbing, no HTTP), proving the exactly-once contract. The temporary
 * feel of the TTL-less ArrayStore keeps the test deterministic; the Redis
 * atomic branch is the same code path exercised by the dashboard API tests
 * in production deployments.
 */
final class RelayTicketBrokerW11Test extends TestCase
{
    // vendor/ is a symlink to the main tree, so vendor/autoload.php resolves
    // RelayTicketBroker to the main tree's unfixed src/. Pin this tree's
    // sources before any test body autoloads the class.
    public static function setUpBeforeClass(): void
    {
        require dirname(__DIR__, 3).'/tests/Schema/FixesAutoload.php';
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('cache.default', 'array');
        $app['config']->set('teleframe.telegram_web.ticket_ttl_minutes', 10);
    }

    public function test_redeem_returns_the_account_then_dies_with_the_ticket(): void
    {
        $broker = new RelayTicketBroker(Cache::store());
        $minted = $broker->mint(42);

        self::assertSame(42, $broker->redeem($minted['ticket']), 'the first redeem must yield the bound account');

        // W-11: the very next redeem of the SAME ticket is null — the ticket
        // is gone, so a second socket can never authenticate with it.
        self::assertNull($broker->redeem($minted['ticket']), 'a ticket may never redeem twice');
        self::assertNull($broker->redeem($minted['ticket']), 'and stays null however many times it is tried');
    }

    public function test_redeem_removes_the_cached_ticket(): void
    {
        $broker = new RelayTicketBroker(Cache::store());
        $minted = $broker->mint(7);

        self::assertTrue(Cache::has('telegram_relay_ticket:'.$minted['ticket']));
        self::assertSame(7, $broker->redeem($minted['ticket']));
        self::assertFalse(Cache::has('telegram_relay_ticket:'.$minted['ticket']), 'the cached entry must be consumed, not left behind');
    }

    public function test_redeem_of_unknown_or_empty_tickets_is_null(): void
    {
        $broker = new RelayTicketBroker(Cache::store());

        self::assertNull($broker->redeem('no-such-ticket-anywhere'));
        self::assertNull($broker->redeem(''));
    }

    public function test_redeem_identity_returns_account_and_bound_user_then_dies(): void
    {
        $broker = new RelayTicketBroker(Cache::store());
        $minted = $broker->mint(42, 5);

        self::assertSame(42, $minted['account_id']);
        self::assertSame(5, $minted['user_id'], 'the minted ticket carries the bound Laravel user');
        self::assertSame(['account_id' => 42, 'user_id' => 5], $broker->redeemIdentity($minted['ticket']));

        self::assertNull($broker->redeemIdentity($minted['ticket']), 'the identity redeem is exactly-once too');
        self::assertNull($broker->redeem($minted['ticket']), 'and the legacy account redeem of the consumed ticket observes null');
    }

    public function test_redeem_identity_without_a_bound_user_binds_none(): void
    {
        $broker = new RelayTicketBroker(Cache::store());
        $identityMinted = $broker->mint(7);

        self::assertNull($identityMinted['user_id'], 'a legacy mint binds no user');
        self::assertSame(['account_id' => 7, 'user_id' => null], $broker->redeemIdentity($identityMinted['ticket']), 'the identity view of a user-less ticket binds none');

        $legacyMinted = $broker->mint(7);
        self::assertSame(7, $broker->redeem($legacyMinted['ticket']), 'legacy redeem() still returns the bare account id');
    }

    public function test_redeem_identity_of_unknown_or_empty_tickets_is_null(): void
    {
        $broker = new RelayTicketBroker(Cache::store());

        self::assertNull($broker->redeemIdentity('no-such-ticket-anywhere'));
        self::assertNull($broker->redeemIdentity(''));
    }
}
