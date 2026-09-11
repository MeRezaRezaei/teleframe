<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Realtime;

use MeRezaRezaei\Teleframe\Ingest\Events\UpdateStored;
use MeRezaRezaei\Teleframe\Laravel\Realtime\UpdateStoredCentrifugoListener;
use MeRezaRezaei\Teleframe\Realtime\CentrifugoBridge;
use MeRezaRezaei\Teleframe\Realtime\ChannelNames;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TfUser;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TfMessage;
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

final class UpdateStoredCentrifugoListenerTest extends TestCase
{
    public function testPublishesToAccountUpdatesChannel(): void
    {
        $bridge = new FakeBridge();
        $listener = new UpdateStoredCentrifugoListener($bridge);

        $model = new TfUser();
        $listener(new UpdateStored($model, 11));

        self::assertCount(1, $bridge->published);
        self::assertSame(ChannelNames::accountUpdates(11), $bridge->published[0][0]);
        self::assertSame(11, $bridge->published[0][1]['account_id']);
        self::assertSame('TfUser', $bridge->published[0][1]['type']);
    }

    public function testPublishesToChatChannelWhenModelHasPeerId(): void
    {
        $bridge = new FakeBridge();
        $listener = new UpdateStoredCentrifugoListener($bridge);

        $model = new TfMessage();
        $model->peer_id = 42;
        $listener(new UpdateStored($model, 7));

        self::assertCount(2, $bridge->published);
        self::assertSame(ChannelNames::accountUpdates(7), $bridge->published[0][0]);
        self::assertSame(ChannelNames::accountMessages(7, 42), $bridge->published[1][0]);
    }

    public function testSuppressesPublishFailures(): void
    {
        $bridge = new class implements CentrifugoBridge {
            public function publish(string $channel, array $payload): void
            {
                throw new \RuntimeException('centrifugo offline');
            }
        };
        $listener = new UpdateStoredCentrifugoListener($bridge);

        // Must not throw — realtime failures never break ingest
        $model = new TfUser();
        $listener(new UpdateStored($model, 1));

        // No assertion needed — reaching this line without exception is the proof
        self::assertTrue(true);
    }

    public function testChatIdFromPeerId(): void
    {
        $bridge = new FakeBridge();
        $listener = new UpdateStoredCentrifugoListener($bridge);

        $model = new TfMessage();
        $model->peer_id = 55;
        $listener(new UpdateStored($model, 3));

        self::assertCount(2, $bridge->published);
        self::assertSame(ChannelNames::accountMessages(3, 55), $bridge->published[1][0]);
    }
}
