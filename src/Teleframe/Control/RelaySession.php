<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Control;

/**
 * Immutable snapshot of one active relay session for a vault account.
 *
 * Wall-clock Unix seconds; `startedAt` is when the session began (relay or
 * manual), `lastSeenAt` the most recent activity heartbeat. The session
 * string itself never enters this object (it lives in the vault and is used
 * by the scope resolver / relay only).
 */
final class RelaySession
{
    public function __construct(
        public readonly int $accountId,
        public readonly string $relayId,
        public readonly int $startedAt,
        public readonly int $lastSeenAt,
    ) {}
}
