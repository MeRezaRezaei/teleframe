<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Events;

/** Fired when an UpdateRoutingRule row is written/deleted (EventObligation of core:UpdateRoutingRule). */
final class RoutingRuleChanged
{
    public function __construct(
        public readonly int $accountId,
        public readonly int $peerType,
        public readonly int $peerId,
        public readonly string $mode,
        public readonly int $priority,
        public readonly string $operation,   // 'upsert' | 'delete'
    ) {}
}
