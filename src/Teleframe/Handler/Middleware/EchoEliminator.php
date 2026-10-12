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
 *
 * BOTH INTAKE ROUTES, OR NEITHER (2026-09-30 fix, the reason this note
 * exists). This guard read its two ids ONLY from ``$update->array``. That is
 * correct on the bus path, where the transport payload carries them, and it
 * was ACCIDENTALLY wrong on the event path: ``Update::fromMirror()`` built a
 * ``['_'] => ctor`` row with no ids, so ``lookup()`` returned null and the
 * middleware fell through to ``$next($update)`` on every event frame — a
 * silent no-op on the SHIPPED default intake (``UpdateStoredHandler``,
 * registered by ``TeleframeServiceProvider:965``). ``ReplayDedup:38-53`` and
 * ``UpdateDispatcher::forwardKey()`` both SKIP the event path deliberately
 * and say so in dated comments; this one skipped it by accident and said
 * nothing, which is the third instance of that shape the audit found.
 *
 * The fix is upstream, in ``Update::fromMirror()``, which now copies the two
 * ids into the identity row from the model's already-hydrated attributes.
 * Deliberately NOT done here: reaching into ``$update->model``'s CHILD
 * relations. ``random_id`` is not on the echo's root at all (the mirror only
 * stores it on the unrelated ``updateActionMessage`` child), so a child-column
 * fallback would cost a query per event dispatch to fetch a value that is
 * usually absent — and it would put mirror-schema knowledge in a route-level
 * middleware. ``tests/Handler/EchoEliminatorEventPathTest.php`` is the
 * executable form of this paragraph, and it asserts the OTHER direction too:
 * a non-echo frame, a foreign-account frame, and an id-less frame must all
 * still reach the handler. Over-eliminating is a starved handler, the same
 * harm as the mass-drop this middleware's neighbours were fixed for.
 *
 * WHAT IT STILL CANNOT SEE, honestly: a row that carries no id at all — a
 * standalone ``TfUpdate`` root has none, and a legacy ``Tl*`` row may not
 * either. Such a frame is forwarded, because dropping it would starve the
 * handler forever. For the common own-send case ingest still catches it by
 * the ``out`` flag; the residual exposure is an ``act_on`` own send arriving
 * on a constructor whose root has no id to match on.
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

        if ($handler !== null && $handler->handler->onOwn) {
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