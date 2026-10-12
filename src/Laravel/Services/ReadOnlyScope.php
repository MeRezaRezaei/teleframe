<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Services;

use Closure;

/**
 * Request-scoped read-only flag carrier for the relay vault terminal.
 *
 * Holds the "the current scope may only READ the managed telegram/bot
 * accounts" state that the enforcement seam (ReadOnlyVaultDispatch) consults
 * on every RPC. Two ways to flip it:
 *
 *  - request frame: ScopeTelegramReadOnly calls begin() when a host Laravel
 *    user session is present on the /tg page and the relay switch/presence
 *    routes, and end() when the request terminates — so a Laravel user
 *    session is read-only for exactly that request;
 *  - operator flip: a host may bind this class with an injected resolver
 *    (always-true = every dispatch is read-only) or begin() it once out of a
 *    request frame to pin the process permanently.
 *
 * Plain PHP, no facades, no credentials: the resolver is an injected callable
 * (never a concrete user model), consulted BEFORE the request-frame counter,
 * so a host can force read-only regardless of the request lifecycle. Off by
 * default: a null resolver plus an empty counter keeps isReadOnly() false and
 * standalone behaviour byte-for-byte unchanged.
 */
final class ReadOnlyScope
{
    private int $references = 0;

    /** @var (Closure(): bool)|null */
    private readonly ?Closure $resolver;

    /**
     * @param  (callable(): bool)|null  $resolver  host pin: when set and true, the scope is permanently read-only
     */
    public function __construct(?callable $resolver = null)
    {
        $this->resolver = $resolver === null ? null : $resolver(...);
    }

    /** Open a read-only request frame (nestable). */
    public function begin(): void
    {
        $this->references++;
    }

    /** Close a read-only request frame, matched to begin(). */
    public function end(): void
    {
        if ($this->references > 0) {
            $this->references--;
        }
    }

    /** Whether the current scope must refuse state-changing RPCs. */
    public function isReadOnly(): bool
    {
        if ($this->resolver !== null && ($this->resolver)()) {
            return true;
        }

        return $this->references > 0;
    }
}
