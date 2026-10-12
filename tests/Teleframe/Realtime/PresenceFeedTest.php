<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Teleframe\Realtime;

use MeRezaRezaei\Teleframe\Control\RelaySessionRegistry;
use MeRezaRezaei\Teleframe\Handler\InMemoryCache;
use MeRezaRezaei\Teleframe\Realtime\CentrifugoBridge;
use MeRezaRezaei\Teleframe\Realtime\ChannelNames;
use MeRezaRezaei\Teleframe\Realtime\PresenceFeed;
use PHPUnit\Framework\TestCase;

/**
 * Fake CentrifugoBridge that records every publish call instead of
 * hitting a real Centrifugo server.
 */
final class FakeBridge implements CentrifugoBridge
{
    /** @var list<array{0: string, 1: array<string, mixed>}> */
    public array $published = [];

    public function publish(string $channel, array $payload): void
    {
        $this->published[] = [$channel, $payload];
    }
}

/**
 * Wave E presence feed: publishes the registry's current status onto the
 * account updates channel, deterministically (online=false when no session
 * exists), with only the relay id + heartbeats — no credentials.
 */
final class PresenceFeedTest extends TestCase
{
    public function test_announce_publishes_online_status_to_the_account_updates_channel(): void
    {
        $bridge = new FakeBridge;
        $registry = new RelaySessionRegistry(new InMemoryCache);
        $registry->start(7, 'relay-1');
        $feed = new PresenceFeed($bridge, $registry);

        $feed->announce(7);

        self::assertCount(1, $bridge->published);
        self::assertSame(ChannelNames::accountUpdates(7), $bridge->published[0][0]);
        self::assertSame(7, $bridge->published[0][1]['account_id']);
        self::assertTrue($bridge->published[0][1]['online']);
        self::assertSame('relay-1', $bridge->published[0][1]['relay_id']);
        self::assertIsInt($bridge->published[0][1]['last_seen_at']);
        self::assertIsInt($bridge->published[0][1]['ts']);
        self::assertGreaterThanOrEqual(0, time() - (int) $bridge->published[0][1]['ts']);
    }

    public function test_announce_without_session_publishes_offline(): void
    {
        $bridge = new FakeBridge;
        $feed = new PresenceFeed($bridge, new RelaySessionRegistry(new InMemoryCache));

        $feed->announce(9);

        self::assertCount(1, $bridge->published, 'announce is deterministic — it always publishes');
        self::assertSame(ChannelNames::accountUpdates(9), $bridge->published[0][0]);
        self::assertSame(9, $bridge->published[0][1]['account_id']);
        self::assertFalse($bridge->published[0][1]['online']);
        self::assertNull($bridge->published[0][1]['relay_id']);
        self::assertNull($bridge->published[0][1]['last_seen_at']);
    }

    public function test_announce_reflects_a_touched_heartbeat(): void
    {
        $bridge = new FakeBridge;
        $registry = new RelaySessionRegistry(new InMemoryCache);
        $registry->start(3, 'relay-a');
        $registry->touch(3);
        $feed = new PresenceFeed($bridge, $registry);

        $feed->announce(3);

        self::assertTrue($bridge->published[0][1]['online']);
        self::assertSame('relay-a', $bridge->published[0][1]['relay_id'], 'touch must keep the relay id');
        self::assertSame('relay-a', $registry->status(3)?->relayId);
    }

    public function test_announce_suppresses_publish_failures(): void
    {
        $bridge = new class implements CentrifugoBridge
        {
            public function publish(string $channel, array $payload): void
            {
                throw new \RuntimeException('centrifugo offline');
            }
        };
        $feed = new PresenceFeed($bridge, new RelaySessionRegistry(new InMemoryCache));

        // Must not throw — presence is a projection; the registry stays truth.
        $feed->announce(1);

        // No assertion needed — reaching this line without exception is the proof
        self::assertTrue(true);
    }
}
