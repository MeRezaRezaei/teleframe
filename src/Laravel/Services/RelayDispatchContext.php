<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Services;

/**
 * Per-connection dispatch context for relay policy middlewares.
 *
 * Carries the identity the WebSocket ticket granted at the WS upgrade — the
 * vault account id plus the Laravel user captured at mint time (null when
 * the minting request had no authenticated user) — alongside the read-only
 * scope the request is running under, so a host middleware can decide per
 * RPC whether the current socket's user may act on the granted account.
 *
 * The ACTIVE context (RelayActiveAccount) still seats which vault account a
 * dispatch actually runs against; this object only informs policy. Both
 * account ids agree in the normal flow (the ticket is minted per account),
 * but the context's account_id is authoritative about the GRANTED session,
 * never the session string.
 *
 * Plain PHP, no facades, no credentials: the read-only scope is an injected
 * carrier, and a ticket grant is a plain value the answerer normalized.
 */
final class RelayDispatchContext
{
    public readonly ?int $accountId;

    public readonly int|string|null $userId;

    public readonly ?ReadOnlyScope $readOnlyScope;

    private function __construct(
        ?int $accountId,
        int|string|null $userId,
        ?ReadOnlyScope $readOnlyScope,
    ) {
        $this->accountId = $accountId;
        $this->userId = $userId;
        $this->readOnlyScope = $readOnlyScope;
    }

    /**
     * Build a context from the grant a terminal received.
     *
     * @param  array{account_id: int, user_id: int|string|null}|null  $ticketGrant
     */
    public static function fromTicketGrant(?array $ticketGrant, ?ReadOnlyScope $readOnlyScope = null): self
    {
        return new self(
            $ticketGrant['account_id'] ?? null,
            $ticketGrant['user_id'] ?? null,
            $readOnlyScope,
        );
    }
}
