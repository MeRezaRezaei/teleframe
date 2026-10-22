<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Notify;

/**
 * Duck-typed AMQP broker fake for AmqpNotificationQueue: records every
 * publish/ack/publishDead and serves published envelopes back through
 * get() in FIFO order — the round-trip and retry/DL paths run without
 * ext-amqp or a real broker. Mirrors the driver's injected-adapter
 * contract (publish/get/ack/publishDead) exactly.
 */
final class FakeAmqpAdapter
{
    /** @var list<string> JSON payloads published on the main queue */
    public array $published = [];

    /** @var list<string> JSON payloads parked on the dead-letter queue */
    public array $dead = [];

    /** @var list<int|string> acknowledged delivery tags, in order */
    public array $acked = [];

    /** @var list<string> envelopes awaiting consumption */
    private array $inbox = [];

    private int $tag = 0;

    public function publish(string $message): void
    {
        $this->published[] = $message;
        $this->inbox[] = $message;
    }

    /** @return array{body: string, delivery_tag: int}|null */
    public function get(): ?array
    {
        $body = array_shift($this->inbox);
        if ($body === null) {
            return null;
        }
        $this->tag++;

        return ['body' => $body, 'delivery_tag' => $this->tag];
    }

    public function ack(int|string $deliveryTag): void
    {
        $this->acked[] = $deliveryTag;
    }

    public function publishDead(string $message): void
    {
        $this->dead[] = $message;
    }
}
