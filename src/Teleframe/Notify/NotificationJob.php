<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Notify;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

/**
 * NotificationJob (design doc §5): the queued unit — one notification
 * send, carried as plain JSON on the wire via toArray()/fromArray().
 * Topic/level/brief/context/attachment/target/premium/attempts; never a
 * session string or token (targets carry chat ids / @usernames and
 * thread ids only — credentials resolve at consume time through the
 * transport seams). execute() is the consume-side entry: it runs the
 * given sender against THIS job and, on failure, logs at error and
 * ALWAYS rethrows — the queue's retry/dead-letter owns recovery, never
 * a silent drop (spec §7: the logger must never swallow).
 *
 * The sender is a parameter of execute(), not constructor state: a
 * closure cannot survive the JSON wire round-trip, so the queue/worker
 * resolves the real sender at consume time (sync handler or async
 * drain) and passes it in.
 */
final class NotificationJob
{
    /** @var list<string> the 8 PSR-3 backed level words */
    private const LEVELS = ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'];

    /**
     * @param  array<string, mixed>  $context
     * @param  array{name?: string, path?: string, mime?: string, bytes?: int}|null  $attachment
     * @param  array{chat?: int|string, thread_id?: int|null}|null  $target
     * @param  int|null  $createdAt  unix epoch; stamped at toArray() when null
     */
    public function __construct(
        public readonly string $jobId,
        public readonly string $topic,
        public readonly string $level,
        public readonly string $brief,
        public readonly array $context = [],
        public readonly ?array $attachment = null,
        public readonly ?array $target = null,
        public readonly bool $premium = false,
        public readonly int $attempts = 0,
        public readonly ?int $createdAt = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'job_id' => $this->jobId,
            'topic' => $this->topic,
            'level' => $this->level,
            'brief' => $this->brief,
            'context' => $this->context,
            'attachment' => $this->attachment,
            'target' => $this->target,
            'premium' => $this->premium,
            'attempts' => $this->attempts,
            'created_at' => $this->createdAt ?? time(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $jobId = $payload['job_id'] ?? null;
        $topic = $payload['topic'] ?? null;
        $level = $payload['level'] ?? null;
        $brief = $payload['brief'] ?? null;
        $context = $payload['context'] ?? [];
        $premium = $payload['premium'] ?? false;
        $attempts = $payload['attempts'] ?? 0;
        if (! is_string($jobId)
            || ! is_string($topic)
            || ! is_string($level)
            || ! in_array($level, self::LEVELS, true)
            || ! is_string($brief)
            || ! is_array($context)
            || ! is_int($attempts)
        ) {
            throw new InvalidArgumentException('notification job payload is missing required fields');
        }

        return new self(
            $jobId,
            $topic,
            $level,
            $brief,
            $context,
            self::nullableShape($payload['attachment'] ?? null),
            self::nullableShape($payload['target'] ?? null),
            is_bool($premium) ? $premium : false,
            $attempts,
            isset($payload['created_at']) && is_int($payload['created_at']) ? $payload['created_at'] : null,
        );
    }

    /**
     * Run this queued notification through the given sender and return
     * what the transport produced. The sender is resolved by the
     * queue/worker at consume time and is never serialized.
     *
     * Failure contract (spec §7 — the logger must never swallow): any
     * Throwable is logged at error level with the job's identity and
     * ALWAYS rethrown, so the queue's retry/dead-letter owns recovery —
     * sending never silently drops a notification.
     *
     * @param  callable(NotificationJob): mixed  $sender
     * @param  LoggerInterface|null  $logger  defaults to NullLogger (silent)
     */
    public function execute(callable $sender, ?LoggerInterface $logger = null): mixed
    {
        try {
            return $sender($this);
        } catch (Throwable $e) {
            ($logger ?? new NullLogger)->error('notification job failed', [
                'job_id' => $this->jobId,
                'topic' => $this->topic,
                'level' => $this->level,
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);
            throw $e;
        }
    }

    /**
     * Optional JSON sub-objects (attachment / target): keep the value
     * when it is an array, drop anything else to null.
     *
     * @return array<string, mixed>|null
     */
    private static function nullableShape(mixed $value): ?array
    {
        return is_array($value) ? $value : null;
    }
}
