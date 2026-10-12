<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Daemon;

use MeRezaRezaei\Teleframe\Core\MTProto\Transport\RelayServer;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\WebSocketRelayServer;
use Psr\Log\LoggerInterface;

/**
 * One account's obfuscated2 relay listen loop.
 *
 * Wraps a relay terminal — the raw-TCP RelayServer (native/desktop clients)
 * or the WebSocketRelayServer (browser clients, wss://…/apiws) — whose
 * upstream resolver is pinned to that account's real Telegram DC
 * (dc{id}.telegram.org unless the command overrides it). The listener owns
 * the listen socket for its whole lifetime and ticks `serveOnce` with a
 * short accept deadline so a stop() flag is honoured promptly (signals,
 * supervision) instead of blocking 30s per accept.
 *
 * Silent by default; errors that escape the terminal's per-client guard
 * (e.g. listen-bind failures) go to the injected logger when one exists.
 */
final class RelayListener
{
    /** @var resource|null */
    private $server = null;

    private bool $shouldStop = false;

    public function __construct(
        private readonly RelayServer|WebSocketRelayServer $relay,
        private readonly ?LoggerInterface $logger = null,
    ) {}

    public function stop(): void
    {
        $this->shouldStop = true;
    }

    /** @return resource the (bound once) listening socket */
    public function listenSocket()
    {
        if (! is_resource($this->server)) {
            $this->server = $this->relay->listen();
        }

        return $this->server;
    }

    /**
     * One accept pass (bounded by $acceptTimeoutSeconds). Returns true when
     * the listener should keep going, false after stop().
     */
    public function runPass(float $acceptTimeoutSeconds = 0.5): bool
    {
        if ($this->shouldStop) {
            return false;
        }

        try {
            $this->relay->serveOnce($this->listenSocket(), $acceptTimeoutSeconds);
        } catch (\Throwable $e) {
            $this->logger?->error('relay listener pass failed', ['exception' => $e]);

            return false;
        }

        // On the success path we already returned early when shouldStop was
        // set, and nothing in serveOnce flips it, so the flag is provably
        // false here.
        return true;
    }

    /** Serve client connections until stop() or a fatal listen error. */
    public function run(float $tickSeconds = 1.0): void
    {
        while ($this->runPass($tickSeconds)) {
            // serveOnce completed one client (or a bounded accept tick).
        }
    }
}
