<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Control;

use Closure;

/**
 * The control-layer update stream for one vault account (the "control what
 * data is passing to the client" seam): pulls ONE update from the injected
 * reader — production: the bus consumer reading tg:stream:updates /
 * group teleclient — wraps it as an INBOUND RelayMessage, runs it through
 * the same RelayFilter onion as AccountRelay (snoop / rewrite / drop), then
 * hands the post-filter message to the publisher callable.
 *
 * Lifecycle mirrors a relay session: start() opens the presence record
 * (relay id "stream"), every live pulse refreshes the heartbeat via
 * registryTouch, stop() closes it. Plain PHP, no facades — reader,
 * publisher and registry hooks are callables with no-op defaults, so the
 * class constructs in isolation and tests inject fakes; the live bus is
 * never dialed here.
 */
final class AccountUpdateStream
{
    /** @var (Closure(): (array<string, mixed>|null))|null pull one update (null = stream drained) */
    private readonly ?Closure $reader;

    /** @var (Closure(RelayMessage): void)|null sees the post-filter message */
    private readonly ?Closure $publisher;

    /** @var (Closure(int, string): void)|null RelaySessionRegistry::start seam */
    private readonly ?Closure $registryStart;

    /** @var (Closure(int): void)|null RelaySessionRegistry::stop seam */
    private readonly ?Closure $registryStop;

    /** @var (Closure(int): void)|null RelaySessionRegistry::touch seam (live-pulse heartbeat) */
    private readonly ?Closure $registryTouch;

    /**
     * @param  (callable(): (array<string, mixed>|null))|null  $reader
     * @param  (callable(RelayMessage): void)|null  $publisher
     * @param  (callable(int, string): void)|null  $registryStart
     * @param  (callable(int): void)|null  $registryStop
     * @param  (callable(int): void)|null  $registryTouch
     * @param  list<RelayFilter>  $filters  control-layer onion, same shape as AccountRelay
     */
    public function __construct(
        public readonly int $accountId,
        ?callable $reader = null,
        ?callable $publisher = null,
        ?callable $registryStart = null,
        ?callable $registryStop = null,
        ?callable $registryTouch = null,
        private RelayPipeline $pipeline = new RelayPipeline,
        private array $filters = [],
    ) {
        $this->reader = $reader === null ? null : $reader(...);
        $this->publisher = $publisher === null ? null : $publisher(...);
        $this->registryStart = $registryStart === null ? null : $registryStart(...);
        $this->registryStop = $registryStop === null ? null : $registryStop(...);
        $this->registryTouch = $registryTouch === null ? null : $registryTouch(...);
    }

    public function addFilter(RelayFilter $filter): self
    {
        $this->filters[] = $filter;

        return $this;
    }

    /** Open the relay session for this account (relay id "stream"). */
    public function start(): void
    {
        if ($this->registryStart !== null) {
            ($this->registryStart)($this->accountId, 'stream');
        }
    }

    /**
     * Pull one update from the reader, push it through the filter onion and
     * publish the post-filter message. A drained reader or an empty/no-op
     * reader yields null. Every live pulse refreshes the session heartbeat.
     */
    public function next(): ?RelayMessage
    {
        if ($this->reader === null) {
            return null;
        }

        $payload = ($this->reader)();
        if ($payload === null) {
            return null;
        }

        if ($this->registryTouch !== null) {
            ($this->registryTouch)($this->accountId);
        }

        $message = $this->pipeline->then(
            $this->filters,
            static fn (RelayMessage $message): RelayMessage => $message,
        )(new RelayMessage(RelayMessage::INBOUND, 'update', $payload, raw: null));

        if ($this->publisher !== null) {
            ($this->publisher)($message);
        }

        return $message;
    }

    /** Close the relay session for this account. */
    public function stop(): void
    {
        if ($this->registryStop !== null) {
            ($this->registryStop)($this->accountId);
        }
    }
}
