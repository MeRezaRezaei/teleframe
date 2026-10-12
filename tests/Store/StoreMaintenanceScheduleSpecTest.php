<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store;

use InvalidArgumentException;
use MeRezaRezaei\Teleframe\Store\Schedules\NullSchedulePublisher;
use MeRezaRezaei\Teleframe\Store\Schedules\RecurringScheduleClient;
use MeRezaRezaei\Teleframe\Store\Schedules\SchedulePublisher;
use MeRezaRezaei\Teleframe\Store\Schedules\StoreMaintenanceScheduleSpec;
use MeRezaRezaei\Teleframe\Store\Schedules\TemporalSchedulePublisher;
use PHPUnit\Framework\TestCase;

/**
 * StoreMaintenanceScheduleSpec — the payload producer the tstore schedule
 * seam was missing.
 *
 * Before it, `teleframe.tstore.schedules` could select a driver
 * (ab1961bc) and nothing on the seam ever called install(): the driver was
 * selectable, the publisher describable, the recurring work a description.
 * This class is the producer, and its contract is the one
 * BackupScheduleSpec already holds — pure data, {interval_seconds, activity}
 * per entry, deterministic ids, plain-PHP constructible, zero container.
 *
 * The tests that matter most are the negative ones: a spec that invented an
 * id per call, or emitted a non-positive cadence, would make re-arming STACK
 * instead of refresh, which is the one failure mode
 * RecurringScheduleClient's contract exists to prevent.
 */
final class StoreMaintenanceScheduleSpecTest extends TestCase
{
    public function test_it_is_plain_php_constructible_with_no_arguments(): void
    {
        // The standalone-smoke posture: construction touches no container, no
        // config, no socket. bin/standalone-smoke.php is out of this slice, so
        // this assertion is its mirror for the new class.
        $spec = new StoreMaintenanceScheduleSpec;

        self::assertSame(
            [3600, 86400],
            [StoreMaintenanceScheduleSpec::DEFAULT_PRUNE_INTERVAL, StoreMaintenanceScheduleSpec::DEFAULT_SWEEP_INTERVAL],
            'the shipped cadences are part of the contract the command help text advertises',
        );
        self::assertCount(2, $spec->installSpec());
    }

    public function test_the_payload_is_the_shape_the_publisher_reads(): void
    {
        foreach ((new StoreMaintenanceScheduleSpec)->installSpec() as $id => $payload) {
            self::assertIsString($id);
            self::assertArrayHasKey('interval_seconds', $payload);
            self::assertArrayHasKey('activity', $payload);
            self::assertIsInt($payload['interval_seconds']);
            self::assertGreaterThan(0, $payload['interval_seconds']);
        }
    }

    public function test_each_activity_is_an_artisan_invocation_that_needs_no_argument(): void
    {
        $payload = (new StoreMaintenanceScheduleSpec)->installSpec();

        // The activities must be runnable AS WRITTEN. `teleframe:tstore
        // prune` and `teleframe:tstore sync` take no positional argument, so a
        // schedule armed from this payload can fire with no host-supplied
        // input — which is why the media index (`index <bucket>`) is NOT here.
        self::assertSame('teleframe:tstore prune', $payload['tstore-hot-cache-prune']['activity']);
        self::assertSame('teleframe:tstore sync', $payload['tstore-registry-sweep']['activity']);
        self::assertSame(3600, $payload['tstore-hot-cache-prune']['interval_seconds']);
        self::assertSame(86400, $payload['tstore-registry-sweep']['interval_seconds']);
    }

    public function test_the_cadences_reach_the_payload_verbatim(): void
    {
        $payload = (new StoreMaintenanceScheduleSpec(120, 300))->installSpec();

        self::assertSame(120, $payload['tstore-hot-cache-prune']['interval_seconds']);
        self::assertSame(300, $payload['tstore-registry-sweep']['interval_seconds']);
    }

    public function test_a_non_positive_cadence_is_refused_rather_than_coerced(): void
    {
        // A zero/negative cadence is not "no cadence" — it is an unthrottled
        // loop. SdkTemporalScheduleClient refuses it too; refusing here means
        // the operator's own input is where the failure surfaces.
        foreach ([0, -1, -3600] as $bad) {
            try {
                new StoreMaintenanceScheduleSpec($bad);
                self::fail("prune interval {$bad} was accepted");
            } catch (InvalidArgumentException $e) {
                self::assertStringContainsString('positive', $e->getMessage());
            }

            try {
                new StoreMaintenanceScheduleSpec(3600, $bad);
                self::fail("sweep interval {$bad} was accepted");
            } catch (InvalidArgumentException $e) {
                self::assertStringContainsString('positive', $e->getMessage());
            }
        }
    }

    /**
     * THE idempotence property, stated as the spec's own contract: the same
     * spec yields identical ids and cadences every time. An id carrying a
     * timestamp or a UUID would make every re-arm a NEW schedule, so this is
     * the property the client's upsert rests on.
     */
    public function test_the_spec_is_deterministic_so_a_re_arm_refreshes_rather_than_stacks(): void
    {
        $a = new StoreMaintenanceScheduleSpec(900, 1800);
        $b = new StoreMaintenanceScheduleSpec(900, 1800);

        self::assertSame($a->installSpec(), $b->installSpec());
        self::assertSame($a->scheduleIds(), $b->scheduleIds());
        self::assertSame($a->installSpec(), $a->installSpec(), 'a second call on one instance drifted');
    }

    public function test_arming_twice_through_the_seam_produces_one_schedule_per_id(): void
    {
        $client = new StoreScheduleUpsertDouble;
        $publisher = new TemporalSchedulePublisher('127.0.0.1:7233', $client);
        $spec = new StoreMaintenanceScheduleSpec;

        $first = $publisher->install($spec->installSpec());
        $second = $publisher->install($spec->installSpec());

        // One schedule per id — the server-side state is keyed by id, so this
        // is where a stacking regression would show.
        self::assertSame(['tstore-hot-cache-prune', 'tstore-registry-sweep'], $client->ids());
        self::assertSame([3600, 86400], array_values($client->intervals()));

        // Two armings, four upserts — the second round UPDATED, it did not
        // CREATE. A create-on-re-arm implementation is the bug this catches.
        self::assertSame(['create', 'create', 'update', 'update'], $client->operations());

        self::assertSame(array_keys($first), array_keys($second));
        foreach ($second as $id => $status) {
            self::assertStringContainsString('recurring', $status, $id);
        }
    }

    public function test_the_payload_is_safe_to_hand_the_null_publisher(): void
    {
        $status = (new NullSchedulePublisher)->install((new StoreMaintenanceScheduleSpec)->installSpec());

        self::assertSame(
            [
                'tstore-hot-cache-prune' => 'skipped (null schedule driver)',
                'tstore-registry-sweep' => 'skipped (null schedule driver)',
            ],
            $status,
        );
    }

    public function test_the_producer_is_not_a_publisher(): void
    {
        // The producer must never dial, never hold a client, and never be
        // bound where a SchedulePublisher is expected.
        self::assertInstanceOf(SchedulePublisher::class, new NullSchedulePublisher);
        self::assertInstanceOf(RecurringScheduleClient::class, new StoreScheduleUpsertDouble);
        self::assertNotInstanceOf(SchedulePublisher::class, new StoreMaintenanceScheduleSpec);
    }

    /**
     * The two EXCLUDED jobs are named in describe() with their reason, so a
     * reader who expected a media-index schedule finds the answer instead of
     * a silent gap. Inventing either one would be inventing work: tg:bus:
     * strikes expires per write (IngestConsumer sets EXPIRE on the hash), and
     * the media index is documented as a one-shot exact reconciliation.
     */
    public function test_describe_explains_what_is_armed_and_why_the_rest_is_not(): void
    {
        $text = (new StoreMaintenanceScheduleSpec)->describe();

        self::assertStringContainsString('tstore-hot-cache-prune', $text);
        self::assertStringContainsString('tstore-registry-sweep', $text);
        self::assertStringContainsString('teleframe:tstore schedules --install', $text);
        self::assertStringContainsString('media index', $text);
        self::assertStringContainsString('tg:bus:strikes', $text);
    }
}
