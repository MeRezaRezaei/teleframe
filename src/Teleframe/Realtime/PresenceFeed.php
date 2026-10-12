<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Realtime;

use MeRezaRezaei\Teleframe\Control\RelaySessionRegistry;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Presence feed (Wave E): the "sit in the middle" live mirror for one vault
 * account.
 *
 * Reads the RelaySessionRegistry and publishes the account's current
 * presence onto its Centrifugo updates channel (`account:{id}:updates`), so
 * a browser subscribed to that channel sees online/offline transitions
 * without polling. Announcements are deterministic: EVERY announce()
 * publishes — online=false when no relay session exists right now — so a
 * browser always learns the current state, never just changes it.
 *
 * Failures never throw into the calling request or heartbeat: realtime is a
 * projection, the registry is the source of truth. A failed publish is
 * logged (if a logger was injected) and dropped, mirroring
 * UpdateStoredCentrifugoListener. No credentials (session strings / tickets)
 * ever enter this payload — only the relay id + heartbeats.
 *
 * Plain PHP, no facades: the bridge and registry are injected, so the same
 * feed works over Laravel's container, Redis, or any CentrifugoBridge.
 */
final class PresenceFeed
{
    public function __construct(
        private readonly CentrifugoBridge $bridge,
        private readonly RelaySessionRegistry $registry,
        private readonly ?LoggerInterface $logger = null,
    ) {}

    public function announce(int $accountId): void
    {
        $session = $this->registry->status($accountId);

        $payload = [
            'account_id' => $accountId,
            'online' => $session !== null,
            'relay_id' => $session?->relayId,
            'last_seen_at' => $session?->lastSeenAt,
            'ts' => time(),
        ];

        try {
            $this->bridge->publish(ChannelNames::accountUpdates($accountId), $payload);
        } catch (Throwable $e) {
            $this->logger?->error('teleframe.realtime: presence publish failed', [
                'account_id' => $accountId,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
