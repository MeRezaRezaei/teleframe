<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Notify;

/**
 * Notification context (design §4.3 target row + §7 context/metadata
 * split): WHERE a notify lands and WHAT it carries. The target is the
 * design's {chat: int|string, threadId: ?int} — a flat channel simply
 * ignores the forum message_thread_id (design §12), so no code fork.
 * context holds the key=value pairs the Segmenter inlines under the
 * brief (design §7 — exception/trace keys are NEVER inlined here),
 * metadata holds free-form transport info (topic/level/timestamps)
 * that stays out of the message text. Immutable — fluent with*()
 * methods return new instances.
 */
final class NotifyContext
{
    /** @param  array<string, mixed>  $context  key=value pairs inlined in the message */
    /** @param  array<string, mixed>  $metadata  free-form extra info (never inlined) */
    public function __construct(
        private readonly int|string|null $chat = null,
        private readonly ?int $threadId = null,
        private readonly array $context = [],
        private readonly array $metadata = [],
    ) {}

    public function chat(): int|string|null
    {
        return $this->chat;
    }

    public function threadId(): ?int
    {
        return $this->threadId;
    }

    /** @return array<string, mixed> key=value context pairs inlined in the message (design §7) */
    public function context(): array
    {
        return $this->context;
    }

    /** @return array<string, mixed> free-form metadata, never inlined (design §7) */
    public function metadata(): array
    {
        return $this->metadata;
    }

    /** The target chat or the fallback when unset (null = host default_chat, resolved at send). */
    public function chatOr(int|string $fallback): int|string
    {
        return $this->chat ?? $fallback;
    }

    /** @return array{chat_id: int|string, message_thread_id: int|null} Bot API params (design G3 target) */
    public function params(): array
    {
        return [
            'chat_id' => $this->chat,
            'message_thread_id' => $this->threadId,
        ];
    }

    /** A copy with the target chat/thread replaced (design G3: PendingNotify::to/chat). */
    public function withTarget(int|string $chat, ?int $threadId = null): self
    {
        return new self($chat, $threadId, $this->context, $this->metadata);
    }

    /** A copy with the given context pairs merged over the current ones. */
    public function withContext(array $context): self
    {
        return new self($this->chat, $this->threadId, array_merge($this->context, $context), $this->metadata);
    }

    /** A copy with the given metadata merged over the current ones. */
    public function withMetadata(array $metadata): self
    {
        return new self($this->chat, $this->threadId, $this->context, array_merge($this->metadata, $metadata));
    }

    /** @return array<string, mixed> the full context: target, context pairs and metadata */
    public function toArray(): array
    {
        return [
            'chat' => $this->chat,
            'thread_id' => $this->threadId,
            'context' => $this->context,
            'metadata' => $this->metadata,
        ];
    }
}
