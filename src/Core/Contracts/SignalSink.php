<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\Contracts;

/**
 * Observer for poller lifecycle signals (gap kinds + resync completion).
 *
 * The Laravel events (TelegramGapDetected / TelegramResynced) remain the
 * framework integration surface; this port is the framework-free one, so
 * plain-PHP hosts (daemon, future handler layer) can observe gaps that
 * today are silently dropped outside a Laravel app.
 */
interface SignalSink
{
    /** @param string $kind One of TelegramGapDetected::KIND_* */
    public function gapDetected(string $kind, array $context): void;

    /** @param array<string, mixed> $state pts/date/qts/seq snapshot after resync */
    public function resynced(array $state, int $accountId): void;
}