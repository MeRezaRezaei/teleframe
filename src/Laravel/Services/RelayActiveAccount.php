<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Services;

use Illuminate\Contracts\Cache\Repository;
use MeRezaRezaei\Teleframe\Control\RelayContext;
use MeRezaRezaei\Teleframe\Control\RelayTerminal;
use Psr\SimpleCache\CacheInterface;

/**
 * Active-account registry for the control layer ("sit in the middle").
 *
 * Renders ONE vault account ACTIVE for the relay within a SCOPE (tenant /
 * owner namespace): switchTo() persists the account's opaque context under
 * the key `relay:active:account[:scope]` (value {account_id, dc_id, label,
 * switched_at}) and returns a fresh RelayTerminal for exactly that account
 * via RelayFactory::terminalFor() — fully lazy, never reading the vault
 * session string, never resolving a client, never dialing a socket (the
 * encrypted connection opens only on the first outbound dispatch).
 * current() reads the persisted context for a scope so a shared relay /
 * dashboard shell knows whose terminal is bound.
 *
 * Every operation is tenant/peer-scoped (W-09): switchFor()/currentFor()/
 * currentGenerationFor() namespace the PSR-16 key by a scope id, so an
 * account switch in tenant A writes `relay:active:account:<A>` and bumps
 * only `relay:active:generation:<A>` — tenant B's context and generation
 * are untouched and B's consumers never re-seat. The legacy key
 * (`relay:active:account` / `relay:active:generation`, SCOPE_GLOBAL) is
 * preserved for the BC surface: switchTo()/current()/currentGeneration()
 * are exactly the old global behavior.
 *
 * Alongside the context, switching bumps a monotonic generation under the
 * scope's generation key (starts at 1 on the first switch). The bump is
 * ATOMIC: on a Laravel cache (the framework store, Redis-backed in hosts)
 * it is a single cache INCR, so concurrent switches in one scope cannot
 * lose a bump; on a plain PSR-16 store it degrades to a bounded
 * compare-and-set loop that never writes a lower value than it read (the
 * residual two-writers-same-baseline window is inherent to PSR-16, as in
 * CacheNonceRegistry). Long-lived consumers (VaultMtprotoAnswerer's
 * dispatch factory) compare currentGenerationFor() on every RPC to detect
 * that the ACTIVE account changed WITHIN THEIR SCOPE and re-seat their
 * cached scope without reading the vault — a cross-scope switch never
 * pivots them.
 *
 * The session string itself is a credential and never enters this class or
 * the cache: only the immutable RelayContext fields are persisted.
 */
final class RelayActiveAccount
{
    /** The BC default scope — the legacy global keys with no suffix. */
    public const SCOPE_GLOBAL = 'global';

    private const KEY = 'relay:active:account';

    private const GENERATION_KEY = 'relay:active:generation';

    /** Retries of the plain-PSR-16 generation CAS before the freshest read wins. */
    private const MAX_GENERATION_ATTEMPTS = 8;

    public function __construct(
        private readonly CacheInterface $cache,
        private readonly RelayFactory $factory,
    ) {}

    /** BC: switch the GLOBAL (legacy) active context. */
    public function switchTo(RelayContext $ctx): RelayTerminal
    {
        return $this->switchFor(self::SCOPE_GLOBAL, $ctx);
    }

    /**
     * Switch the ACTIVE context for one tenant/peer scope. Tenant A switching
     * never re-points tenant B: the persisted key + generation bump are
     * namespaced by GET, and other scopes' consumers (which watch their own
     * generation) do not re-seat.
     */
    public function switchFor(string $scope, RelayContext $ctx): RelayTerminal
    {
        $this->cache->set(
            $this->key($scope),
            json_encode([
                'account_id' => $ctx->accountId,
                'dc_id' => $ctx->dcId,
                'label' => $ctx->label,
                'switched_at' => time(),
            ], JSON_THROW_ON_ERROR),
        );

        $this->bumpGeneration($scope);

        return $this->factory->terminalFor($ctx);
    }

    /** BC: the monotonic switch generation of the GLOBAL scope. */
    public function currentGeneration(): int
    {
        return $this->currentGenerationFor(self::SCOPE_GLOBAL);
    }

    /**
     * The monotonic switch generation for one scope: 0 when nothing has
     * been switched there, then 1, 2, ... after each switchFor(). Consumers
     * re-seat their cached scope whenever THIS value changes.
     */
    public function currentGenerationFor(string $scope): int
    {
        return $this->readGeneration($this->generationKey($scope));
    }

    /**
     * Atomically bump the scope's switch generation and return the new value.
     *
     * Laravel's Repository is PSR-16 AND exposes an atomic increment — on a
     * Redis-backed store that is a single INCR round-trip (numeric values
     * are stored raw, so currentGenerationFor() reads them back), and
     * concurrent switches in one scope serialize: no bump is lost.
     *
     * Plain PSR-16 falls back to a bounded compare-and-set loop: every pass
     * re-reads the freshest baseline and writes baseline+1; when the
     * read-back no longer matches the value written, a concurrent bump
     * interleaved and we retry against the new baseline. Writes are
     * monotonic by construction (next = observed + 1), so a racing switch
     * can only push the generation UP. PSR-16 has no test-and-set primitive,
     * so two writers that read the SAME baseline can still both write
     * baseline+1 and one bump is subsumed — the residual window is
     * documented, as in CacheNonceRegistry; a Redis-backed Laravel store
     * closes it via the increment() fast path above.
     */
    private function bumpGeneration(string $scope): int
    {
        $key = $this->generationKey($scope);

        if ($this->cache instanceof Repository) {
            $bumped = $this->cache->increment($key);

            if (is_int($bumped) && $bumped > 0) {
                return $bumped;
            }
        }

        for ($attempt = 0; $attempt < self::MAX_GENERATION_ATTEMPTS; $attempt++) {
            $next = $this->readGeneration($key) + 1;
            $this->cache->set($key, $next);

            if ($this->readGeneration($key) === $next) {
                return $next;
            }
        }

        return $this->readGeneration($key);
    }

    /** Parse a stored generation value: absent/negative/garbage reads as 0. */
    private function readGeneration(string $key): int
    {
        $raw = $this->cache->get($key);

        if (is_int($raw)) {
            return $raw >= 0 ? $raw : 0;
        }

        if (! is_string($raw) || $raw === '') {
            return 0;
        }

        $generation = filter_var($raw, FILTER_VALIDATE_INT);

        return $generation === false || $generation < 0 ? 0 : $generation;
    }

    /** BC: the currently active account context of the GLOBAL scope. */
    public function current(): ?RelayContext
    {
        return $this->currentFor(self::SCOPE_GLOBAL);
    }

    /** The currently active account context for one scope, or null when none is switched. */
    public function currentFor(string $scope): ?RelayContext
    {
        $raw = $this->cache->get($this->key($scope));
        if (! is_string($raw) || $raw === '') {
            return null;
        }

        try {
            $payload = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        if (! is_array($payload)) {
            return null;
        }

        $accountId = $payload['account_id'] ?? null;
        $dcId = $payload['dc_id'] ?? null;
        $label = $payload['label'] ?? null;
        if (! is_int($accountId) || ! is_int($dcId) || ! is_string($label)) {
            return null;
        }

        return new RelayContext(accountId: $accountId, dcId: $dcId, label: $label);
    }

    private function key(string $scope): string
    {
        return $scope === self::SCOPE_GLOBAL
            ? self::KEY
            : self::KEY.':'.$this->sanitizeScope($scope);
    }

    private function generationKey(string $scope): string
    {
        return $scope === self::SCOPE_GLOBAL
            ? self::GENERATION_KEY
            : self::GENERATION_KEY.':'.$this->sanitizeScope($scope);
    }

    /** Strip control characters so a scope cannot inject cache-key separators. */
    private function sanitizeScope(string $scope): string
    {
        $scope = str_replace(["\r", "\n", "\0"], '', $scope);

        return $scope === '' ? 'anonymous' : $scope;
    }
}
