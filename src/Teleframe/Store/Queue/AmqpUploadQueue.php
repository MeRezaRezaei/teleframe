<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Queue;

use RuntimeException;
use Throwable;

/**
 * AmqpUploadQueue (verbatim S9): ext-amqp direct queue for store uploads.
 * Durable queue `tstore.upload` bound to topic exchange
 * `teleframe.tstore`; enqueue publishes the UploadJob JSON; consume pulls
 * with manual ack — the worker acks only after upload+index committed, so
 * a crashed worker redelivers instead of losing the job.
 *
 * Failure is loud but time-boxed: without ext-amqp the constructor throws
 * (config said amqp, the operator must know). A broker that is down at
 * convenience time throws on the call, matching RabbitMQ's non-optional
 * role when `tstore.queue.driver=amqp` — the sync queue is the default;
 * choosing amqp is choosing the broker, and choosing it means choosing a
 * broker IDENTITY too (see assertUsableIdentity()).
 *
 * WHO ANSWERS "IS THE TRANSPORT THERE?" — AmqpExtension, not this class and
 * not the machine. The guard used to be an inline class_exists() here, which
 * made every amqp assertion a statement about the extensions the host
 * happened to load (GitLab pipeline #206: two tests green locally, erroring
 * in CI for a reason that had nothing to do with the wiring). The refusal
 * itself is unchanged — same throw, same moment, same message, never a
 * silent degrade to sync — it is now a named seam, so a caller can pin which
 * answer the constructor uses. Nothing dials at construction, so proving the
 * amqp path needs the transport fact, not a live connection.
 */
final class AmqpUploadQueue implements UploadQueueContract
{
    public const QUEUE = 'tstore.upload';

    public const EXCHANGE = 'teleframe.tstore';

    public const DEAD_QUEUE = 'tstore.upload.dl';

    private const ROUTING_KEY = 'tstore.upload';

    private const DEAD_ROUTING_KEY = 'tstore.upload.dl';

    /**
     * How many times one payload is offered to the handler before it is moved
     * to the dead-letter queue. Bounded on purpose: the previous behaviour was
     * to ack in a `finally`, so a handler that threw lost the job outright, and
     * the only alternative — never acking — turns one poison payload into an
     * infinite redelivery loop that starves every healthy job behind it.
     */
    public const MAX_ATTEMPTS = 3;

    private const PAYLOAD_KEY_ATTEMPTS = '_attempts';

    private ?\AMQPConnection $connection = null;

    private ?\AMQPChannel $channel = null;

    private ?\AMQPExchange $exchange = null;

    private ?\AMQPQueue $queue = null;

    /**
     * @param  array<string, string|int>  $config  host/port/user/pass/vhost
     * @param  ?AmqpExtension  $extension  the transport-availability fact to use;
     *                                     null = the ambient answer (AmqpExtension::detected(),
     *                                     which is detection unless a caller declared one)
     *
     * @throws RuntimeException when the transport cannot be used here — the
     *                          refusal is NOT softened into a sync degrade, see AmqpExtension
     */
    public function __construct(private readonly array $config = [], ?AmqpExtension $extension = null)
    {
        ($extension ?? AmqpExtension::detected())->assertLoaded();
    }

    /**
     * The effective broker config — the configured map with the defaults
     * merged, exactly the map connection() dials with. Reading it dials
     * nothing, which is what makes "the amqp driver carries the configured
     * broker/credentials through" assertable without a broker (and lets a
     * doctor-style preflight inspect the wiring offline).
     *
     * It CONTAINS the broker password: treat it as a secret, never log it.
     *
     * @return array<string, string|int>
     */
    public function brokerConfig(): array
    {
        return array_merge([
            'host' => '127.0.0.1',
            'port' => 5672,
            'user' => '',
            'pass' => '',
            'vhost' => '/',
            // Socket-level facts, so a half-open connection is detected by the
            // broker rather than by the OS TCP timeout on the first write.
            'heartbeat' => 30,
            'connect_timeout' => 3.0,
            'read_timeout' => 5.0,
            'write_timeout' => 5.0,
        ], $this->config);
    }

    public function enqueue(array $payload): string
    {
        $message = json_encode($payload, JSON_THROW_ON_ERROR);
        $this->publish($message);

        return (string) ($payload['account_id'] ?? '');
    }

    public function consume(callable $handler, int $max = 1): int
    {
        $queue = $this->queue();
        $handled = 0;
        while ($handled < $max) {
            $envelope = $queue->get();
            if (! $envelope) { // AMQPQueue::get() returns AMQPEnvelope|false (null on empty)
                break;
            }
            $payload = [];
            try {
                $decoded = json_decode((string) $envelope->getBody(), true, 512, JSON_THROW_ON_ERROR);
                $payload = is_array($decoded) ? $decoded : [];
                $handler($payload);
            } catch (Throwable $e) {
                // The disposition is made DURABLE (republished to the main
                // queue, or moved to the dead-letter queue) BEFORE the ack, and
                // the ack happens on every path. Acking first would lose the
                // job; not acking at all would redeliver forever. Republish-
                // then-ack is at-least-once, which is the only honest option
                // without a transaction, and it is strictly better than the
                // ack-in-finally this replaced.
                $this->dispose($payload, $e);
            }
            $queue->ack($envelope->getDeliveryTag());
            $handled++;
        }

        return $handled;
    }

    /**
     * Record a handler failure durably: requeue with a stamped attempt count,
     * or dead-letter once MAX_ATTEMPTS is reached.
     *
     * The failure reason travels with the payload so a dead-lettered job is
     * diagnosable from the queue alone, without a log scrape.
     */
    private function dispose(array $payload, Throwable $failure): void
    {
        $attempts = (int) ($payload[self::PAYLOAD_KEY_ATTEMPTS] ?? 0) + 1;
        $payload[self::PAYLOAD_KEY_ATTEMPTS] = $attempts;
        $payload['_last_error'] = $failure->getMessage();
        $payload['_last_error_class'] = $failure::class;

        try {
            if ($attempts >= self::MAX_ATTEMPTS) {
                $this->publishTo(self::DEAD_QUEUE, self::DEAD_ROUTING_KEY, $payload);
            } else {
                $this->publishTo(self::QUEUE, self::ROUTING_KEY, $payload);
            }
        } catch (Throwable $e) {
            // The requeue itself failed — the broker is gone. Acking now would
            // lose the job; refusing to ack leaves it pending, where an operator
            // (or the next consumer) can still find it. Loud beats silent loss.
            throw new RuntimeException(
                'tstore upload job failed ('.$failure->getMessage().') AND could not be requeued or '
                .'dead-lettered ('.$e->getMessage().') — the message is left UNACKED on '.self::QUEUE
                .'. It is not lost, but it is stalled until the broker returns.',
                0,
                $e,
            );
        }
    }

    public function isAsync(): bool
    {
        return true;
    }

    /**
     * Remove every pending message (tests / operators draining a stale
     * durable queue). Ensuring the queue first makes the first call safe
     * even when the broker has never seen tstore.upload.
     */
    public function purge(): void
    {
        $this->queue()->purge();
    }

    private function publish(string $message): void
    {
        $this->publishTo(self::QUEUE, self::ROUTING_KEY, $message);
    }

    /**
     * Publish one message to a named durable queue on the store exchange.
     *
     * Declare + bind BEFORE publishing: on first run the broker has neither
     * exchange nor queue, and a message published to a not-yet-bound queue is
     * dropped. Declarations are idempotent; the exchange must exist before a
     * queue binds to it.
     *
     * The retry is bounded by WALL TIME, not by attempt count, so a broker
     * outage cannot hang the caller indefinitely: a half-open TCP connection
     * otherwise blocks for the OS timeout on the first write.
     */
    private function publishTo(string $queueName, string $routingKey, string|array $message): void
    {
        if (is_array($message)) {
            $message = json_encode($message, JSON_THROW_ON_ERROR);
        }

        $queue = $this->declareQueue($queueName, $routingKey);
        $this->exchange();

        $deadline = microtime(true) + (float) ($this->config['publish_retry_budget_seconds'] ?? 5.0);
        $delayUs = 100_000; // 100ms, doubling, capped below
        $last = null;

        do {
            try {
                $this->exchange()->publish($message, $routingKey, \AMQP_NOPARAM, [
                    'content_type' => 'application/json',
                    'delivery_mode' => 2, // persistent
                ]);

                return;
            } catch (Throwable $e) {
                $last = $e;
                // A dead connection must not be reused: drop the cached objects
                // so channel() rebuilds them off a fresh connection.
                $this->reset();
                if (microtime(true) >= $deadline) {
                    break;
                }
                usleep($delayUs);
                $delayUs = min($delayUs * 2, 1_000_000);
            }
        } while (true);

        throw new RuntimeException(
            'amqp upload queue could not publish to '.$queueName.' within the retry budget: '
            .$last->getMessage(),
            0,
            $last,
        );
    }

    private function declareQueue(string $name, string $routingKey): \AMQPQueue
    {
        $queue = new \AMQPQueue($this->channel());
        $queue->setName($name);
        $queue->setFlags(\AMQP_DURABLE);
        $queue->declareQueue();
        // exchange() declares the exchange (idempotent) before the bind.
        $this->exchange();
        $queue->bind(self::EXCHANGE, $routingKey);

        return $queue;
    }

    private function reset(): void
    {
        $this->queue = null;
        $this->exchange = null;
        $this->channel = null;
        $this->connection = null;
    }

    private function queue(): \AMQPQueue
    {
        if ($this->queue !== null) {
            return $this->queue;
        }
        $this->exchange();
        // The dead-letter queue is declared UP FRONT, not lazily on first
        // failure. An operator must be able to see that tstore.upload has a
        // dead-letter destination before anything has gone wrong; declaring it
        // only when it is first needed means the first failure also has to
        // create infrastructure, and that create is another thing that can
        // fail while the job is already in trouble.
        $this->declareQueue(self::DEAD_QUEUE, self::DEAD_ROUTING_KEY);
        $this->queue = $this->declareQueue(self::QUEUE, self::ROUTING_KEY);

        return $this->queue;
    }

    private function exchange(): \AMQPExchange
    {
        if ($this->exchange !== null) {
            return $this->exchange;
        }
        $exchange = new \AMQPExchange($this->channel());
        $exchange->setName(self::EXCHANGE);
        $exchange->setType(\AMQP_EX_TYPE_TOPIC);
        $exchange->setFlags(\AMQP_DURABLE);
        $exchange->declareExchange();
        $this->exchange = $exchange;

        return $exchange;
    }

    private function channel(): \AMQPChannel
    {
        if ($this->channel !== null && $this->channel->isConnected()) {
            return $this->channel;
        }
        $connection = $this->connection();
        $this->channel = new \AMQPChannel($connection);

        return $this->channel;
    }

    private function connection(): \AMQPConnection
    {
        if ($this->connection !== null && $this->connection->isConnected()) {
            return $this->connection;
        }
        $config = $this->brokerConfig();
        self::assertUsableIdentity((string) $config['host'], (string) $config['user'], (string) $config['pass']);
        try {
            $connection = new \AMQPConnection($config);
            $connection->connect();
        } catch (Throwable $e) {
            throw new RuntimeException(
                'amqp upload queue unreachable at '.$config['host'].':'.$config['port'].' — '.$e->getMessage(),
                0,
                $e,
            );
        }
        $this->connection = $connection;

        return $connection;
    }

    /**
     * Broker identity preflight — refuses to dial with the stock guest/guest
     * pair against anything but a loopback broker.
     *
     * guest/guest is a well-known credential: every default RabbitMQ install
     * ships it, and `tstore.upload` accepts a payload that (before the
     * UploadJob confinement) named a local path. Shipping it as the DEFAULT
     * would mean "anyone who can reach the broker can publish a job". On
     * loopback the broker is unreachable off-host anyway, so local development
     * keeps working; anywhere else the host must configure a dedicated
     * identity. Public so `teleframe:doctor`-style preflights can check the
     * config without dialing.
     *
     * @throws RuntimeException when the identity is the stock pair on a remote host
     */
    public static function assertUsableIdentity(string $host, string $user, string $pass): void
    {
        $user = $user === '' ? 'guest' : $user;
        $pass = $pass === '' ? 'guest' : $pass;

        if ($user !== 'guest' || $pass !== 'guest') {
            return;
        }

        foreach (['127.0.0.1', '::1', 'localhost'] as $loopback) {
            if (strcasecmp($host, $loopback) === 0) {
                return; // local development broker
            }
        }

        throw new RuntimeException(
            'refusing to dial the amqp upload queue at '.$host.' with the stock guest/guest broker identity: '
            .'anyone who can publish to '.self::QUEUE.' could drive the worker. Set dedicated credentials '
            .'(TELEFRAME_TSTORE_AMQP_USER / TELEFRAME_TSTORE_AMQP_PASSWORD) or point the driver at a loopback broker.'
        );
    }
}
