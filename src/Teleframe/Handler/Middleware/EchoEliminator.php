<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Handler\Middleware;

use MeRezaRezaei\Teleframe\Handler\HandlerMatcher;
use MeRezaRezaei\Teleframe\Handler\Update;
use Psr\SimpleCache\CacheInterface;

/**
 * Loop prevention, default-on first middleware (Q2 d+b pair).
 *
 * The mirror cannot tell framework-sent from human-typed-on-phone via the
 * ``out`` column alone, so elimination reads a send-time registry (Q2d):
 * a PSR-16 cache holding ``{account_id, random_id, msg_id, sent_at}`` keys
 * written by the facade's send path. On an inbound uprate, a match marks it
 * self-originated and — unless the matched handler opted in with
 * ``onOwn: true`` (route-level escape hatch, Q2c) — terminates the chain
 * before any user handler runs. The mirror stays truth-complete: this
 * middleware sits at route level, never at ingest.
 */
final class EchoEliminator
{
    /** Cache key prefix for the send registry. */
    public const KEY = 'teleframe.handler.sends';

    public function __construct(
        private readonly CacheInterface $sends,
        private readonly HandlerMatcher $matcher,
        private readonly int $ttl = 3600,
    ) {
    }

    /**
     * @param callable(Update): mixed $next
     */
    public function __invoke(Update $update, callable $next): mixed
    {
        $randomId = (string) ($update->array['random_id'] ?? '');
        $msgId = (string) ($update->array['msg_id'] ?? '');
        $found = $this->lookup($update->accountId, $randomId)
            ?? $this->lookup($update->accountId, $msgId);

        if ($found === null) {
            return $next($update);
        }

        $selfOriginated = $update->withSelfOriginated(true);
        $handler = $this->matcher->match($update->constructor());

        if ($handler !== null && $handler->onOwn) {
            return $next($selfOriginated);
        }

        return null;
    }

    /**
     * Record a framework send so its echo is recognized later. The facade's
     * send path calls this (via the PSR-16 registry in the container).
     *
     * @param array<string, mixed> $send {random_id?, msg_id?, sent_at}
     */
    public function remember(int $accountId, array $send): void
    {
        foreach (['random_id', 'msg_id'] as $field) {
            $value = (string) ($send[$field] ?? '');
            if ($value === '') {
                continue;
            }
            $this->sends->set($this->key($accountId, $value), $send, $this->ttl);
        }
    }

    private function lookup(int $accountId, string $id): ?array
    {
        if ($id === '') {
            return null;
        }

        $record = $this->sends->get($this->key($accountId, $id));

        return is_array($record) ? $record : null;
    }

    private function key(int $accountId, string $id): string
    {
        return self::KEY . ':' . $accountId . ':' . $id;
    }
}