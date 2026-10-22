<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Notify;

/**
 * Log-level facade plumbing (design G2 — the level→topic bridge half):
 * maps every Level to a topic and funnels level-typed log calls into a
 * sink. The default mapping is the design's Level::defaultTopic()
 * ('app.{level}'); when a Topic registry is injected, the registry's
 * topicFor() row decides and Level::defaultTopic() is the fallback. The
 * sink is a nullable constructor wire `callable(Level, string, array,
 * string): string` returning the notified job id (typically wired to
 * NotifyService::log()); a null sink is byte-for-byte silent, matching
 * the engine's NullLogger posture. The full PSR-3 LoggerInterface
 * adapter (Psr3NotifyBridge) stays wave-2 per design §9; this class is
 * the typed Level→topic plumbing the bridge will sit on. Zero regex —
 * strtolower/trim only.
 */
final class NotificationLogger
{
    /** @param  ?\Closure(Level, string, array<string, mixed>, string): (string|null)  $sink  null = silent (NullLogger posture) */
    public function __construct(
        private readonly ?\Closure $sink = null,
        private readonly ?Topic $registry = null,
    ) {}

    /** The topic name a level routes to: registry topicFor() row, else 'app.{level}' (design §4). */
    public function topicFor(Level $level): string
    {
        if ($this->registry !== null) {
            return $this->registry->topicFor($level)->name();
        }

        return $level->defaultTopic();
    }

    /** Log $brief under $level (routed to $topic when given, else the level's default topic); returns the sink's job id or ''. */
    public function log(Level $level, string $brief, array $context = [], ?string $topic = null): string
    {
        if ($this->sink === null) {
            return '';
        }

        return (string) ($this->sink)($level, $brief, $context, $topic ?? $this->topicFor($level));
    }

    public function debug(string $brief, array $context = []): string
    {
        return $this->log(Level::Debug, $brief, $context);
    }

    public function info(string $brief, array $context = []): string
    {
        return $this->log(Level::Info, $brief, $context);
    }

    public function notice(string $brief, array $context = []): string
    {
        return $this->log(Level::Notice, $brief, $context);
    }

    public function warning(string $brief, array $context = []): string
    {
        return $this->log(Level::Warning, $brief, $context);
    }

    public function error(string $brief, array $context = []): string
    {
        return $this->log(Level::Error, $brief, $context);
    }

    public function critical(string $brief, array $context = []): string
    {
        return $this->log(Level::Critical, $brief, $context);
    }

    public function alert(string $brief, array $context = []): string
    {
        return $this->log(Level::Alert, $brief, $context);
    }

    public function emergency(string $brief, array $context = []): string
    {
        return $this->log(Level::Emergency, $brief, $context);
    }
}
