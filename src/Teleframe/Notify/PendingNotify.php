<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Notify;

use Closure;

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
 *
 * THE GATE (D2). send()/queue() do NOT enqueue straight into the queue
 * seam: they first run `$gate` — the closure `NotifyService::to()` binds,
 * which is `log()`'s own capability-check → segment → budget path. Without
 * it this fluent surface bypassed every gate the module ships: a
 * `->to('app.error', str_repeat('x', 10000))->send()` went out at 10047
 * chars against a 4096 cap and never split, and five sends to one chat
 * sailed past `per_chat_rate = 1/s` with no refusal — while `config/notify.php`
 * documents those budgets as "enforced on every send". The gate returns a
 * NEW PendingNotify carrying the gated state (brief trimmed to the cap, the
 * full text moved into an attachment when it was over, the resolved chat),
 * and THAT instance is the one compiled and enqueued, so the returned wire
 * array is the job that really went out — not a second, ungated one.
 *
 * A builder constructed directly (no service, no gate) keeps the pre-gate
 * behaviour and enqueues on its own queue seam: that is the plain-PHP
 * construction this class has always promised, and it is NOT a path
 * `NotifyService` can produce.
 *
 * Plain-PHP constructible: the queue contract and the gate are nullable
 * constructor wires (null = throw at send/queue), never a container call.
 */
final class PendingNotify
{
    /**
     * @param  ?NotificationQueueContract  $queue  the notify queue seam (sync inline default)
     * @param  (Closure(self): self)|null  $gate  the service's send-time gate (topic row →
     *                                            capability check → segment → budgets); it returns
     *                                            a NEW builder carrying the gated state, or throws
     */
    public function __construct(
        private readonly string $topic,
        private readonly string $brief,
        private readonly NotifyContext $context,
        private readonly ?NotificationQueueContract $queue = null,
        private readonly ?Level $level = null,
        private readonly bool $premium = false,
        private readonly ?AttachmentFile $attachment = null,
        private readonly ?Closure $gate = null,
    ) {}

    /** A copy routed to the given topic name. */
    public function topic(string $topic): self
    {
        return new self($topic, $this->brief, $this->context, $this->queue, $this->level, $this->premium, $this->attachment, $this->gate);
    }

    /** A copy carrying the given level (overrides the topic row's level). */
    public function level(Level $level): self
    {
        return new self($this->topic, $this->brief, $this->context, $this->queue, $level, $this->premium, $this->attachment, $this->gate);
    }

    /** A copy targeted at chat / forum thread (design G3; overrides the topic row's placement). */
    public function to(int|string $chat, ?int $threadId = null): self
    {
        return new self($this->topic, $this->brief, $this->context->withTarget($chat, $threadId), $this->queue, $this->level, $this->premium, $this->attachment, $this->gate);
    }

    /** Design-name alias of to(): target chat / forum thread. */
    public function chat(int|string $chat, ?int $threadId = null): self
    {
        return $this->to($chat, $threadId);
    }

    /** A copy with the given context pairs merged over the current ones (design §7 key=value block). */
    public function with(array $context): self
    {
        return new self($this->topic, $this->brief, $this->context->withContext($context), $this->queue, $this->level, $this->premium, $this->attachment, $this->gate);
    }

    /** A copy flagged premium (design §6 — capability rows apply the premium multiplier). */
    public function premium(bool $premium): self
    {
        return new self($this->topic, $this->brief, $this->context, $this->queue, $this->level, $premium, $this->attachment, $this->gate);
    }

    /** A copy carrying a file attachment (design §7 — the long section's rendered HTML). */
    public function attach(AttachmentFile $file): self
    {
        return new self($this->topic, $this->brief, $this->context, $this->queue, $this->level, $this->premium, $file, $this->gate);
    }

    /**
     * Run the gate (when one is bound), compile the GATED job, enqueue it
     * and return its wire array (design send(): array).
     */
    public function send(): array
    {
        $pending = $this->gated();
        $job = $pending->job();
        $pending->queueContract()->enqueue($job);

        return $job->toArray();
    }

    /** As send(), returning only the job id (queue() is the id-only variant). */
    public function queue(): string
    {
        $pending = $this->gated();
        $job = $pending->job();

        return $pending->queueContract()->enqueue($job);
    }

    /**
     * This builder after the service's gate, or unchanged when no gate is
     * bound. The GATED instance is the one that gets compiled and enqueued,
     * so what send() returns is the job that really went out.
     */
    private function gated(): self
    {
        if ($this->gate === null) {
            return $this;
        }

        return ($this->gate)($this);
    }

    /** The topic name this send asks for. */
    public function topicName(): string
    {
        return $this->topic;
    }

    /** The brief as the caller wrote it (pre-gate). */
    public function briefText(): string
    {
        return $this->brief;
    }

    /** The context pairs this send carries. */
    public function contextPairs(): array
    {
        return $this->context->context();
    }

    /** The attachment this send carries, if any. */
    public function attachmentFile(): ?AttachmentFile
    {
        return $this->attachment;
    }

    /** The explicit per-send premium flag (not the probed status — the gate owns that). */
    public function isPremium(): bool
    {
        return $this->premium;
    }

    /** The level this send carries — an explicit override, else the info default. */
    public function levelEnum(): Level
    {
        return $this->resolvedLevel();
    }

    /** The chat THIS send asked for, or null when it asked for none (the gate falls back to the topic row). */
    public function targetChatValue(): int|string|null
    {
        return $this->context->chat();
    }

    /**
     * A copy carrying the gate's decisions: the (possibly trimmed) brief,
     * the attachment the segmenter produced, and the chat the gate resolved.
     */
    public function gatedInto(string $brief, ?AttachmentFile $attachment, int|string $chat): self
    {
        return new self(
            $this->topic,
            $brief,
            $this->context->withTarget($chat, $this->context->threadId()),
            $this->queue,
            $this->level,
            $this->premium,
            $attachment,
            null,
        );
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
