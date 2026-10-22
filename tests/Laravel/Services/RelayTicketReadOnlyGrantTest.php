<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel\Services;

use Illuminate\Support\Facades\Cache;
use MeRezaRezaei\Teleframe\Laravel\Services\RelayReadOnlyGrants;
use MeRezaRezaei\Teleframe\Laravel\Services\RelayTicketBroker;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;

/**
 * C5, mint half: the read-only intent must travel ON THE TICKET.
 *
 * The relay daemon has no HTTP request, so the only read-only source that
 * exists in the process that dispatches RPCs is the ticket the socket
 * redeemed. A ticket minted from a read-only session must therefore BE a
 * read-only ticket, and a ticket that states no intent must be UNDETERMINED
 * (which the registry reads as read-only) — never implicitly read-write.
 *
 * The real broker against the array cache, exactly-once semantics untouched.
 */
final class RelayTicketReadOnlyGrantTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require dirname(__DIR__, 3).'/tests/Schema/FixesAutoload.php';
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('cache.default', 'array');
        $app['config']->set('teleframe.telegram_web.ticket_ttl_minutes', 10);
    }

    public function test_a_read_only_ticket_carries_its_intent_through_the_redeem(): void
    {
        $broker = new RelayTicketBroker(Cache::store());
        $minted = $broker->mint(11, 5, true);

        self::assertTrue($minted['read_only'], 'the mint echoes the bound intent');

        $grant = $broker->redeemGrant($minted['ticket']);

        self::assertSame(
            ['account_id' => 11, 'user_id' => 5, 'read_only' => true],
            $grant,
            'the daemon reads the identity AND the read-only grant off the one-shot ticket',
        );
        self::assertNull($broker->redeemGrant($minted['ticket']), 'and the grant redeem is exactly-once too');
    }

    public function test_a_ticket_that_states_no_intent_is_undetermined_not_read_write(): void
    {
        $broker = new RelayTicketBroker(Cache::store());

        // The legacy 2-arg mint (a caller that declined to state an intent).
        $minted = $broker->mint(11, 5);
        self::assertNull($minted['read_only']);

        $grant = $broker->redeemGrant($minted['ticket']);
        self::assertNull($grant['read_only'], 'no stated intent is UNDETERMINED');

        // Undetermined registers as read-only — deny by default.
        $grants = new RelayReadOnlyGrants;
        $grants->remember($grant['account_id'], $grant['user_id'], $grant['read_only'] ?? true);
        self::assertTrue($grants->isReadOnly(['account_id' => 11, 'user_id' => 5]));
    }

    public function test_a_full_power_ticket_redeems_a_write_capable_grant(): void
    {
        $broker = new RelayTicketBroker(Cache::store());
        $minted = $broker->mint(12, 6, false);

        $grant = $broker->redeemGrant($minted['ticket']);
        self::assertFalse($grant['read_only']);

        $grants = new RelayReadOnlyGrants;
        $grants->remember($grant['account_id'], $grant['user_id'], $grant['read_only']);
        self::assertFalse($grants->isReadOnly(['account_id' => 12, 'user_id' => 6]));
    }

    public function test_the_identity_redeem_shape_is_unchanged(): void
    {
        $broker = new RelayTicketBroker(Cache::store());
        $minted = $broker->mint(42, 5, true);

        // redeemIdentity() is the historical {account_id, user_id} view other
        // consumers (and their tests) depend on — the new key must not leak
        // into it.
        self::assertSame(['account_id' => 42, 'user_id' => 5], $broker->redeemIdentity($minted['ticket']));
    }

    public function test_a_corrupt_read_only_flag_degrades_to_undetermined(): void
    {
        $broker = new RelayTicketBroker(Cache::store());
        $minted = $broker->mint(11, 5, true);

        // Simulate a payload whose flag is not a boolean (hand-edited cache,
        // a downgrade, a partial write).
        Cache::put('telegram_relay_ticket:'.$minted['ticket'], json_encode([
            'account_id' => 11,
            'user_id' => 5,
            'read_only' => 'yes-please',
        ], JSON_THROW_ON_ERROR), now()->addMinutes(10));

        $grant = $broker->redeemGrant($minted['ticket']);
        self::assertNull($grant['read_only'], 'a non-boolean flag is UNDETERMINED, not a truthy read-write');
    }
}
