<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Notify;

/**
 * Typed topic registry (design §4, "Topic = log-level routing model").
 * A topic IS a log level plus a placement: name, Level, one-line
 * description, target chat, forum message_thread_id and an operator
 * throttle budget (the throttlePerHour row exists from day one —
 * enforcement is wave 3). Ships the 8 PSR-3 levels as predefined
 * 'app.*' topics (forum threads 1..8), plus the domain rows
 * backup / backup.missing / report, mirroring the design's default
 * topics block. Instances are immutable rows; the design's registry
 * surface (defaults/define/resolve/topicFor/topics/compile) lives on
 * the same class: Topic::defaults() builds the preloaded registry, a
 * bare `new Topic()` is an empty registry hosts fill via define() with
 * config-merged rows. resolve() and topicFor() are TOTAL — unknown
 * names synthesize a minimal row (chat null) and the loud
 * NotifyException is raised at send() time (design §4: "unknown topic
 * at send() throws"), so plain-PHP construction never fails.
 */
final class Topic
{
    /** @var array<string, Topic> name ⇒ row, populated on registry instances */
    private array $rows = [];

    /** @param  int|string|null  $chat  target chat id / @username; null = host default_chat resolved at send */
    public function __construct(
        private readonly string $name = '',
        private readonly ?Level $level = null,
        private readonly string $description = '',
        private readonly int|string|null $chat = null,
        private readonly ?int $threadId = null,
        private readonly ?int $throttlePerHour = null,
    ) {}

    /** @return list<self> the predefined topic rows (design §4 default topics block) */
    public static function predefined(): array
    {
        return [
            new self('app.debug', Level::Debug, 'debug-level logging (forum thread 1)', null, 1),
            new self('app.info', Level::Info, 'info-level logging (forum thread 2)', null, 2),
            new self('app.notice', Level::Notice, 'notice-level logging (forum thread 3)', null, 3),
            new self('app.warning', Level::Warning, 'warning-level logging (forum thread 4)', null, 4),
            new self('app.error', Level::Error, 'error-level logging (forum thread 5)', null, 5),
            new self('app.critical', Level::Critical, 'critical-level logging (forum thread 6)', null, 6),
            new self('app.alert', Level::Alert, 'alert-level logging (forum thread 7)', null, 7),
            new self('app.emergency', Level::Emergency, 'emergency-level logging (forum thread 8)', null, 8),
            new self('backup', Level::Info, 'heartbeat channel for backup runs'),
            new self('backup.missing', Level::Alert, 'watchdog destination: no-show backup alerts (G10)'),
            new self('report', Level::Notice, 'report() lands here'),
        ];
    }

    /** A fresh registry preloaded with the predefined topics (design TopicRegistry::defaults). */
    public static function defaults(): self
    {
        $registry = new self;
        foreach (self::predefined() as $row) {
            $registry->rows[$row->name()] = $row;
        }

        return $registry;
    }

    /** Register or overwrite one topic row; returns the registry for chaining (hosts merge config rows). */
    public function define(string $name, Level $level, string $description = '', int|string|null $chat = null, ?int $threadId = null, ?int $throttlePerHour = null): self
    {
        $this->rows[$name] = new self($name, $level, $description, $chat, $threadId, $throttlePerHour);

        return $this;
    }

    /** Resolve a topic by name — never throws; unknown names yield a minimal row and send() stays the loud gate. */
    public function resolve(string $name): self
    {
        return $this->rows[$name] ?? new self($name, Level::Info, '');
    }

    /** The default topic for a level — 'app.{level}', synthesized when the registry lacks the row. */
    public function topicFor(Level $level): self
    {
        return $this->rows[$level->defaultTopic()] ?? new self($level->defaultTopic(), $level, '');
    }

    /** @return array<string, self> the name ⇒ row map (design TopicRegistry::topics) */
    public function topics(): array
    {
        return $this->rows;
    }

    /** Rebuild the name ⇒ row map from the current rows (idempotent — rows are already typed). */
    public function compile(): self
    {
        $compiled = [];
        foreach ($this->rows as $row) {
            $compiled[$row->name()] = $row;
        }
        $this->rows = $compiled;

        return $this;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function level(): Level
    {
        return $this->level ?? Level::Info;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function chat(): int|string|null
    {
        return $this->chat;
    }

    public function threadId(): ?int
    {
        return $this->threadId;
    }

    public function throttlePerHour(): ?int
    {
        return $this->throttlePerHour;
    }

    /** The row's chat, or the given fallback when the row carries none (null = host default_chat). */
    public function chatOr(int|string $fallback): int|string
    {
        return $this->chat ?? $fallback;
    }

    /** @return array<string, mixed> the wire-shaped row (design TopicDefinition::toRow) */
    public function toRow(): array
    {
        return [
            'name' => $this->name,
            'level' => $this->level()->value,
            'description' => $this->description,
            'chat' => $this->chat,
            'thread_id' => $this->threadId,
            'throttle_per_hour' => $this->throttlePerHour,
        ];
    }
}
