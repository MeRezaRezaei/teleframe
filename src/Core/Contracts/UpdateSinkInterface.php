<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\Contracts;

/**
 * Contract for consuming and dispatching raw incoming Telegram updates.
 * Allows higher-level packages to plug in custom pipelines (Redis Streams, Postgres storage, Spatie DTOs, etc.).
 */
interface UpdateSinkInterface
{
    /**
     * Handle an incoming raw update from Telegram.
     *
     * Backpressure contract: return `true` when the update was processed
     * (or deliberately consumed), `false` to signal "skip / not now" so
     * producers and pipelines can react (drop, retry, defer) instead of
     * assuming silent success. Implementations must not throw to signal
     * a soft refusal.
     *
     * @param array<string, mixed> $update Raw Telegram update payload
     * @param string|null $source Identifier of the source (e.g. bot token, user account ID)
     * @param int|null $ts The entry's own timestamp, when the transport has one.
     *        ADDED 2026-10-17 (NEW-1) and OPTIONAL on purpose: an existing
     *        three-implementation seam keeps type-checking, while a producer that
     *        HAS a timestamp can finally pass it instead of having it dropped.
     *        Ignoring it costs correctness, not compatibility — with `$ts` null
     *        the identity row's own per-entry fields still discriminate.
     */
    public function handle(array $update, ?string $source = null, ?int $ts = null): bool;
}
