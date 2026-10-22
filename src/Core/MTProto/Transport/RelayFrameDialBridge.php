<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\MTProto\Transport;

use MeRezaRezaei\Teleframe\Core\MTProto\Connection\EncryptedConnection;
use MeRezaRezaei\Teleframe\Core\MTProto\SessionData;
use RuntimeException;

/**
 * Wave-C-shape-#2 terminal: the TRUE "connect to our system" path.
 *
 * A patched client's WebSocket/HTTP transport does NOT speak MTProto
 * directly — it speaks frames (WS/HTTP). A managed vault session is NOT
 * a raw dial — it is a vault-bound scope that must be driven into a
 * real encrypted connection. This bridge is the seam that joins those
 * two truths: it pumps an inbound frame stream straight into
 * EncryptedConnection and seats the managed vault session behind it.
 *
 * Multi-account switching is therefore NOT a new mechanism: the scope
 * resolver is the swap point. This bridge resolves the session scope
 * through a callable the RelayCommand/listener already swaps behind the
 * ticket (RelayScopeResolver). Building a bridge never dials; the
 * encrypted socket opens lazily on the first RPC — exactly like
 * EncryptedConnection itself (see its Wave-1 docblock).
 *
 * Two construction paths:
 *  - The ctor seats a caller-supplied (connection, vaultSession) pair up
 *    front — the fixture/test shape.
 *  - lazy() seats NOTHING: the EncryptedConnection + SessionData are
 *    derived from a caller-supplied scope resolver the first time
 *    surface()/lease()/pumpFrame() is exercised. That is the production
 *    path (RelayFactory::bridgeFor()), so a bridge can be built without
 *    touching the vault, decoding a session string, or dialing — the
 *    session/connection come from the living scope, and swapping the
 *    scope resolver behind the ticket re-seats the bridge on another
 *    account (see swapScopeResolver()).
 */
final class RelayFrameDialBridge
{
    /**
     * An upstream frame-to-session pump. Receives frames from a patched
     * client's transport and must push them into DialResult::client().
     *
     * @var (callable(EncryptedConnection, mixed): void)|null
     */
    private $framePump = null;

    /**
     * lazy() seat resolver: yields a scope-shaped object whose ->client
     * exposes getSession(): SessionData (production: the RelayScope
     * AccountRelay::scope() / RelayFactory::bridgeFor() resolves). When
     * set, the ctor-seated connection/session are ignored entirely.
     *
     * @var (callable(): mixed)|null
     */
    private $seatScope = null;

    /** Lazily-built connection for the seat path (null until first use). */
    private ?EncryptedConnection $seated = null;

    public function __construct(
        /** The true dial target — what a patched client's transport pumps INTO. */
        private readonly EncryptedConnection $connection,
        private readonly SessionData $vaultSession,
        /**
         * Callsite-injected scope resolver; the multi-account swap point.
         * Deliberately NOT readonly: swapScopeResolver() rewrites it so the
         * same bridge can lease a different managed session.
         */
        private $scopeResolver,
    ) {}

    /**
     * The honest lazy seat: build a bridge whose EncryptedConnection and
     * SessionData are NOT fixed at construction — they are derived from a
     * caller-supplied scope resolver the first time surface()/lease()/
     * pumpFrame() runs. Building never resolves the scope, never reads a
     * session string, and never dials (the encrypted socket opens lazily
     * on the first RPC, as always).
     *
     * The resolver must yield a scope-shaped object whose ->client exposes
     * getSession(): SessionData — production hands it the vault account's
     * RelayScope (AccountRelay::scope()), i.e. the SAME live scope family
     * forAccount()/terminalFor() resolve, so a multi-account swap behind
     * the ticket moves the seated session too.
     *
     * @param  callable(): mixed  $seatScope  () => scope-shaped object (->client with getSession(): SessionData)
     */
    public static function lazy(callable $seatScope): static
    {
        // The final ctor still takes a real (never-dialed) connection: a
        // throwaway placeholder seats it. The lazy path ignores the
        // ctor-seated pair entirely — surface()/lease()/pumpFrame() route
        // through the derived connection, so the placeholder never leaks.
        $placeholder = new SessionData(1, 'lazy');

        $bridge = new self(
            new EncryptedConnection($placeholder),
            $placeholder,
            null,
        );
        $bridge->seatScope = $seatScope(...);

        return $bridge;
    }

    /**
     * The swap seam behind the ticket. Multi-account switching = swap the
     * resolver the relay sits behind, exactly as the verbatim spells out:
     * "multi-account switching = swap the scope resolver behind the ticket".
     * On the lazy path the swap rewrites WHICH scope the bridge seats
     * against and drops the cached connection, so the next use re-seats
     * against the swapped account (never reading the vault at build).
     */
    public function swapScopeResolver(mixed $resolver): static
    {
        if ($this->seatScope !== null) {
            if (! is_callable($resolver)) {
                throw new RuntimeException('relay frame-dial bridge: the lazy seat swap needs a callable scope resolver');
            }

            $this->seatScope = $resolver(...);
            $this->seated = null;

            return $this;
        }

        $this->scopeResolver = $resolver;

        return $this;
    }

    public function surface(): EncryptedConnection
    {
        return $this->seatScope === null ? $this->connection : $this->seated();
    }

    /**
     * Seat the upstream frame pump: a patched client's WS/HTTP transport
     * hands its inbound frames to this bridge, which pushes them into the
     * dialed connection. Seating never dials — the socket still opens
     * lazily on the first RPC.
     *
     * @param  callable(EncryptedConnection $connection, mixed $frame): void  $pump
     */
    public function withFramePump(callable $pump): static
    {
        $this->framePump = $pump;

        return $this;
    }

    /**
     * Push one inbound frame through the seated pump. A no-op when no pump
     * is seated (the bridge still holds a valid lazily-dialed connection).
     * On the lazy path the pump receives the DERIVED connection, so inbound
     * frames terminate into the same live scope surface() leases.
     */
    public function pumpFrame(mixed $frame): void
    {
        if ($this->framePump !== null) {
            ($this->framePump)($this->surface(), $frame);
        }
    }

    public function lease(): SessionData
    {
        if ($this->seatScope !== null) {
            return $this->seated()->lastSessionData();
        }

        $resolver = $this->scopeResolver;

        if (is_callable($resolver)) {
            $candidate = $resolver();
        } elseif (is_object($resolver)) {
            $candidate = $resolver->session ?? null;
        } else {
            $candidate = null;
        }

        return $candidate instanceof SessionData ? $candidate : $this->vaultSession;
    }

    /**
     * The lazy seat itself: resolve the scope once, derive the SessionData
     * from its client via getSession(), and hand it to a fresh
     * EncryptedConnection — with NO socket, so this never dials. The
     * derived connection is cached; swapScopeResolver() drops the cache so
     * the next use re-seats against the swapped account.
     */
    private function seated(): EncryptedConnection
    {
        if ($this->seated !== null) {
            return $this->seated;
        }

        $resolver = $this->seatScope;

        if (! is_callable($resolver)) {
            throw new RuntimeException('relay frame-dial bridge: no lazy seat resolver wired — pass a callable to lazy()/swapScopeResolver()');
        }

        $scope = $resolver();
        $client = is_object($scope) && is_object($scope->client ?? null) ? $scope->client : $scope;

        if (! is_object($client) || ! method_exists($client, 'getSession')) {
            throw new RuntimeException(sprintf(
                'relay frame-dial bridge: seat scope %s must expose a client with getSession(): SessionData',
                is_object($scope) ? $scope::class : get_debug_type($scope),
            ));
        }

        /** @var mixed $session */
        $session = $client->getSession();
        if (! $session instanceof SessionData) {
            throw new RuntimeException('relay frame-dial bridge: scope client getSession() must return a SessionData');
        }

        return $this->seated = new EncryptedConnection($session);
    }
}
