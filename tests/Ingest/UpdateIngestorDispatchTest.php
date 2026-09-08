<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Event;
use MeRezaRezaei\Teleframe\Ingest\Events\UpdateStored;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;

/**
 * Phase 0 Task 1: the event fires through the INJECTED dispatcher, and the
 * Laravel wiring (provider closure below) makes Event::fake() still see it.
 */
final class UpdateIngestorDispatchTest extends IngestTestCase
{
    private const ACCOUNT = 7;

    public function test_ingest_fires_update_stored_through_injected_dispatcher(): void
    {
        $captured = [];
        $capturing = new class ($captured) implements Dispatcher {
            public function __construct(private array &$captured) {}

            public function dispatch($event, $payload = [], $halt = false): array
            {
                $this->captured[] = $event;

                return [];
            }

            public function listen($events, $listener = null) {}
            public function hasListeners($eventName) { return false; }
            public function subscribe($subscriber) {}
            public function until($event, $payload = []) {}
            public function forget($event) {}
            public function forgetPushed() {}
            public function push($event, $payload = []) {}
            public function flush($event) {}
        };

        $ingestor = new UpdateIngestor(events: $capturing);
        $ingestor->ingest(self::userPayload(), self::ACCOUNT);

        self::assertCount(1, $captured);
        self::assertInstanceOf(UpdateStored::class, $captured[0]);
        self::assertSame(self::ACCOUNT, $captured[0]->accountId);
    }

    public function test_container_resolved_ingestor_fires_into_laravel_events(): void
    {
        Event::fake([UpdateStored::class]);

        $this->app->make(UpdateIngestor::class)->ingest(self::userPayload(), self::ACCOUNT);

        Event::assertDispatchedTimes(UpdateStored::class, 1);
    }

    private static function userPayload(): array
    {
        return [
            '_' => 'user',
            'flags' => (1 << 0) | (1 << 1) | (1 << 2) | (1 << 3) | (1 << 4) | (1 << 22) | (1 << 28),
            'id' => 501558149,
            'access_hash' => -5988024083302710253,
            'first_name' => 'Reza',
            'last_name' => 'Rezaei',
            'username' => 'RezaRezaei',
            'phone' => '989121234567',
            'lang_code' => 'en',
            'flags2' => (1 << 4),
            'stories_unavailable' => true,
            'premium' => true,
        ];
    }
}