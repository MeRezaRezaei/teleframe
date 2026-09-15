<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Event;
use MeRezaRezaei\Teleframe\Ingest\Events\UpdateStored;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;

/**
 * Phase C re-baseline: the UpdateStored event fires through the INJECTED
 * dispatcher, and the Laravel wiring (provider closure below) makes
 * Event::fake() still see it. The payload is the curated messages surface
 * (tf_messages) — the legacy user-domain ingest stored nothing and fired
 * nothing.
 */
final class UpdateIngestorDispatchTest extends IngestTestCase
{
    private const ACCOUNT = 7;

    public function test_ingest_fires_update_stored_through_injected_dispatcher(): void
    {
        $captured = [];
        $capturing = new class($captured) implements Dispatcher
        {
            public function __construct(private array &$captured) {}

            public function dispatch($event, $payload = [], $halt = false): array
            {
                $this->captured[] = $event;

                return [];
            }

            public function listen($events, $listener = null) {}

            public function hasListeners($eventName)
            {
                return false;
            }

            public function subscribe($subscriber) {}

            public function until($event, $payload = []) {}

            public function forget($event) {}

            public function forgetPushed() {}

            public function push($event, $payload = []) {}

            public function flush($event) {}
        };

        $ingestor = new UpdateIngestor(events: $capturing);
        $ingestor->ingest(self::messagePayload(), self::ACCOUNT);

        self::assertCount(1, $captured);
        self::assertInstanceOf(UpdateStored::class, $captured[0]);
        self::assertSame(self::ACCOUNT, $captured[0]->accountId);
    }

    public function test_container_resolved_ingestor_fires_into_laravel_events(): void
    {
        Event::fake([UpdateStored::class]);

        $this->app->make(UpdateIngestor::class)->ingest(self::messagePayload(), self::ACCOUNT);

        Event::assertDispatchedTimes(UpdateStored::class, 1);
    }

    /**
     * @return array<string, mixed>
     */
    private static function messagePayload(): array
    {
        return [
            '_' => 'message',
            'out' => false,
            'id' => 5,
            'peer_id' => ['_' => 'peerChannel', 'channel_id' => 900],
            'date' => 1750000000,
            'message' => 'proof',
        ];
    }
}
