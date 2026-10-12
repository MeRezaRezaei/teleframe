<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Notify;

/**
 * Fluent notification builder (design §2, PendingNotify) — what
 * NotifyService::to() returns and what the facade chain
 * ->topic()/->level()/->to()/->with()/->attach()/->premium() then
 * ->send()/->queue() drives. Immutable: every fluent method returns a
 * NEW instance (readonly state). send() enqueues the compiled
 * NotificationJob through the injected NotificationQueueContract and
 * returns the job's wire array (deterministic, inspectable); queue()
 * enqueues and returns only the job id. Both are the loud gate for an
 * unresolvable send — no queue wired or no target chat (unknown topic,
 * design §4) throws NotifyException; nothing silently drops.
 * Plain-PHP constructible: the queue contract is a nullable constructor
 * wire (null = throw at send/queue), never a container call.
 */
final class PendingNotify
{
    /** @param  ?NotificationQueueContract  $queue  the notify queue seam (sync inline default) */
    public function __construct(
        private readonly string $topic,
        private readonly string $brief,
        private readonly NotifyContext $context,
        private readonly ?NotificationQueueContract $queue = null,
        private readonly ?Level $level = null,
        private readonly bool $premium = false,
        private readonly ?AttachmentFile $attachment = null,
    ) {}

    /** A copy routed to the given topic name. */
    public function topic(string $topic): self
    {
        return new self($topic, $this->brief, $this->context, $this->queue, $this->level, $this->premium, $this->attachment);
    }

    /** A copy carrying the given level (overrides the topic row's level). */
    public function level(Level $level): self
    {
        return new self($this->topic, $this->brief, $this->context, $this->queue, $level, $this->premium, $this->attachment);
    }

    /** A copy targeted at chat / forum thread (design G3; overrides the topic row's placement). */
    public function to(int|string $chat, ?int $threadId = null): self
    {
        return new self($this->topic, $this->brief, $this->context->withTarget($chat, $threadId), $this->queue, $this->level, $this->premium, $this->attachment);
    }

    /** Design-name alias of to(): target chat / forum thread. */
    public function chat(int|string $chat, ?int $threadId = null): self
    {
        return $this->to($chat, $threadId);
    }

    /** A copy with the given context pairs merged over the current ones (design §7 key=value block). */
    public function with(array $context): self
    {
        return new self($this->topic, $this->brief, $this->context->withContext($context), $this->queue, $this->level, $this->premium, $this->attachment);
    }

    /** A copy flagged premium (design §6 — capability rows apply the premium multiplier). */
    public function premium(bool $premium): self
    {
        return new self($this->topic, $this->brief, $this->context, $this->queue, $this->level, $premium, $this->attachment);
    }

    /** A copy carrying a file attachment (design §7 — the long section's rendered HTML). */
    public function attach(AttachmentFile $file): self
    {
        return new self($this->topic, $this->brief, $this->context, $this->queue, $this->level, $this->premium, $file);
    }

    /** Enqueue through the queue seam and return the job's wire array (design send(): array). */
    public function send(): array
    {
        $job = $this->job();
        $this->queueContract()->enqueue($job);

        return $job->toArray();
    }

    /** Enqueue through the queue seam and return only the job id (queue() is the id-only variant). */
    public function queue(): string
    {
        return $this->queueContract()->enqueue($this->job());
    }

    /** @return array<string, mixed> the design §5 wire payload (job_id/topic/level/brief/context/attachment/target/premium/attempts/created_at) */
    public function toWireArray(): array
    {
        return $this->jobArray();
    }

    private function queueContract(): NotificationQueueContract
    {
        if ($this->queue === null) {
            throw new NotifyException('no notify queue wired — cannot send topic "'.$this->topic.'"');
        }

        return $this->queue;
    }

    private function job(): NotificationJob
    {
        return NotificationJob::fromArray($this->jobArray());
    }

    /** @return array<string, mixed> */
    private function jobArray(): array
    {
        $chat = $this->context->chat();
        if ($chat === null) {
            throw new NotifyException('unknown topic "'.$this->topic.'" — no target chat on the topic row or the send');
        }

        return [
            'job_id' => 'tf-'.bin2hex(random_bytes(8)),
            'topic' => $this->topic,
            'level' => $this->resolvedLevel()->value,
            'brief' => $this->brief,
            'context' => $this->context->context(),
            'attachment' => $this->attachment?->toArray(),
            'target' => [
                'chat' => $chat,
                'thread_id' => $this->context->threadId(),
            ],
            'premium' => $this->premium,
            'attempts' => 0,
            'created_at' => time(),
        ];
    }

    private function resolvedLevel(): Level
    {
        return $this->level ?? Level::Info;
    }
}
