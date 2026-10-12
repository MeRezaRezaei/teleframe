<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Control;

/**
 * Immutable identity of one relayed vault account. Carries only what the
 * control layer needs to route + observe — the session string itself never
 * enters this object (it lives in the vault and is used by the scope
 * resolver only).
 */
final class RelayContext
{
    public function __construct(
        public readonly int $accountId,
        public readonly int $dcId,
        public readonly string $label,
        public readonly ?string $liveEndpoint = null,
    ) {}
}
