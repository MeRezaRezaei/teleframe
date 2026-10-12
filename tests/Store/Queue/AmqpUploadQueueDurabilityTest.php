<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store\Queue;

use AMQPChannel;
use AMQPConnection;
use AMQPEnvelope;
use AMQPQueue;
use MeRezaRezaei\Teleframe\Store\Cache\MemoryHotCache;
use MeRezaRezaei\Teleframe\Store\Index\MemoryStoreIndex;
use MeRezaRezaei\Teleframe\Store\ObjectManifest;
use MeRezaRezaei\Teleframe\Store\Queue\AmqpUploadQueue;
use MeRezaRezaei\Teleframe\Store\Queue\SyncUploadQueue;
use MeRezaRezaei\Teleframe\Store\Queue\UploadJob;
use MeRezaRezaei\Teleframe\Store\StoreService;
use MeRezaRezaei\Teleframe\Store\TelegramStoreChannel;
use MeRezaRezaei\Teleframe\Tests\Support\FakeStoreApi;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

/**
 * THE DURABILITY FIX, PINNED (the four properties it claims).
 *
 * Before the fix, consume() acked the envelope from a `finally`, so a handler
 * that threw LOST the job outright; and StoreService::putFromJob() unlinked
 * the spooled source from its own `finally`, so the retry the queue now
 * performs had nothing left to send. Two silent data losses, one of them a
 * total loss of the only copy of the bytes.
 *
 * After: consume() makes a DURABLE disposition first (stamp `_attempts`,
 * attach `_last_error`, republish to tstore.upload or — at MAX_ATTEMPTS — to
 * tstore.upload.dl) and only then acks; putFromJob() unlinks only after the
 * upload has RETURNED.
 *
 * ── WHY THESE TESTS NEED ext-amqp AND A BROKER, STATED PLAINLY ─────────
 *
 * The tempting version of this file is a set of hand-rolled AMQP doubles
 * injected by reflection. That version is WORTHLESS and this one deliberately
 * refuses to be it:
 *
 *   • ext-amqp classes are NOT free to re-declare. When the extension is
 *     loaded, AMQPConnection/AMQPChannel/AMQPExchange/AMQPQueue/AMQPEnvelope
 *     already exist in the global namespace, so a stub definition is a fatal
 *     redeclare. Subclassing them instead does not help: AmqpUploadQueue is
 *     `final` and, worse, publishTo() hard-codes `new \AMQPQueue(...)` and
 *     `new \AMQPExchange(...)` internally, so there is no seam to inject
 *     through. A subclass that skips the parent constructor yields
 *     "Stale reference to the channel object" on the first declare, and one
 *     that calls it needs a real connection. There is no way to run the real
 *     dispose()/publishTo() logic without a real channel.
 *
 *   • So the honest options are (b) skip, or run against a real broker. This
 *     file runs against the real thing, gated on a bounded reachability
 *     probe, and SKIPS WITH THE EXACT REASON when there is no broker or no
 *     ext-amqp. That is the opposite of the defect this file exists to
 *     remove: here the assertions execute the production ack/dispose/requeue
 *     path or they are not reported as a pass. In an environment with no
 *     broker these three tests are SKIPPED — three skips are not three
 *     passes, and they prove nothing about a broker.
 *
 * The frozen queue names are not negotiable: this suite publishes into the
 * real `tstore.upload` / `tstore.upload.dl` an operator would be watching, so
 * every test purges both on entry and on exit and leaves nothing behind.
 *
 * Case 4 needs no broker at all and runs unconditionally.
 */
final class AmqpUploadQueueDurabilityTest extends TestCase
{
    private const BUCKET = 'media-pool';

    /**
     * Bounded by design: a 1s connect and 2s read/write mean a missing or
     * half-open broker surfaces as a failure, never as a hang. The whole file
     * is also run behind an outer `timeout` by the caller.
     *
     * @return array<string, string|int|float>
     */
    private function brokerConfig(): array
    {
        return [
            'host' => '127.0.0.1',
            'port' => 5672,
            'user' => 'guest',
            'pass' => 'guest',
            'vhost' => '/',
            'heartbeat' => 0,
            'connect_timeout' => 1.0,
            'read_timeout' => 2.0,
            'write_timeout' => 2.0,
            'publish_retry_budget_seconds' => 2.0,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('amqp')) {
            self::markTestSkipped(
                'ext-amqp is not loaded, so AmqpUploadQueue cannot be constructed at all '
                .'(its constructor calls assertLoaded()). SKIPPED, NOT PASSED: the ack/dispose/requeue '
                .'path is unexercised in this environment.'
            );
        }

        $connection = null;
        try {
            $connection = new AMQPConnection($this->brokerConfig());
            $connection->connect();
        } catch (Throwable $e) {
            self::markTestSkipped(
                'no RabbitMQ broker reachable at 127.0.0.1:5672 ('.$e->getMessage().'). SKIPPED, NOT '
                .'PASSED: AmqpUploadQueue::publishTo() constructs \AMQPQueue/\AMQPExchange internally, so '
                .'its retry + requeue logic is unexercised without a real channel.'
            );
        } finally {
            if ($connection instanceof AMQPConnection && $connection->isConnected()) {
                $connection->disconnect();
            }
        }

        // The names are shared with a live operator-visible broker: leave it as found.
        $this->purge(AmqpUploadQueue::QUEUE);
        $this->purge(AmqpUploadQueue::DEAD_QUEUE);
    }

    protected function tearDown(): void
    {
        $this->purge(AmqpUploadQueue::QUEUE);
        $this->purge(AmqpUploadQueue::DEAD_QUEUE);

        parent::tearDown();
    }

    // ── the three broker-backed properties ──────────────────────────────

    /**
     * PROPERTY 1 — a throwing handler loses nothing.
     *
     * The job comes back, carrying proof of what killed it, and the requeued
     * envelope is itself acked (so a poison payload cannot spin forever).
     */
    public function test_a_throwing_handler_republishes_the_job_with_an_attempt_stamp_and_does_not_swallow_it(): void
    {
        $queue = $this->queue();
        $marker = $this->marker();
        $queue->enqueue($this->payload($marker));

        $handled = $queue->consume(static function (array $payload): void {
            throw new RuntimeException('durability-guard: handler refused the job');
        }, max: 1);
        $this->assertSame(1, $handled, 'the envelope was consumed and dispositioned');

        $requeued = [];
        $drained = $queue->consume(static function (array $payload) use (&$requeued): void {
            $requeued[] = $payload;
        }, max: 5);

        $this->assertSame(1, $drained, 'the failed job must be back on tstore.upload, not gone');
        $this->assertCount(1, $requeued);
        $this->assertSame($marker, $requeued[0]['marker'] ?? null, 'the payload survived the round trip intact');
        $this->assertSame(1, $requeued[0]['_attempts'] ?? null, 'the attempt counter was stamped on requeue');
        $this->assertNotEmpty($requeued[0]['_last_error'] ?? null, 'a requeued job says why it failed');
        $this->assertStringContainsString('handler refused the job', (string) $requeued[0]['_last_error']);
        $this->assertSame(RuntimeException::class, $requeued[0]['_last_error_class'] ?? null);

        // Bounded retry: the requeued envelope is acked too, so it is not
        // redelivered in a tight loop behind every healthy job.
        $this->assertSame(0, $queue->consume(static fn (array $p): null => null, max: 5), 'requeued envelope was acked');
        $this->assertSame([], $this->deadLetterBodies(), 'a first failure is not a dead letter');
    }

    /**
     * PROPERTY 2 — the poison job stops somewhere, on purpose.
     *
     * MAX_ATTEMPTS is the whole point of the bounded retry: at the cap the
     * payload goes to tstore.upload.dl under the dead routing key, diagnosable
     * from the queue alone, and NOT back to the main queue.
     */
    public function test_a_handler_failing_at_max_attempts_is_dead_lettered_not_requeued(): void
    {
        $queue = $this->queue();
        $marker = $this->marker();

        // Arrive already stamped one below the cap, so THIS failure is the
        // one that reaches it.
        $queue->enqueue($this->payload($marker, ['_attempts' => AmqpUploadQueue::MAX_ATTEMPTS - 1]));

        $handled = $queue->consume(static function (array $payload): void {
            throw new RuntimeException('durability-guard: still refusing');
        }, max: 1);
        $this->assertSame(1, $handled);

        $this->assertSame(
            0,
            $queue->consume(static fn (array $p): null => null, max: 5),
            'a job at MAX_ATTEMPTS must not go back to tstore.upload'
        );

        $dead = $this->deadLetterBodies();
        $this->assertCount(1, $dead, 'the job must be on tstore.upload.dl');
        $this->assertSame($marker, $dead[0]['marker'] ?? null, 'the dead letter carries the original payload');
        $this->assertSame(AmqpUploadQueue::MAX_ATTEMPTS, $dead[0]['_attempts'] ?? null);
        $this->assertNotEmpty($dead[0]['_last_error'] ?? null, 'a dead-lettered job must be diagnosable from the queue');
        $this->assertStringContainsString('still refusing', (string) $dead[0]['_last_error']);
    }

    /**
     * PROPERTY 3 — the fix does not over-correct.
     *
     * A job that uploads fine is acked and goes NOWHERE: not back onto
     * tstore.upload, not to the dead-letter queue. This is the case a
     * "republish everything" fix would silently break.
     */
    public function test_a_successful_handler_acks_the_job_and_republishes_nothing(): void
    {
        $queue = $this->queue();
        $marker = $this->marker();
        $queue->enqueue($this->payload($marker));

        $seen = [];
        $handled = $queue->consume(function (array $payload) use (&$seen): void {
            $seen[] = $payload;
        }, max: 1);

        $this->assertSame(1, $handled);
        $this->assertCount(1, $seen);
        $this->assertSame($marker, $seen[0]['marker'] ?? null, 'the handler saw the payload it was given');
        $this->assertArrayNotHasKey('_attempts', $seen[0], 'a success is not an attempt');
        $this->assertArrayNotHasKey('_last_error', $seen[0]);

        $this->assertSame(0, $queue->consume(static fn (array $p): null => null, max: 5), 'a delivered job is acked, not redelivered');
        $this->assertSame([], $this->deadLetterBodies(), 'a success is never dead-lettered');
    }

    // ── property 4: the spooled source outlives a failed upload ──────────

    /**
     * PROPERTY 4 — the retry has something to retry WITH.
     *
     * The unlink used to live in a `finally`, so an upload that threw deleted
     * the only copy of the bytes while the queue (before its own fix) acked
     * the message: a transient Telegram error destroyed the object outright.
     */
    public function test_a_failed_upload_leaves_the_source_on_disk_and_a_successful_one_removes_it(): void
    {
        UploadJob::allowSourceRoot((string) realpath(sys_get_temp_dir()));

        // ── the upload throws: the source MUST survive ──
        $kept = $this->spooledSource('durability-keep.bin');
        $failing = $this->service(alwaysFailingUploads: true);
        try {
            $failing->putFromJob($this->jobPayload($kept));
            $this->fail('the upload was supposed to throw');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('durability-guard: upload refused', $e->getMessage());
        }
        $this->assertFileExists(
            $kept,
            'a failed upload must leave source_path on disk — the requeued retry needs those bytes'
        );
        unlink($kept);

        // ── the upload succeeds: the source is cleaned up ──
        $removed = $this->spooledSource('durability-remove.bin');
        $this->service()->putFromJob($this->jobPayload($removed));
        $this->assertFileDoesNotExist($removed, 'a successful upload still owns the spool file');
    }

    // ── helpers ─────────────────────────────────────────────────────────

    private function queue(): AmqpUploadQueue
    {
        return new AmqpUploadQueue($this->brokerConfig());
    }

    private function marker(): string
    {
        return 'dur-'.bin2hex(random_bytes(6));
    }

    /** @return array<string, mixed> */
    private function payload(string $marker, array $extra = []): array
    {
        return $extra + [
            'account_id' => 7,
            'bucket' => self::BUCKET,
            'name' => $marker.'.bin',
            'mode' => 'raw',
            'source_path' => '/tmp/'.$marker,
            'size' => 3,
            'marker' => $marker,
        ];
    }

    /**
     * Drain tstore.upload.dl through a raw channel, returning the decoded
     * bodies. Consume() only ever binds the main queue, so the dead-letter
     * destination has to be read directly — which is also what proves the
     * payload landed there and not on the main queue.
     *
     * @return list<array<string, mixed>>
     */
    private function deadLetterBodies(): array
    {
        $connection = new AMQPConnection($this->brokerConfig());
        $connection->connect();
        try {
            $queue = new AMQPQueue(new AMQPChannel($connection));
            $queue->setName(AmqpUploadQueue::DEAD_QUEUE);

            $bodies = [];
            // Bounded: a DL holding more than this is already a failure, and
            // an unbounded drain would be a hang.
            for ($i = 0; $i < 20; $i++) {
                $envelope = $queue->get();
                if (! $envelope instanceof AMQPEnvelope) {
                    break;
                }
                $bodies[] = json_decode((string) $envelope->getBody(), true, 512, JSON_THROW_ON_ERROR);
                $queue->ack($envelope->getDeliveryTag());
            }

            return $bodies;
        } finally {
            $connection->disconnect();
        }
    }

    private function purge(string $name): void
    {
        try {
            $connection = new AMQPConnection($this->brokerConfig());
            $connection->connect();
        } catch (Throwable) {
            return; // no broker: nothing to leave behind
        }

        try {
            $queue = new AMQPQueue(new AMQPChannel($connection));
            $queue->setName($name);
            $queue->setFlags(\AMQP_DURABLE);
            $queue->declareQueue();
            $queue->purge();
        } catch (Throwable) {
            // A queue the broker has not seen is already empty.
        } finally {
            $connection->disconnect();
        }
    }

    private function spooledSource(string $suffix): string
    {
        $path = tempnam(sys_get_temp_dir(), 'tfdur-');
        if ($path === false) {
            self::fail('could not spool a temp source file');
        }
        file_put_contents($path, 'durable-bytes-'.$suffix);

        return $path;
    }

    /** @return array<string, mixed> */
    private function jobPayload(string $sourcePath): array
    {
        return [
            'account_id' => 7,
            'bucket' => self::BUCKET,
            'name' => 'durability-'.bin2hex(random_bytes(4)).'.bin',
            'mode' => ObjectManifest::MODE_RAW,
            'source_path' => $sourcePath,
            'size' => (int) filesize($sourcePath),
        ];
    }

    /**
     * @param  bool  $alwaysFailingUploads  make every byte-level upload throw
     */
    private function service(bool $alwaysFailingUploads = false): StoreService
    {
        $api = new FakeStoreApi(scope: 'durability');
        $map = $api->apiMap();
        if ($alwaysFailingUploads) {
            // Fails at the real upload depth, past the chunk planner and the
            // store channel, and is NOT a flood/migration error, so
            // ResilientCall rethrows it instead of retrying.
            $map['uploadPart'] = static function (): array {
                throw new RuntimeException('durability-guard: upload refused');
            };
        }

        return new StoreService(
            accountId: 7,
            channelFactory: static fn (string $bucket): TelegramStoreChannel => new TelegramStoreChannel(
                $bucket,
                $map,
                sleep: static fn (int $seconds): int => 0, // never sleep in a test
            ),
            index: new MemoryStoreIndex,
            cache: new MemoryHotCache(ttlSeconds: 60, maxBytes: 1 << 20),
            queue: new SyncUploadQueue,
        );
    }
}
