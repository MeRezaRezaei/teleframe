<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Notify;

use Psr\Log\InvalidArgumentException as PsrInvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Psr3NotifyBridge (design §2/§7, spec §7 G2 — a wave-2 non-goal for
 * slice 1, this class closes the seam): the PSR-3 LoggerInterface adapter
 * that routes log records INTO the notify core. Every PSR-3 call becomes a
 * notifier send through ONE chokepoint:
 *
 *   log($level, $message, array $context)
 *       → Level::fromPsr3($level)   (the 8 PSR-3 words map 1:1 onto the
 *                                    8 Level cases — design §2)
 *       → NotifyService::log($level, $message, $context, topic-from-level)
 *
 * The topic is the level's predefined topic (Level::defaultTopic() →
 * 'app.{level}', design §4), so a bare log call needs no registry lookup:
 * the notify core's own topic-resolution and loud send-time gates still
 * apply, and the level has byte-identical routing whether it arrived via
 * the typed facade (Notify::log(Level::Warning, …)) or via this bridge.
 *
 * Failure contract (AGENTS.md logger seam, spec §7): the bridge NEVER
 * swallows — a failed send propagates the Throwable to the caller exactly
 * as NotifyService::log() rethrows it; the notify queue's retry/
 * dead-letter owns recovery, not a silent drop. The message is passed raw
 * with its context (the Segmenter renders context as the inline metadata
 * block); PSR-3 `{placeholder}` interpolation is deliberately left to the
 * host — matching the engine's NullLogger posture of shipping untouched
 * context. The returned queue job id is ignored: the PSR-3 surface is
 * void.
 *
 * Constructor: a NotifyService, or any callable in NotifyService::log()'s
 * shape — plain-PHP constructible, zero container calls. Inside Laravel
 * the real service resolves from FacadeSupport::SERVICE_KEY; a plain host
 * wires `new Psr3NotifyBridge($service)` and lets the sync queue run the
 * send inline. log() feeds the raw (mixed) $level through
 * Level::fromPsr3() and maps an unknown level to the PSR-3 contract's
 * Psr\Log\InvalidArgumentException. Zero regex — strtolower/trim + the
 * enum match only.
 */
final class Psr3NotifyBridge implements LoggerInterface
{
    /** @var \Closure(Level, string, array<string, mixed>, ?string): string|\Closure(Level, string, array<string, mixed>, ?string): (string|null) */
    private readonly \Closure $sink;

    /**
     * @param  NotifyService|\Closure(Level, string, array<string, mixed>, ?string): (string|null)  $sink
     *                                                                                                     the notify core entry — a NotifyService (converted to its
     *                                                                                                     log() callable at construction) or any equivalent closure.
     */
    public function __construct(NotifyService|\Closure $sink)
    {
        $this->sink = $sink instanceof NotifyService ? $sink->log(...) : $sink;
    }

    /** System is unusable (Level::Emergency → app.emergency). */
    public function emergency(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    /** Action must be taken immediately (Level::Alert → app.alert). */
    public function alert(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    /** Critical conditions (Level::Critical → app.critical). */
    public function critical(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    /** Runtime errors needing monitoring (Level::Error → app.error). */
    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    /** Exceptional occurrences that are not errors (Level::Warning → app.warning). */
    public function warning(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    /** Normal but significant events (Level::Notice → app.notice). */
    public function notice(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    /** Interesting events (Level::Info → app.info). */
    public function info(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    /** Detailed debug information (Level::Debug → app.debug). */
    public function debug(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    /**
     * Logs with an arbitrary level: convert the PSR-3 word to the Level
     * enum, then route the record to the notify core under the level's
     * default topic. Unknown levels throw Psr\Log\InvalidArgumentException
     * (the PSR-3 contract); send failures are never swallowed.
     *
     * @param  mixed  $level  a PSR-3 level word (e.g. 'warning')
     * @param  array<string, mixed>  $context
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        try {
            $notifyLevel = Level::fromPsr3((string) $level);
        } catch (\InvalidArgumentException $e) {
            // The enum throws the global class; the interface contract
            // requires the PSR-3 one — translate, never mask the reason.
            throw new PsrInvalidArgumentException($e->getMessage(), 0, $e);
        }

        ($this->sink)(
            $notifyLevel,
            (string) $message,
            $context,
            $notifyLevel->defaultTopic(), // topic = the level's predefined topic (design §4)
        );
    }
}
