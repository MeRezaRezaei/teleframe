<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Mirror\Models\TfMessage;

/**
 * Phase C re-baseline of the injectable-clock seam family.
 *
 * The legacy route-table timestamp path (RouteIdempotency with an injected
 * `now` clock, upgraded to created_at on tl_route_*) is GONE with the
 * tl_route_* surface. The curated dial is clock-AWARE but clock-FREE: fact
 * tables carry no created_at/updated_at columns, and the ingestor exposes
 * no time injection single — wire timestamps (the message `date`) are
 * stored VERBATIM, byte for byte, with zero wall-clock involvement.
 *
 * The surviving injectable-clock surface (Daemon, the only process that
 * truly needs a clock) is covered by its own test family; the ingest path
 * here must simply prove it never reaches for one.
 */
final class InjectableClockTest extends IngestTestCase
{
    private const ACCOUNT = 7;

    public function test_ingest_stores_wire_timestamp_verbatim_no_clock_involved(): void
    {
        $ingestor = new UpdateIngestor;

        $ingestor->ingest([
            '_' => 'message',
            'id' => 1186,
            'peer_id' => ['_' => 'peerChannel', 'channel_id' => 1737473577],
            'date' => 1724852400,
            'message' => 'the wire time, exactly',
        ], self::ACCOUNT);

        $row = DB::table('tf_messages')->where('account_id', self::ACCOUNT)->where('id', 1186)->first();
        self::assertSame(1724852400, (int) $row->date, 'wire `date` (task-time epoch) stored verbatim');

        // Proving "verbatim": a second payload with a different wire date
        // overwrites the cell with ITS value — no monotonic/now() skew.
        $ingestor->ingest([
            '_' => 'message',
            'id' => 1186,
            'peer_id' => ['_' => 'peerChannel', 'channel_id' => 1737473577],
            'date' => 998877,
            'message' => 'rewritten',
        ], self::ACCOUNT);

        $row = DB::table('tf_messages')->where('account_id', self::ACCOUNT)->where('id', 1186)->first();
        self::assertSame(998877, (int) $row->date, 'second wire value wins verbatim');
        self::assertSame(1, TfMessage::forAccount(self::ACCOUNT)->count(), 'upsert only ever touches the one row');
    }

    public function test_curated_dial_is_timestamp_free_and_clock_seamless(): void
    {
        // Fact tables carry NO created_at/updated_at: nothing to seed with a
        // clock, nothing to assert monotonicity on.
        self::assertFalse(Schema::hasColumn('tf_messages', 'created_at'));
        self::assertFalse(Schema::hasColumn('tf_messages', 'updated_at'));
        self::assertFalse(Schema::hasColumn('tf_updates', 'created_at'));

        // The ingestor signature exposes NO clock/now injection point.
        $ctor = new \ReflectionMethod(UpdateIngestor::class, '__construct');
        $params = array_map(static fn (\ReflectionParameter $p): string => (string) $p->getName(), $ctor->getParameters());
        self::assertSame(['events', 'logger'], $params, 'only the even/logger seams remain');
    }
}
