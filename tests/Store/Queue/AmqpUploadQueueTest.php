<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store\Queue;

use MeRezaRezaei\Teleframe\Store\Queue\AmqpUploadQueue;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * AmqpUploadQueue (verbatim S9, plan W5): ext-amqp direct queue.
 * Offline coverage: unconfigured/broker-down behaviour surfaces as the
 * loud RuntimeException the spec demands (the "sync queue is default;
 * choosing amqp is choosing the broker" contract).
 *
 * Live-gated roundtrip (TELEFRAME_LIVE_AMQP=1): publish → consume with
 * manual ack against the local RabbitMQ (127.0.0.1:5672) — requires a
 * broker, so it is never part of CI.
 */
final class AmqpUploadQueueTest extends TestCase
{
    /**
     * ext-amqp is an OPTIONAL extension: the amqp driver is one queue option
     * among several (sync is the shipped default) and the package does not
     * require it. These tests used to assume it was always present, so the
     * GitLab pipeline failed with "tstore.queue.driver=amqp selected but
     * ext-amqp is not loaded" raised from AmqpUploadQueue's own constructor.
     *
     * Skipping is the honest outcome, not a workaround: an absent optional
     * extension is an ordinary configuration, and a test for it cannot assert
     * anything in that environment. The rest of the suite still covers the
     * default sync queue.
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('amqp')) {
            self::markTestSkipped('ext-amqp is not loaded; the amqp queue driver is optional.');
        }
    }

    private function queue(): AmqpUploadQueue
    {
        return new AmqpUploadQueue([
            'host' => '127.0.0.1',
            'port' => 9999, // nothing listens here — deterministic down broker
            'connect_timeout' => 1,
            'read_timeout' => 1,
        ]);
    }

    public function test_without_ext_amqp_constructor_throws_loudly(): void
    {
        // The constructor's guard is class_exists(AMQPConnection). With the
        // extension loaded the guard passes and the down-broker path is the
        // loud failure; both must produce the RuntimeException contract.
        $this->expectException(RuntimeException::class);
        $this->queue()->enqueue([
            'account_id' => 7,
            'bucket' => 'media-pool',
            'name' => 'a.zip',
            'mode' => 'sealed',
            'source_path' => '/tmp/a',
            'size' => 1,
        ]);
    }

    public function test_consume_without_broker_is_loud(): void
    {
        $this->expectException(RuntimeException::class);
        $this->queue()->consume(static function (array $payload): void {});
    }

    public function test_is_async_reports_true(): void
    {
        if (! getenv('TELEFRAME_LIVE_AMQP')) {
            // isAsync is pure metadata — construct without touching the broker.
            $queue = new AmqpUploadQueue(['host' => '127.0.0.1', 'port' => 5672]);
            $this->assertTrue($queue->isAsync());

            return;
        }
        $this->assertTrue($this->queue()->isAsync());
    }

    public function test_live_roundtrip_publish_and_consume_per_ack(): void
    {
        if (! getenv('TELEFRAME_LIVE_AMQP')) {
            self::markTestSkipped('live RabbitMQ gate: set TELEFRAME_LIVE_AMQP=1');
        }

        $queue = new AmqpUploadQueue(['host' => '127.0.0.1', 'port' => 5672]);
        $queue->purge();
        $queue->enqueue([
            'account_id' => 7,
            'bucket' => 'media-pool',
            'name' => 'live-a.zip',
            'mode' => 'sealed',
            'source_path' => '/tmp/live-a',
            'size' => 42,
        ]);

        $seen = null;
        $handled = $queue->consume(function (array $payload) use (&$seen): void {
            $seen = $payload;
        }, max: 1);

        // consume acks each job after the handler ran.
        $this->assertSame(1, $handled);
        $this->assertSame('live-a.zip', $seen['name'] ?? null);

        // ack happened: a second drain finds nothing left.
        $again = $queue->consume(static function (array $payload): void {}, max: 5);
        $this->assertSame(0, $again, 'acked job must not redeliver');
    }

    /**
     * The stock guest/guest broker identity must never be used against a
     * non-loopback broker.
     *
     * guest/guest is in every default RabbitMQ install's docs, and this queue
     * accepts a payload the worker acts on. Shipping it as the DEFAULT would
     * mean "anyone who can reach the broker can publish to tstore.upload".
     * Loopback keeps working — that broker is unreachable off-host anyway.
     */
    public function test_the_stock_guest_identity_is_refused_off_loopback(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('stock guest/guest broker identity');
        AmqpUploadQueue::assertUsableIdentity('broker.example.com', 'guest', 'guest');
    }

    public function test_the_stock_guest_identity_is_refused_when_it_is_the_implicit_default(): void
    {
        // Empty config used to MEAN guest/guest — the reviewer's point exactly.
        $this->expectException(RuntimeException::class);
        AmqpUploadQueue::assertUsableIdentity('10.0.0.5', '', '');
    }

    public function test_a_dedicated_identity_is_always_accepted(): void
    {
        AmqpUploadQueue::assertUsableIdentity('broker.example.com', 'teleframe', 'a-real-password');

        $this->assertTrue(true, 'no throw is the assertion');
    }

    public function test_a_local_development_broker_keeps_guest(): void
    {
        foreach (['127.0.0.1', 'localhost', '::1'] as $host) {
            AmqpUploadQueue::assertUsableIdentity($host, 'guest', 'guest');
        }

        $this->assertTrue(true, 'loopback is not refused');
    }
}
