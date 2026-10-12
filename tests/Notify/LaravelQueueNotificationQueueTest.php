<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Notify;

use MeRezaRezaei\Teleframe\Laravel\Notify\LaravelQueueNotificationQueue;
use MeRezaRezaei\Teleframe\Notify\AmqpNotificationQueue;
use MeRezaRezaei\Teleframe\Notify\NotificationJob;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use UnexpectedValueException;

/**
 * LaravelQueueNotificationQueue — the async notify driver over the HOST'S OWN
 * Laravel queue, unit-tested against hand-written fakes (a real
 * database/redis backend is neither needed nor wanted here).
 *
 * Contract under test: enqueue() hands the job to the backend and RETURNS (the
 * caller's request never sends); consume() drains, runs the handler, deletes
 * the envelope, and on a handler throw re-publishes attempts+1 up to
 * RETRY_CAP before parking on tframe.notify.dl; a malformed envelope is
 * deleted and rethrown LOUDLY (the logger never swallows).
 */
final class LaravelQueueNotificationQueueTest extends TestCase
{
    public function test_enqueue_pushes_the_payload_onto_the_notify_queue_and_returns_the_job_id(): void
    {
        $manager = new FakeQueueManager;
        $queue = new LaravelQueueNotificationQueue($manager, 'database');

        $job = self::sampleJob();
        $id = $queue->enqueue($job);

        self::assertSame($job->jobId, $id);
        $pushed = $manager->pushed();
        self::assertCount(1, $pushed);
        self::assertSame(LaravelQueueNotificationQueue::QUEUE, $pushed[0]['queue']);
        self::assertSame($job->toArray(), $pushed[0]['payload']->payload);
        self::assertFalse($pushed[0]['payload']->deadLetter);
    }

    public function test_the_driver_is_async_and_names_the_canonical_dead_letter(): void
    {
        $queue = new LaravelQueueNotificationQueue(new FakeQueueManager, 'database');

        self::assertTrue($queue->isAsync());
        self::assertSame('tframe.notify', LaravelQueueNotificationQueue::QUEUE);
        self::assertSame('tframe.notify.dl', $queue->deadLetterName());
    }

    public function test_the_dl_key_is_the_one_the_other_drivers_answer(): void
    {
        // One opaque key across every driver (the tg:stream:dead-letter
        // precedent): sync, amqp and the laravel driver must agree.
        self::assertSame('tframe.notify.dl', AmqpNotificationQueue::DEAD_LETTER);
        self::assertSame(
            AmqpNotificationQueue::DEAD_LETTER,
            (new LaravelQueueNotificationQueue(new FakeQueueManager, 'database'))->deadLetterName(),
        );
    }

    public function test_consume_runs_the_handler_and_deletes_the_envelope(): void
    {
        $manager = new FakeQueueManager;
        $manager->queue()->seed(self::sampleJob());
        $queue = new LaravelQueueNotificationQueue($manager, 'database');

        $seen = [];
        $handled = $queue->consume(static function (NotificationJob $job) use (&$seen): void {
            $seen[] = $job->brief;
        }, 5);

        self::assertSame(1, $handled);
        self::assertSame(['first probe'], $seen);
        self::assertCount(1, $manager->deleted());
        self::assertSame([], $manager->pushed()); // nothing re-published on success
    }

    public function test_consume_on_an_empty_queue_handles_nothing(): void
    {
        $queue = new LaravelQueueNotificationQueue(new FakeQueueManager, 'database');

        self::assertSame(0, $queue->consume(static fn (): null => null, 3));
    }

    public function test_consume_stops_at_max_and_leaves_the_rest(): void
    {
        $manager = new FakeQueueManager;
        $manager->queue()->seed(self::sampleJob('a'));
        $manager->queue()->seed(self::sampleJob('b'));
        $manager->queue()->seed(self::sampleJob('c'));
        $queue = new LaravelQueueNotificationQueue($manager, 'database');

        $handled = $queue->consume(static fn (): null => null, 2);

        self::assertSame(2, $handled);
        self::assertCount(1, $manager->queue()->remaining()); // one left for the next drain
    }

    public function test_a_failing_handler_republishes_one_attempt_further_and_still_deletes(): void
    {
        $manager = new FakeQueueManager;
        $manager->queue()->seed(self::sampleJob(attempts: 0));
        $queue = new LaravelQueueNotificationQueue($manager, 'database');

        try {
            $queue->consume(static function (): void {
                throw new RuntimeException('telegram is down');
            });
            self::fail('the handler failure must propagate to the drain');
        } catch (RuntimeException $e) {
            self::assertSame('telegram is down', $e->getMessage());
        }

        // Re-published BEFORE the delete: a crashed drain loses nothing, and
        // the broker/table never holds the same envelope twice.
        self::assertCount(1, $manager->pushed());
        self::assertSame(LaravelQueueNotificationQueue::QUEUE, $manager->pushed()[0]['queue']);
        self::assertSame(1, $manager->pushed()[0]['payload']->payload['attempts']);
        self::assertCount(1, $manager->deleted());
        // The ORDER is the actual guarantee — the counts above cannot show it,
        // and deleting first is what loses the job if the drain then crashes.
        self::assertSame(
            ['push:'.LaravelQueueNotificationQueue::QUEUE, 'delete:tag-0'],
            $manager->events(),
        );
    }

    public function test_a_job_at_the_retry_cap_is_parked_on_the_dead_letter_queue(): void
    {
        $manager = new FakeQueueManager;
        $manager->queue()->seed(self::sampleJob(attempts: LaravelQueueNotificationQueue::RETRY_CAP));
        $queue = new LaravelQueueNotificationQueue($manager, 'database');

        $thrown = null;

        try {
            $queue->consume(static function (): void {
                throw new RuntimeException('still down');
            });
        } catch (RuntimeException $e) {
            $thrown = $e;
        }

        self::assertNotNull($thrown, 'the cap-exhausted failure still propagates to the drain');
        self::assertCount(1, $manager->pushed());
        self::assertSame(LaravelQueueNotificationQueue::DEAD_LETTER, $manager->pushed()[0]['queue']);
        self::assertTrue($manager->pushed()[0]['payload']->deadLetter);
        self::assertCount(1, $manager->deleted());
    }

    public function test_the_retry_cap_is_the_same_number_the_amqp_driver_parks_at(): void
    {
        self::assertSame(AmqpNotificationQueue::RETRY_CAP, LaravelQueueNotificationQueue::RETRY_CAP);
        self::assertSame(3, LaravelQueueNotificationQueue::RETRY_CAP);
    }

    public function test_a_malformed_envelope_is_deleted_and_rethrown_loudly_never_swallowed(): void
    {
        $manager = new FakeQueueManager;
        // Hand-edited row / another producer: data.command is not our job
        // object (no unserialize, so the assertion is about the LOUD path,
        // not about PHP's unserialize warning).
        $manager->queue()->seedRaw(['data' => ['command' => ['commandName' => 'Something\Else']]]);
        $queue = new LaravelQueueNotificationQueue($manager, 'database');

        $thrown = null;

        try {
            $queue->consume(static fn (): null => null);
        } catch (UnexpectedValueException $e) {
            $thrown = $e;
        }

        self::assertNotNull($thrown, 'a malformed envelope must be loud, not a silent drop');
        self::assertStringContainsString('malformed notify envelope', $thrown->getMessage());
        self::assertCount(1, $manager->deleted());
        self::assertSame([], $manager->pushed()); // no job to account for
    }

    public function test_the_unserialize_allow_list_blocks_a_hand_edited_row_instantiating_another_class(): void
    {
        // The malformed-envelope test above passes either way, because a payload
        // that is not ours is rejected on the instanceof check regardless. What
        // the allow-list alone buys is that unserialize() never CONSTRUCTS a
        // class the driver did not write — observable as PHP's incomplete-class
        // marker instead of a live object. Removing the allow-list keeps the
        // exception but quietly instantiates whatever the row names.
        $manager = new FakeQueueManager;
        $manager->queue()->seedRaw(['data' => ['command' => serialize(new \stdClass)]]);
        $queue = new LaravelQueueNotificationQueue($manager, 'database');

        $thrown = null;

        try {
            $queue->consume(static fn (): null => null);
        } catch (UnexpectedValueException $e) {
            $thrown = $e;
        }

        self::assertNotNull($thrown);
        self::assertStringContainsString(
            '__PHP_Incomplete_Class',
            $thrown->getMessage(),
            'a foreign serialized class must arrive as an incomplete class, never as an instantiated object',
        );
    }

    public function test_construction_never_touches_the_backend(): void
    {
        // The standalone-smoke posture: building the driver must not dial,
        // boot a worker, or resolve a container.
        $manager = new FakeQueueManager;
        $queue = new LaravelQueueNotificationQueue($manager, 'database');

        self::assertInstanceOf(LaravelQueueNotificationQueue::class, $queue);
        self::assertSame([], $manager->pushed());
        self::assertSame([], $manager->deleted());
    }

    private static function sampleJob(string $brief = 'first probe', int $attempts = 0): NotificationJob
    {
        return new NotificationJob(
            jobId: 'job-'.$brief,
            topic: 'app.info',
            level: 'info',
            brief: $brief,
            context: ['src' => 'test'],
            attachment: null,
            target: ['chat' => '@ops', 'thread_id' => 2],
            premium: false,
            attempts: $attempts,
            createdAt: 1750000000,
        );
    }
}
