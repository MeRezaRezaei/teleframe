<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Ingest\RouteIdempotency;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;

/**
 * Phase 0 Task 2: timestamps come from the injectable clock, not the
 * Laravel now() helper (undefined function in plain PHP). Uses the same
 * proven route table as IngestResponseTest (tl_route_messages_get_history)
 * and its migration pattern (RouteIdempotency::migrationPaths()).
 */
final class InjectableClockTest extends IngestTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', [
            '--force' => true,
            '--realpath' => true,
            '--path' => RouteIdempotency::migrationPaths(),
        ])->run();
    }

    public function test_route_marking_uses_injected_clock(): void
    {
        $frozen = new \DateTimeImmutable('2026-09-07 10:00:00', new \DateTimeZone('UTC'));
        $routes = new RouteIdempotency(now: static fn (): \DateTimeImmutable => $frozen);

        $routes->mark('messages.getHistory', 'clock-test', 7, 42);

        $mark = DB::table('tl_route_messages_get_history')->sole();
        self::assertSame('2026-09-07 10:00:00', substr((string) $mark->created_at, 0, 19));
    }

    public function test_ingestor_threading_passes_clock_to_routes(): void
    {
        $frozen = new \DateTimeImmutable('2026-09-07 11:00:00', new \DateTimeZone('UTC'));
        $ingestor = new UpdateIngestor(now: static fn (): \DateTimeImmutable => $frozen);

        $routes = new \ReflectionProperty(UpdateIngestor::class, 'routes');
        $routes->getValue($ingestor)->mark('messages.getHistory', 'clock-thread', 7, 42);

        $mark = DB::table('tl_route_messages_get_history')->sole();
        self::assertSame('2026-09-07 11:00:00', substr((string) $mark->created_at, 0, 19));
    }
}
