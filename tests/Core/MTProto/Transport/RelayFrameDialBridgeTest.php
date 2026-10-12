<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Core\MTProto\Transport;

use MeRezaRezaei\Teleframe\Core\MTProto\Connection\EncryptedConnection;
use MeRezaRezaei\Teleframe\Core\MTProto\SessionData;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\RelayFrameDialBridge;
use PHPUnit\Framework\TestCase;

/**
 * The multi-account switch behind the ticket, with zero I/O.
 *
 * EncryptedConnection is dialed lazily (inside a real dial) so its
 * constructor takes the socket + session but opens NO socket here. The
 * scope resolver is injected as a plain mutable holder — the SAME shape
 * the relay listener swaps — so the test can prove that swapping the
 * resolver behind the SAME bridge leases a DIFFERENT managed session,
 * i.e. "multi-account switching = swap the scope resolver behind the
 * ticket" without ever dialing.
 */
final class RelayFrameDialBridgeTest extends TestCase
{
    public function test_swap_seam_leases_different_managed_session_behind_same_bridge(): void
    {
        $session = new SessionData(1, 'arm-account-a');      // account A vault session
        $second = new SessionData(2, 'arm-account-b');      // account B vault session

        $conn = $this->dialableConnection($session);
        $bridge = (new RelayFrameDialBridge($conn, $session, null))
            ->swapScopeResolver(new class($second)
            {
                public function __construct(public readonly SessionData $session) {}
            });

        $this->assertSame('arm-account-b', $bridge->lease()->authKey, 'swap resolver leases the new managed session');
        $this->assertSame($conn, $bridge->surface(), 'surface is the same lazily-dialed connection');
        $this->assertFalse($bridge->surface()->isInited(), 'surface never init-dials at build time');
    }

    /**
     * A connection built with no socket: the constructor seats the session
     * but opens nothing, so this test exercises the swap seam with zero I/O.
     */
    private function dialableConnection(SessionData $session): EncryptedConnection
    {
        return new EncryptedConnection($session);
    }
}
