<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Supervisor;

use MeRezaRezaei\Teleframe\Bus\StreamSchema;
use MeRezaRezaei\Teleframe\Supervisor\OpsReadModel;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayRedis;
use PHPUnit\Framework\TestCase;

/**
 * The operations read-model over state the package ALREADY records: the
 * ingest strike ledger (StreamSchema::STRIKES_KEY), the dead-letter stream,
 * and the undrained stream backlog. No new storage is involved — these rows
 * come out of the same keys IngestConsumer writes.
 */
final class OpsReadModelTest extends TestCase
{
    public function test_a_dead_lettered_item_is_visible_in_the_ops_output(): void
    {
        $redis = new ArrayRedis;
        $redis->xadd(StreamSchema::DL, '*', [
            'account_id' => '42',
            'update' => '{"_":"updateNewMessage","seq":7}',
            'ts' => '1720000000',
            'reason' => 'ingest-failed',
            'error' => 'unknown constructor',
        ]);

        $snapshot = (new OpsReadModel($redis))->snapshot();

        self::assertCount(1, $snapshot->deadLetters);
        $row = $snapshot->deadLetters[0];
        self::assertSame('ingest-failed', $row['reason']);
        self::assertSame('unknown constructor', $row['error']);
        self::assertSame(42, $row['account_id']);
        self::assertSame(1, $snapshot->toArray()['summary']['dead_letter_total']);
    }

    public function test_the_strike_ledger_surfaces_failing_entries_and_skips_reset_ones(): void
    {
        $redis = new ArrayRedis;
        $redis->hset(StreamSchema::STRIKES_KEY, '1-0', '3');
        $redis->hset(StreamSchema::STRIKES_KEY, '2-0', '0'); // reset by a later success
        $redis->hset(StreamSchema::STRIKES_KEY, '3-0', '1');

        $snapshot = (new OpsReadModel($redis))->snapshot();

        self::assertSame(
            [['entry_id' => '1-0', 'attempts' => 3, 'failing' => true], ['entry_id' => '3-0', 'attempts' => 1, 'failing' => true]],
            $snapshot->failingIngest,
            'worst first, and a strike count of 0 is not a failure',
        );
    }

    public function test_the_snapshot_reports_the_stream_backlog_and_the_supervisor_rows(): void
    {
        $redis = new ArrayRedis;
        $redis->xadd(StreamSchema::STREAM, '*', ['account_id' => '1', 'update' => '{}', 'ts' => '1']);
        $redis->xadd(StreamSchema::STREAM, '*', ['account_id' => '1', 'update' => '{}', 'ts' => '2']);

        $snapshot = (new OpsReadModel(
            $redis,
            supervised: static fn (): array => [
                ['name' => 'ingest', 'status' => 'running', 'pid' => 99, 'restarts' => 0, 'last_exit_code' => null, 'backoff_seconds' => 0, 'next_attempt_at' => 0],
            ],
        ))->snapshot();

        // The strikes count rides in backlog so the panel can report the
        // TRUE number of failing entries instead of the size of the sample it
        // displays. This assertion is what proves that total is carried, not
        // just re-derived from the slice.
        self::assertSame([
            StreamSchema::STREAM => 2,
            StreamSchema::DL => 0,
            StreamSchema::STRIKES_KEY => 0,
        ], $snapshot->backlog);
        self::assertSame(1, $snapshot->toArray()['summary']['supervised_running']);
    }

    public function test_the_snapshot_is_json_and_says_so_when_no_bus_is_bound(): void
    {
        $json = (new OpsReadModel)->toJson();

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        self::assertFalse($decoded['bus']);
        self::assertSame([], $decoded['dead_letters']);
        self::assertSame('not-recorded', $decoded['daemon_rotation'], 'daemon rotation state is process-local — the snapshot admits it');
    }

    public function test_a_dead_letter_ledger_entry_appears_in_the_json_payload(): void
    {
        $redis = new ArrayRedis;
        $redis->xadd(StreamSchema::DL, '*', [
            'account_id' => '7',
            'update' => '{}',
            'ts' => '1720000000',
            'reason' => 'decode-failed',
            'error' => 'poison payload',
        ]);

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode((new OpsReadModel($redis))->toJson(), true, 512, JSON_THROW_ON_ERROR);

        self::assertTrue($decoded['bus']);
        self::assertCount(1, $decoded['dead_letters']);
        self::assertSame('poison payload', $decoded['dead_letters'][0]['error']);
        self::assertSame('ok', $decoded['sources']['dead_letter']);
    }
}
