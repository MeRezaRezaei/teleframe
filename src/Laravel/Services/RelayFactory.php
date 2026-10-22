<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Services;

use MeRezaRezaei\Teleframe\Control\AccountRelay;
use MeRezaRezaei\Teleframe\Control\RelayContext;
use MeRezaRezaei\Teleframe\Control\RelayPipeline;
use MeRezaRezaei\Teleframe\Control\RelayTerminal;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\RelayFrameDialBridge;

/**
 * Builds the control-layer seam for one vault account behind the "sit in
 * the middle" terminal: forAccount() yields the AccountRelay, the filter
 * onion that guards the conversation; terminalFor() yields the transport
 * RelayTerminal that dispatches OUTBOUND calls against the same live
 * scope; bridgeFor() yields the client-in frame-dial bridge that seats a
 * patched client's inbound transport on the same live scope. In every
 * case the context is fixed at build time while the scope is a lazy
 * closure over the RelaySessionResolver — so constructing a relay never
 * reads the vault, never builds a client, and never dials. Filters are
 * pushed afterwards via AccountRelay::push().
 */
final class RelayFactory
{
    public function __construct(
        private readonly RelaySessionResolver $resolver,
    ) {}

    public function forAccount(RelayContext $ctx): AccountRelay
    {
        return new AccountRelay(
            $ctx,
            fn (): RelayScope => $this->resolver->forAccount($ctx),
            new RelayPipeline,
        );
    }

    /**
     * The transport terminal for the same account the AccountRelay seam
     * serves: OUTBOUND RelayMessages are dispatched via RelayTerminal's
     * default executor against exactly the scope resolver forAccount()
     * uses, and the RPC reply comes back as the produced INBOUND message.
     * Fully lazy — binding the terminal resolves nothing until the first
     * outbound dispatch (RelayTerminal::__invoke()).
     */
    public function terminalFor(RelayContext $ctx): RelayTerminal
    {
        $relay = $this->forAccount($ctx);

        return new RelayTerminal(
            static fn (): mixed => $relay->scope(),
        );
    }

    /**
     * The client-in frame-dial seam for the same account the AccountRelay
     * onion and RelayTerminal serve: a patched client's binary
     * MTProto-over-WebSocket transport terminates into the bridge's
     * EncryptedConnection, which is seated lazily on the SAME vault account
     * scope forAccount()/terminalFor() resolve (RelayFrameDialBridge::lazy()
     * derives connection + SessionData from the scope's client via
     * getSession() on first use). Fully lazy — building the bridge resolves
     * nothing, reads no session string, and dials no socket; swapping the
     * scope resolver behind the ticket (swapScopeResolver()) re-seats the
     * same bridge on another vault account.
     */
    public function bridgeFor(RelayContext $ctx): RelayFrameDialBridge
    {
        $relay = $this->forAccount($ctx);

        return RelayFrameDialBridge::lazy(
            static fn (): mixed => $relay->scope(),
        );
    }
}
