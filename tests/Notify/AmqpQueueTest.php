<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Notify;

use MeRezaRezaei\Teleframe\Notify\AmqpNotificationQueue;
use MeRezaRezaei\Teleframe\Notify\NotificationJob;
use MeRezaRezaei\Teleframe\Notify\SyncNotificationQueue;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * AmqpNotificationQueue (design §5, wave 2): the async queue driver over an
 * injected duck-typed adapter. Covers the wire keys (queue/exchange/routing
 * key/dead letter/cap), the enqueue→consume round-trip, handler-failure
 * repost with attempts+1, the retry-cap → dead-letter parking, the lazy
 * adapter factory, and the loud malformed-envelope gate.
 */
final class AmqpQueueTest extends TestCase
{
    public function test_constants_match_the_design_wire_keys(): void
    {
        self::assertSame('tframe.notify', AmqpNotificationQueue::QUEUE);
        self::assertSame('teleframe.notify', AmqpNotificationQueue::EXCHANGE);
        self::assertSame('tframe.notify', AmqpNotificationQueue::ROUTING_KEY);
        self::assertSame('tframe.notify.dl', AmqpNotificationQueue::DEAD_LETTER);
        self::assertSame(3, AmqpNotificationQueue::RETRY_CAP);
        self::assertSame(SyncNotificationQueue::DEAD_LETTER, AmqpNotificationQueue::DEAD_LETTER);

        $queue = new AmqpNotificationQueue(new FakeAmqpAdapter);
        self::assertTrue($queue->isAsync());
        self::assertSame('tframe.notify.dl', $queue->deadLetterName());
    }

    public function test_round_trip_enqueue_then_consume(): void
    {
        $adapter = new FakeAmqpAdapter;
        $queue = new AmqpNotificationQueue($adapter);

        $job = new NotificationJob('j-1', 'app.info', 'info', 'hello', ['account_id' => 7], null, ['chat' => '@ops', 'thread_id' => 4], false, 0);
        self::assertSame('j-1', $queue->enqueue($job));

        $payload = json_decode($adapter->published[0], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('j-1', $payload['job_id']);
        self::assertSame('app.info', $payload['topic']);
        self::assertSame(0, $payload['attempts']);

        $handled = [];
        $count = $queue->consume(static function (NotificationJob $consumed) use (&$handled): void {
            $handled[] = $consumed;
        });

        self::assertSame(1, $count);
        self::assertCount(1, $handled);
        self::assertSame('j-1', $handled[0]->jobId);
        self::assertSame('app.info', $handled[0]->topic);
        self::assertSame(['account_id' => 7], $handled[0]->context);
        self::assertSame(0, $handled[0]->attempts);
        self::assertSame([1], $adapter->acked);
    }

    public function test_handler_throw_reposts_with_attempts_plus_one(): void
    {
        $adapter = new FakeAmqpAdapter;
        $queue = new AmqpNotificationQueue($adapter);
        $queue->enqueue(new NotificationJob('j-2', 'app.info', 'info', 'hello', [], null, null, false, 0));

        try {
            $queue->consume(static function (NotificationJob $job): void {
                throw new RuntimeException('handler failed');
            });
            self::fail('the handler failure must propagate to the worker');
        } catch (RuntimeException $e) {
            self::assertSame('handler failed', $e->getMessage());
        }

        self::assertCount(2, $adapter->published); // original + the repost
        $repost = json_decode($adapter->published[1], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('j-2', $repost['job_id']);
        self::assertSame(1, $repost['attempts']);
        self::assertSame([], $adapter->dead);
        self::assertCount(1, $adapter->acked); // envelope acked in the finally
    }

    public function test_cap_exhausted_job_parks_on_the_dead_letter_queue(): void
    {
        $adapter = new FakeAmqpAdapter;
        $queue = new AmqpNotificationQueue($adapter);
        $queue->enqueue(new NotificationJob('j-3', 'app.error', 'error', 'boom', [], null, null, false, AmqpNotificationQueue::RETRY_CAP));

        try {
            $queue->consume(static function (NotificationJob $job): void {
                throw new RuntimeException('still failing');
            });
            self::fail('the handler failure must propagate to the worker');
        } catch (RuntimeException $e) {
            self::assertSame('still failing', $e->getMessage());
        }

        self::assertCount(1, $adapter->dead); // parked, not reposted
        self::assertCount(1, $adapter->published); // only the original enqueue
        self::assertCount(1, $adapter->acked);

        $parked = json_decode($adapter->dead[0], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('j-3', $parked['job_id']);
        self::assertSame(3, $parked['attempts']);
    }

    public function test_empty_queue_consume_returns_zero(): void
    {
        $adapter = new FakeAmqpAdapter;
        $queue = new AmqpNotificationQueue($adapter);

        self::assertSame(0, $queue->consume(static function (NotificationJob $job): void {}));
        self::assertSame([], $adapter->acked);
    }

    public function test_adapter_factory_resolves_lazily_on_first_use(): void
    {
        $adapter = new FakeAmqpAdapter;
        $resolved = 0;
        $queue = new AmqpNotificationQueue(static function () use (&$resolved, $adapter): FakeAmqpAdapter {
            $resolved++;

            return $adapter;
        });

        self::assertSame(0, $resolved); // construction never touches the broker
        $queue->enqueue(new NotificationJob('j-4', 'app.info', 'info', 'x'));
        self::assertSame(1, $resolved);
        self::assertCount(1, $adapter->published);
    }

    public function test_malformed_envelope_is_loud(): void
    {
        $adapter = new class
        {
            public function publish(string $message): void {}

            public function get(): array
            {
                return ['delivery_tag' => 1]; // no body
            }

            public function ack(int|string $deliveryTag): void {}

            public function publishDead(string $message): void {}
        };
        $queue = new AmqpNotificationQueue($adapter);

        try {
            $queue->consume(static function (NotificationJob $job): void {});
            self::fail('a malformed envelope must not be silently consumed');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('malformed envelope', $e->getMessage());
        }
    }
}
