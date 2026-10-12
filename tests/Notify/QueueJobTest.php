<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Notify;

use InvalidArgumentException;
use MeRezaRezaei\Teleframe\Notify\NotificationJob;
use MeRezaRezaei\Teleframe\Notify\SyncNotificationQueue;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use RuntimeException;

/**
 * Queue seam + payload DTO (design §5): the sync driver enqueues inline and
 * answers the canonical dead-letter key; NotificationJob round-trips the JSON
 * wire shape {job_id,topic,level,brief,context,attachment,target,premium,
 * attempts,created_at} unchanged; malformed payloads throw
 * InvalidArgumentException; execute() failure is logged at error (PSR-3) and
 * ALWAYS rethrown — the logger never swallows (spec §7).
 */
final class QueueJobTest extends TestCase
{
    public function test_sync_queue_runs_the_handler_inline_and_returns_the_job_id(): void
    {
        $received = null;
        $queue = new SyncNotificationQueue(static function (NotificationJob $job) use (&$received): void {
            $received = $job;
        });

        $job = self::sampleJob();
        $id = $queue->enqueue($job);

        self::assertSame($job->jobId, $id);
        self::assertSame($job, $received); // inline: handler ran before enqueue returned
    }

    public function test_sync_queue_without_handler_is_a_passthrough(): void
    {
        $queue = new SyncNotificationQueue;

        self::assertSame('job-x', $queue->enqueue(new NotificationJob('job-x', 'app.info', 'info', 'brief')));
        self::assertFalse($queue->isAsync());
        self::assertSame(0, $queue->consume(static fn (NotificationJob $job): bool => true, 10));
    }

    public function test_sync_queue_dead_letter_name_is_the_canonical_wire_key(): void
    {
        self::assertSame('tframe.notify.dl', (new SyncNotificationQueue)->deadLetterName());
    }

    public function test_job_to_array_from_array_round_trip_is_identical(): void
    {
        $wire = self::sampleJob()->toArray();

        self::assertSame([
            'job_id',
            'topic',
            'level',
            'brief',
            'context',
            'attachment',
            'target',
            'premium',
            'attempts',
            'created_at',
        ], array_keys($wire));

        $json = json_encode($wire, JSON_THROW_ON_ERROR);
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);
        $back = NotificationJob::fromArray($decoded);

        self::assertSame($wire, $back->toArray());
    }

    public function test_job_from_array_rejects_malformed_payloads(): void
    {
        $payload = self::sampleJob()->toArray();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('missing required fields');

        NotificationJob::fromArray(['job_id' => $payload['job_id'], 'topic' => 'app.info', 'level' => 'bogus']);
    }

    public function test_job_from_array_rejects_missing_job_id(): void
    {
        $payload = self::sampleJob()->toArray();
        unset($payload['job_id']);

        $this->expectException(InvalidArgumentException::class);

        NotificationJob::fromArray($payload);
    }

    public function test_job_execute_returns_the_sender_result_on_success(): void
    {
        $job = self::sampleJob();
        $seen = null;
        $result = $job->execute(static function (NotificationJob $inner) use (&$seen): string {
            $seen = $inner;

            return 'sent:'.$inner->topic;
        });

        self::assertSame('sent:app.error', $result);
        self::assertSame($job, $seen);
    }

    public function test_job_execute_logs_error_and_rethrows_the_original_exception(): void
    {
        $logger = new class extends AbstractLogger
        {
            /** @var list<array{level: mixed, message: string, context: array<string, mixed>}> */
            public array $records = [];

            public function log($level, string|\Stringable $message, array $context = []): void
            {
                $this->records[] = ['level' => $level, 'message' => (string) $message, 'context' => $context];
            }
        };

        $job = self::sampleJob();
        $original = new RuntimeException('transport down');

        try {
            $job->execute(static fn (NotificationJob $inner): mixed => throw $original, $logger);
            self::fail('execute() must rethrow the original exception, never swallow it');
        } catch (RuntimeException $caught) {
            self::assertSame($original, $caught);
        }

        self::assertCount(1, $logger->records);
        self::assertSame('error', $logger->records[0]['level']);
        self::assertSame('notification job failed', $logger->records[0]['message']);
        self::assertSame($job->jobId, $logger->records[0]['context']['job_id']);
        self::assertSame('transport down', $logger->records[0]['context']['error']);
    }

    private static function sampleJob(): NotificationJob
    {
        return new NotificationJob(
            jobId: 'tf-0123456789abcdef',
            topic: 'app.error',
            level: 'error',
            brief: 'ingest stalled',
            context: ['account_id' => 7],
            attachment: ['name' => 'a.html', 'path' => '/tmp/a.html', 'mime' => 'text/html', 'bytes' => 128],
            target: ['chat' => '@ops-log', 'thread_id' => 5],
            premium: true,
            attempts: 0,
            createdAt: 1756116000,
        );
    }
}
