<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Handler;

/**
 * The handler table — code wins (Q1: declarations live here, in code; the
 * Redis hash stays the ops-level cache, not the source of truth).
 *
 * Ordering contract: first-match-wins in priority order, then registration
 * order (PHP 8's stable sort). `on()` returns $this for fluency; every
 * registration is one immutable ``Handler`` record, so the table is
 * reviewable like a route file and compile-time crash-free.
 *
 * Singleton contract: every production path must reach the SAME table —
 * ``HandlerRegistry::instance()`` is that one instance per process (the
 * Laravel container binding delegates to it). ``new HandlerRegistry()`` still
 * yields a plain table for tests.
 */
final class HandlerRegistry
{
    /** The one shared table for the process; null until first touch. */
    private static ?self $instance = null;

    /** @var list<Handler> */
    private array $handlers = [];

    private ?Deduplicator $deduplicator = null;

    public function __construct(?Deduplicator $deduplicator = null)
    {
        $this->deduplicator = $deduplicator;
    }

    /**
     * The process-wide handler table. Registrations made through this
     * instance are hot: dispatch-time matchers (HandlerMatcher + EchoEliminator)
     * read the table live, so accounts, bots and handler routes added at
     * runtime are picked up without restart by every already-running consumer.
     */
    public static function instance(): self
    {
        return self::$instance ??= new self;
    }

    /** Test seam: drop the shared instance so the next call builds a fresh table. */
    public static function reset(): void
    {
        self::$instance = null;
    }

    /**
     * Register one match → invokable. Match grammar (zero-regex):
     *  - `*`              catch-all (also the `onMessage()` surface)
     *  - `Foo`            exact constructor name
     *  - `FooBar*`        constructor-name prefix
     *  - `/start %s`      single sscanf token (route arg), zero-regex
     *
     * ``$tag`` is the optional hot-registration pass-through (default null =
     * current behavior): any opaque marker a caller hangs on the Handler
     * record (kind / worker / route label). Nothing here or in the pipeline
     * reads it — it is identity + introspection data for consumers of the
     * shared hot table.
     *
     * @param  string|array|\Closure  $handler  PSR-11-addressable invokable
     */
    public function on(string $match, string|array|\Closure $handler, int $priority = 0, bool $onOwn = false, ?string $tag = null): static
    {
        if (substr_count($match, '%s') > 1) {
            throw new MultipleSscanfTokensException(
                "Match pattern [$match] carries more than one %s token; exactly zero or one is allowed.",
            );
        }

        $this->handlers[] = new Handler($match, $handler, $priority, $onOwn, $tag);

        return $this;
    }

    /**
     * Subscribe to every update — the ``onMessage()``-style fan-in surface
     * (spec D1 first; ``onCommand``-type sugar layers on later, non-breaking).
     * An optional ``$tag`` is carried onto the Handler record (see ``on()``).
     */
    public function onMessage(string|array|\Closure $handler, int $priority = 0, ?string $tag = null): static
    {
        return $this->on('*', $handler, $priority, false, $tag);
    }

    /**
     * All registrations, high priority first; equal priorities keep
     * registration order (stable sort, PHP >= 8).
     *
     * @return list<Handler>
     */
    public function all(): array
    {
        $handlers = $this->handlers;
        usort($handlers, static fn (Handler $a, Handler $b) => $b->priority <=> $a->priority);

        return $handlers;
    }

    /**
     * Compiled snapshot — already sorted; in-memory for this phase (the
     * Redis hash cache / route:cache-style artifact is post-Phase-3).
     *
     * @return list<Handler>
     */
    public function compile(): array
    {
        return $this->all();
    }

    /**
     * Attach a cross-session deduplicator (fluent).
     */
    public function setDeduplicator(?Deduplicator $deduplicator): static
    {
        $this->deduplicator = $deduplicator;

        return $this;
    }

    public function getDeduplicator(): ?Deduplicator
    {
        return $this->deduplicator;
    }

    public function withDeduplicator(?Deduplicator $deduplicator): static
    {
        return $this->setDeduplicator($deduplicator);
    }

    /**
     * Session-aware deduplication key (account_id + update_id + update_hash).
     */
    public function dedupKey(Update $update, ?int $sessionId = null): string
    {
        return $this->deduplicatorFor()->key($update, $sessionId);
    }

    public function deduplicationKey(Update $update, ?int $sessionId = null): string
    {
        return $this->dedupKey($update, $sessionId);
    }

    public function deduplication_key(Update $update, ?int $sessionId = null): string
    {
        return $this->dedupKey($update, $sessionId);
    }

    public function sessionKey(Update $update, ?int $sessionId = null): string
    {
        return $this->dedupKey($update, $sessionId);
    }

    /**
     * Combined check-and-mark through the attached (or a default) deduplicator.
     */
    public function isDuplicate(Update $update, ?int $sessionId = null): bool
    {
        return $this->deduplicatorFor()->isDuplicate($update, $sessionId);
    }

    private function deduplicatorFor(): Deduplicator
    {
        return $this->deduplicator ??= new Deduplicator(new InMemoryCache);
    }

    /** Registering hand-observed invokables is introspection-only below. */
    public function count(): int
    {
        return count($this->handlers);
    }
}
